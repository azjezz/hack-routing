<?php

declare(strict_types=1);

namespace HackRouting\Resolver;

use HackRouting\HttpException\NotFoundException;
use HackRouting\PrefixMatching\PrefixMap;
use Psl\Dict;
use Psl\Iter;
use Psl\Str\Byte;

use function is_string;
use function preg_match;

/**
 * @template TResponder
 *
 * @implements ResolverInterface<TResponder>
 */
final class PrefixMatchingResolver implements ResolverInterface
{
    /**
     * @var array<non-empty-string, array<non-empty-string, array{0: TResponder, array<string, string>}>>
     */
    private array $lookup = [];

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
        return new self(Dict\map(
            $map,
            /**
             * @param array<string, Tr> $flat_map
             */
            static fn (array $flat_map): PrefixMap => PrefixMap::fromFlatMap($flat_map)
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
        if (isset($this->lookup[$method][$path])) {
            return $this->lookup[$method][$path];
        }

        $map = $this->map[$method] ?? null;
        if ($map === null) {
            throw new NotFoundException();
        }

        return $this->lookup[$method][$path] = $this->resolveWithMap($path, $map);
    }

    /**
     * @param PrefixMap<TResponder> $map
     *
     * @return array{0: TResponder, array<string, string>}
     *
     * @throws NotFoundException
     */
    private function resolveWithMap(string $path, PrefixMap $map): array
    {
        $literals = $map->getLiterals();
        if (isset($literals[$path])) {
            return [$literals[$path], []];
        }

        $prefixes = $map->getPrefixes();
        if ($prefixes) {
            $prefix_len = Byte\length((string)Iter\first_key($prefixes));
            $prefix = Byte\slice($path, 0, $prefix_len);
            if (isset($prefixes[$prefix])) {
                return $this->resolveWithMap(
                    Byte\strip_prefix($path, $prefix),
                    $prefixes[$prefix],
                );
            }
        }

        foreach ($map->getRegexps() as $regexp => $sub) {
            if (preg_match('#^' . $regexp . '#', $path, $matches) !== 1) {
                continue;
            }

            $matched = $matches[0];
            $remaining = Byte\strip_prefix($path, $matched);

            /** @var array<string, string> $data */
            $data = Dict\filter_keys($matches, static fn (int|string $key): bool => is_string($key));

            if ($sub->isResponder()) {
                if ($remaining === '') {
                    return array($sub->getResponder(), $data);
                }
                continue;
            }

            try {
                [$responder, $sub_data] = $this->resolveWithMap($remaining, $sub->getMap());
            } catch (NotFoundException) {
                continue;
            }

            return array($responder, Dict\merge($data, $sub_data));
        }

        throw new NotFoundException();
    }
}
