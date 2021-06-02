<?php

declare(strict_types=1);

namespace HackRouting;

require_once(__DIR__ . '/../vendor/autoload.php');

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use HackRouting\Cache\FileCache;
use HackRouting\Cache\MemoryCache;
use HackRouting\HttpException\NotFoundException;
use Psl\{Dict, Env, Filesystem, IO, Iter, Json, Math, Str, Type, Vec, Hash};
use Psl;
use Psr\Cache\CacheItemPoolInterface;
use function microtime;

function write(string $message, ...$args): void
{
    static $output = null;
    if (null === $output) {
        $output = IO\output_handle();
    }

    $output->write(Str\format($message, ...$args) . "\n");
}

function write_error(string $message, ...$args): void
{
    static $output = null;
    if (null === $output) {
        /** @var IO\WriteHandleInterface $output */
        $output = IO\error_handle();
    }

    $output->write(Str\format($message, ...$args) . "\n");
}

final class NaiveBenchmark
{
    public static function main(): void
    {
        write(
            "Map has %d entries and %d URIs",
            Iter\count(self::getMap()),
            Math\sum(Vec\map(self::getMap(), fn ($row) => Iter\count($row[1]))),
        );

        $impls = self::getImplementations();

        self::testImplementations('Cold', $impls);

        self::testImplementations('Warm', $impls);
    }

    /**
     * @param array<string, (callable(): IResolver<string>)>
     */
    private static function testImplementations(string $run_name, array $impls): void
    {
        foreach ($impls as $name => $impl) {
            write("%s run for %s...", $run_name, $name);
            [$init, $lookup, $lookup_per_item] = self::testImplementation($name, $impl);
            write(
                "... done (init: %0.02fms, lookups: %0.02fms, " .
                    "per lookup: %0.02fms, estimated total per request: %0.02fms)\n",
                $init * 1000,
                $lookup * 1000,
                $lookup_per_item * 1000,
                ($init + $lookup_per_item) * 1000,
            );
        }
    }

    /**
     * @param (callable(): IResolver<string>) $impl
     * 
     * @return array{0: float, 1: float, 2: float}
     * 
     * @throws NotFoundException
     */
    private static function testImplementation(string $name, callable $impl): array
    {
        $create_start = microtime(true);
        $impl = $impl();
        $create_time = microtime(true) - $create_start;

        $map = self::getMap();
        $resolve_time = 0.0;
        $lookups = 0;
        foreach ($map as $row) {
            [$expected_responder, $examples] = $row;
            foreach ($examples as $uri => $expected_data) {
                ++$lookups;
                $resolve_start = microtime(true);
                try {
                    [$responder, $data] = $impl->resolve(HttpMethod::GET, $uri);
                } catch (NotFoundException $e) {
                    write(
                        "!!! %s failed to resolve %s - expected %s !!!\n",
                        $name,
                        $uri,
                        $expected_responder,
                    );

                    throw $e;
                }
                $resolve_time += microtime(true) - $resolve_start;

                Psl\invariant(
                    $responder === $expected_responder,
                    "For resolver %s:\nFor path %s:\n  Expected: %s\n  Actual: %s\n",
                    $name,
                    $uri,
                    $expected_responder,
                    $responder,
                );
                $pretty_data =
                    /**
                     * @param array<string, string> $dict
                     */
                    static fn (array $dict): string => Str\join(Vec\map(Str\split(\var_export($dict, true), "\n"), fn (string $line): string => '    ' . $line), "\n");

                Psl\invariant(
                    $data === $expected_data,
                    "For resolver: %s\nFor path %s:\n  Expected data:\n%s\n  Actual data:\n%s\n",
                    $name,
                    $uri,
                    $pretty_data($expected_data),
                    $pretty_data($data),
                );
            }
        }

        return array($create_time, $resolve_time, $resolve_time / $lookups);
    }

    /**
     * @return list<array{0: string, 1: array<string, array<string, string>>}>
     */
    private static function getMap(): array
    {
        static $cache = null;
        if (null === $cache) {
            $content = Filesystem\read_file(__DIR__ . '/../data/med-random-map.json');
            $cache = Json\typed($content, Type\vec(Type\shape([
                0 => Type\string(),
                1 => Type\dict(
                    Type\string(),
                    Type\dict(Type\string(), Type\string())
                )
            ])));
        }

        return $cache;
    }

    /**
     * @return array<string, (callable(): IResolver<string>)>
     */
    private static function getImplementations(): array
    {
        $fast_route_cache = Filesystem\create_temporary_file(Env\temp_dir(), 'frcache');
        if (Filesystem\is_file($fast_route_cache)) {
            Filesystem\delete_file($fast_route_cache);
        }

        $routes = Vec\map(self::getMap(), fn ($row) => $row[0]);
        $map = [HttpMethod::GET => Dict\associate($routes, $routes)];

        return [
            'simple regexp                ~> ' => static fn () => new SimpleRegexpResolver($map),
            'uncached prefix match        ~> ' => static fn () => PrefixMatchingResolver::fromFlatMap($map),
            'cached prefix match - file   ~> ' => static function () use ($map) {
                $cache = new FileCache();
                $prefix_map = $cache->fetch(__FUNCTION__ . 'file', function() use($map) {
                    return Dict\map($map, fn ($v) => PrefixMatching\PrefixMap::fromFlatMap($v));
                });

                return new PrefixMatchingResolver($prefix_map);
            },
            'cached prefix match - memory ~> ' => static function () use ($map) {
                $memory = new MemoryCache();
                $prefix_map = $memory->fetch(__FUNCTION__ . 'memory', function() use($map) {
                    return Dict\map($map, fn ($v) => PrefixMatching\PrefixMap::fromFlatMap($v));
                });

                return new PrefixMatchingResolver($prefix_map);
            },
        ];
    }
}

NaiveBenchmark::main();
