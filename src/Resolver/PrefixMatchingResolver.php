<?php

declare(strict_types=1);

namespace HackRouting\Resolver;

use HackRouting\HttpException\NotFoundException;
use HackRouting\PrefixMatching\PrefixMap;
use function array_merge;
use function is_string;
use function preg_match;
use function strlen;
use function substr;

/**
 * @template TResponder
 *
 * @implements ResolverInterface<TResponder>
 */
final class PrefixMatchingResolver implements ResolverInterface
{
    /**
     * @param array<non-empty-string, PrefixMap<TResponder>> $map
     */
    public function __construct(private array $map)
    {
    }

    /**
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @template Tr
     *
     * @param array<non-empty-string, array<string, Tr>> $map
     *
     * @return PrefixMatchingResolver<Tr>
     */
    public static function fromFlatMap(array $map): PrefixMatchingResolver
    {
        return new self(array_map(
            /**
             * @param array<string, Tr> $flat_map
             */
            static fn(array $flat_map): PrefixMap => PrefixMap::fromFlatMap($flat_map),
            $map,
        ));
    }

    /**
     * @param non-empty-string $method
     * @param non-empty-string $path
     *
     * @return array{0: TResponder, array<string, string>}
     *
     * @throws NotFoundException
     */
    public function resolve(string $method, string $path): array
    {
        $map = $this->map[$method] ?? null;
        if ($map === null) {
            throw new NotFoundException();
        }

        return self::resolveWithMap($path, $map);
    }

    /**
     * @template T
     *
     * @param non-empty-string $path
     * @param PrefixMap<T> $map
     *
     * @return array{0: T, array<string, string>}
     *
     * @throws NotFoundException
     */
    private static function resolveWithMap($path, $map)
    {
        if (isset($map->literals[$path])) {
            return [$map->literals[$path], []];
        }

        if ($prefixes = $map->prefixes) {
            $prefix = substr($path, 0, $map->getPrefixLength());
            if (isset($prefixes[$prefix])) {
                return self::resolveWithMap(
                    substr($path, $map->prefixLength),
                    $prefixes[$prefix],
                );
            }
        }

        foreach ($map->regexps as $regexp => $sub) {
            if (preg_match('#^' . $regexp . '#', $path, $matches) !== 1) {
                continue;
            }

            $matched = $matches[0];
            $remaining = substr($path, strlen($matched));

            /** @var array<string, string> $data */
            $data = [];
            foreach ($matches as $name => $match) {
                if (is_string($name)) {
                    $data[$name] = $match;
                }
            }

            if ($remaining === '') {
                return [$sub->getResponder(), $data];
            }

            try {
                [$responder, $sub_data] = self::resolveWithMap($remaining, $sub->getMap());
            } catch (NotFoundException) {
                continue;
            }

            return [$responder, array_merge($data, $sub_data)];
        }

        throw new NotFoundException();
    }
}
