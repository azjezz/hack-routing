<?php

declare(strict_types=1);

namespace HackRouting;

require_once(__DIR__ . '/../vendor/autoload.php');

use Psl\Dict;
use Psl\Math;
use Psl\PseudoRandom;
use Psl\Str;
use Psl\Vec;
use Psl\Json;
use Psl\IO;

// Dump out a massive URI map for testing/benchmarking
final class RandomUriMapGenerator
{
    public const  TOP_LEVEL_COUNT = 500;

    public static function main(): void
    {
        $output = IO\output_handle();
        $map = Vec\sort_by(Vec\filter(Vec\concat(...Vec\map(
            Vec\range(1, self::TOP_LEVEL_COUNT),
            fn($_) => self::generateExampleInner(0),
        )), fn($row) => !Str\starts_with($row[0], '/{')), fn($row) => $row[0]);

        $output->write(Json\encode($map, pretty: true) . "\n");
    }

    /**
     * @return list<array{0: string, 1: array<string, array<string, string>>}>
     */
    private static function generateExampleInner(int $depth): array
    {
        $base = self::generateExampleComponent();
        $child_free = PseudoRandom\int(0, 5) <= $depth;
        if ($child_free) {
            return [$base];
        }

        $children = Vec\values(Dict\unique_by(
            Vec\concat(...Vec\map(
                Vec\range(2, PseudoRandom\int(2, Math\maxva(2, Math\div(10, $depth + 1)))),
                fn($_) => self::generateExampleInner($depth + 1),
            )),
            function (array $row): string {
                $pattern = $row[0];
                if (Str\starts_with($pattern, '/{')) {
                    return Str\slice($pattern, 0, 4);
                }
                return $pattern;
            },
        ));

        [$prefix, $base_examples] = $base;

        return Vec\map(
            $children,
            function ($child) use ($base_examples, $prefix) {
                [$suffix, $child_examples] = $child;
                $examples = [];
                foreach ($base_examples as $base_uri => $base_data) {
                    foreach ($child_examples as $child_uri => $child_data) {
                        $examples[$base_uri . $child_uri] =
                            Dict\merge($base_data, $child_data);
                    }
                }

                return array($prefix . $suffix, $examples);
            },
        );
    }

    private static function randomAlnum(int $min_length, int $max_length): string
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';
        $alphabet_max = Str\length($alphabet) - 1;
        $len = PseudoRandom\int($min_length, $max_length);
        $out = '';
        for ($i = 0; $i < $len; ++$i) {
            $out .= $alphabet[PseudoRandom\int(0, $alphabet_max)];
        }
        return $out;
    }

    // It's important that more specific regexps sort first
    public const INT_REGEXP_PREFIX = 'a_';
    public const DEFAULT_REGEXP_PREFIX = 'b_';

    /**
     * @return array{0: string, 1: array<string, array<string, string>>}
     */
    private static function generateExampleComponent(): array
    {
        switch (PseudoRandom\int(0, 10)) {
            // Component with default regexp
            case 0:
                $name = self::DEFAULT_REGEXP_PREFIX . self::randomAlnum(5, 15);
                return array(
                    '/{' . $name . '}/',
                    Dict\pull(Vec\map(Vec\fill(PseudoRandom\int(1, 5), ''), fn($_) => self::randomAlnum(5, 15)), fn($v) => [$name => $v], fn($v) => '/' . $v . '/'),
                );
            case 1:
                // Component with int regexp
                $name = self::INT_REGEXP_PREFIX . self::randomAlnum(5, 15);
                return array(
                    '/{' . $name . ':\\d+}/',
                    Dict\pull(Vec\map(Vec\fill(PseudoRandom\int(1, 5), ''), fn($_) => (string)PseudoRandom\int(1, Math\INT64_MAX)), fn($v) => [$name => $v], fn($v) => '/' . $v . '/'),
                );
            // Literal
            default:
                $value = self::randomAlnum(5, 15);
                return array($value, [$value => []]);
        }
    }
}

RandomUriMapGenerator::main();
