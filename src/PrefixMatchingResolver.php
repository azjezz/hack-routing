<?php

declare(strict_types=1);

namespace HackRouting;

use HackRouting\HttpException\NotFoundException;
use HackRouting\PrefixMatching\PrefixMap;
use Psl\{Dict, Iter, Regex, Str, Type};

/**
 * @template-covariant TResponder
 *
 * @implements IResolver TResponder
 */
final class PrefixMatchingResolver implements IResolver
{
    /**
     * @param array<non-empty-string, PrefixMap<TResponder>> $map
     */
    public function __construct(private array $map)
    {
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
            static fn(array $flat_map): PrefixMap => PrefixMap::fromFlatMap($flat_map)
        ));
    }

    /**
     * @param non-empty-string $method
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

        return $this->resolveWithMap($path, $map);
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
        if (Iter\contains_key($literals, $path)) {
            return array($literals[$path], []);
        }

        $prefixes = $map->getPrefixes();
        if (!Iter\is_empty($prefixes)) {
            $prefix_len = Str\length((string)Iter\first_key($prefixes));
            $prefix = Str\slice($path, 0, $prefix_len);
            if (Iter\contains_key($prefixes, $prefix)) {
                return $this->resolveWithMap(
                    Str\strip_prefix($path, $prefix),
                    $prefixes[$prefix],
                );
            }
        }

        $regexps = $map->getRegexps();
        foreach ($regexps as $regexp => $sub) {
            $pattern = '#^' . $regexp . '#';
            $matches = Regex\first_match($path, $pattern);
            if (null === $matches) {
                continue;
            }

            $matched = $matches[0];
            $remaining = Str\strip_prefix($path, $matched);

            $data = Dict\filter_keys($matches, static fn($key) => Type\string()->matches($key));
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
