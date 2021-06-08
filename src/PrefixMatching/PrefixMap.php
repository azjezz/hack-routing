<?php

namespace HackRouting\PrefixMatching;

use HackRouting\PatternParser\LiteralNode;
use HackRouting\PatternParser\Node;
use HackRouting\PatternParser\ParameterNode;
use HackRouting\PatternParser\Parser;
use Psl;
use Psl\Dict;
use Psl\Vec;

use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function min;
use function strlen;
use function substr;

/**
 * @template TResponder
 */
final class PrefixMap
{
    /**
     * @readonly
     *
     * @var array<string, TResponder>
     */
    public array $literals;

    /**
     * @readonly
     *
     * @var array<string, PrefixMap<TResponder>>
     */
    public array $prefixes;

    /**
     * @readonly
     *
     * @var array<string, PrefixMapOrResponder<TResponder>>
     */
    public array $regexps;

    /**
     * @readonly
     *
     * @var int
     */
    public int $prefixLength;

    /**
     * @param array<string, TResponder> $literals
     * @param array<string, PrefixMap<TResponder>> $prefixes
     * @param array<string, PrefixMapOrResponder<TResponder>> $regexps
     */
    public function __construct(
        array $literals,
        array $prefixes,
        array $regexps,
        int $prefixLength,
    ) {
        $this->literals = $literals;
        $this->prefixes = $prefixes;
        $this->regexps = $regexps;
        $this->prefixLength = $prefixLength;
    }

    /**
     * @return array<string, TResponder>
     */
    public function getLiterals(): array
    {
        return $this->literals;
    }

    /**
     * @return array<string, PrefixMap<TResponder>>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * @return array<string, PrefixMapOrResponder<TResponder>>
     */
    public function getRegexps(): array
    {
        return $this->regexps;
    }

    public function getPrefixLength(): int
    {
        return $this->prefixLength;
    }

    /**
     * @template Ts
     *
     * @param array<string, Ts> $map
     *
     * @return PrefixMap<Ts>
     */
    public static function fromFlatMap(array $map): PrefixMap
    {
        $entries = Vec\map_with_key(
            $map,
            /**
             * @param Ts $responder
             *
             * @return array{0: list<Node>, 1: Ts}
             */
            static fn(string $pattern, mixed $responder): array => [
                Parser::parse($pattern)->getChildren(),
                $responder
            ],
        );

        return self::fromFlatMapImpl($entries);
    }

    /**
     * @template Ts
     *
     * @param list<array{0: list<Node>, 1: Ts}> $entries
     *
     * @return PrefixMap<Ts>
     */
    private static function fromFlatMapImpl(array $entries): PrefixMap
    {
        $literals = [];
        $prefixes = [];
        $regexps = [];
        foreach ($entries as [$nodes, $responder]) {
            if (!$nodes) {
                $literals[''] = $responder;
                continue;
            }

            $node = array_shift($nodes);
            $nodes = array_values($nodes);
            if ($node instanceof LiteralNode) {
                if (!$nodes) {
                    $literals[$node->getText()] = $responder;
                } else {
                    $prefixes[] = [$node->getText(), $nodes, $responder];
                }

                continue;
            }

            if ($node instanceof ParameterNode && $node->getRegexp() === null) {
                $next = $nodes[0] ?? null;
                if ($next instanceof LiteralNode && $next->getText()[0] === '/') {
                    $regexps[] = [$node->asRegexp('#'), $nodes, $responder];
                    continue;
                }
            }

            $regexps[] = [
                implode('', array_map(
                    static fn(Node $n): string => $n->asRegexp('#'),
                    array_merge([$node], $nodes),
                )),
                [],
                $responder,
            ];
        }

        /** @var array<string, list<array{0: string, 1: list<Node>, 2: Ts}>> $by_first */
        $by_first = Dict\group_by(
            $prefixes,
            /**
             * @param array{0: string, 1: list<Node>, 2: Ts} $entry
             */
            static fn(array $entry): string => $entry[0]
        );

        [$prefix_length, $grouped] = self::groupByCommonPrefix(array_keys($by_first));
        $prefixes = Dict\map_with_key(
            $grouped,
            /**
             * @param list<string> $keys
             */
            static function (string|int $prefix, array $keys) use ($by_first, $prefix_length): PrefixMap {
                $prefix = (string)$prefix;
                return self::fromFlatMapImpl(array_merge(...array_map(
                    /**
                     * @return list<array{0: list<Node>, 1: Ts}>
                     */
                    static fn(string $key) => array_map(
                        /**
                         * @param array{0: string, 1: list<Node>, 2: Ts} $row
                         *
                         * @return array{0: list<Node>, 1: Ts}
                         */
                        static function (array $row) use ($prefix, $prefix_length): array {
                            [$text, $nodes, $responder] = $row;
                            if ($text === $prefix) {
                                return [$nodes, $responder];
                            }

                            $suffix = substr($text, $prefix_length);
                            return [
                                array_merge([new LiteralNode($suffix)], $nodes),
                                $responder,
                            ];
                        },
                        $by_first[$key],
                    ),
                    $keys,
                )));
            },
        );

        $by_first = Dict\group_by(
            $regexps,
            /**
             * @param array{0: string, 1: list<Node>, 2: Ts} $entry
             */
            static fn(array $entry): string => $entry[0]
        );
        $regexps = [];
        foreach ($by_first as $first => $group_entries) {
            if (count($group_entries) === 1) {
                [, $nodes, $responder] = $group_entries[0];
                $rest = implode('', array_map(static fn(Node $n): string => $n->asRegexp('#'), $nodes));
                $regexps[$first . $rest] = new PrefixMapOrResponder(null, $responder);
                continue;
            }

            $regexps[$first] = new PrefixMapOrResponder(
                self::fromFlatMapImpl(array_map(
                /**
                 * @param array{0: string, 1: list<Node>, 2: Ts} $e
                 *
                 * @return array{0: list<Node>, 1: Ts}
                 */
                    static fn(array $e): array => [$e[1], $e[2]],
                    $group_entries,
                )),
                null,
            );
        }

        return new self($literals, $prefixes, $regexps, $prefix_length);
    }

    /**
     * @param list<string> $keys
     *
     * @return array{0: int, 1: array<string, list<string>>}
     */
    private static function groupByCommonPrefix(array $keys): array
    {
        if (!$keys) {
            return [0, []];
        }

        $lens = array_map(static fn(string $key): int => strlen($key), $keys);
        $min = min($lens);
        Psl\invariant($min !== 0, "Shouldn't have 0-length prefixes");

        return [$min, Dict\group_by($keys, static fn(string $key): string => substr($key, 0, $min))];
    }

    /**
     * @return array{
     *   literals: array<string, TResponder>,
     *   prefixes: array<string, PrefixMap<TResponder>>,
     *   regexps: array<string, PrefixMapOrResponder<TResponder>>,
     *   prefix_length: int
     * }
     *
     * @internal
     */
    public function __serialize(): array
    {
        return [
            'literals' => $this->literals,
            'prefixes' => $this->prefixes,
            'regexps' => $this->regexps,
            'prefix_length' => $this->prefixLength,
        ];
    }

    /**
     * @param array{
     *   literals: array<string, TResponder>,
     *   prefixes: array<string, PrefixMap<TResponder>>,
     *   regexps: array<string, PrefixMapOrResponder<TResponder>>,
     *   prefix_length: int
     * } $data
     *
     * @internal
     */
    public function __unserialize(array $data): void
    {
        [
            'literals' => $this->literals,
            'prefixes' => $this->prefixes,
            'regexps' => $this->regexps,
            'prefix_length' => $this->prefixLength,
        ] = $data;
    }
}
