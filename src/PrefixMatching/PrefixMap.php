<?php

namespace HackRouting\PrefixMatching;

use HackRouting\PatternParser\LiteralNode;
use HackRouting\PatternParser\Node;
use HackRouting\PatternParser\ParameterNode;
use HackRouting\PatternParser\Parser;
use Psl;
use Psl\Dict;
use Psl\Iter;
use Psl\Math;
use Psl\Str;
use Psl\Str\Byte;
use Psl\Vec;

/**
 * @template TResponder
 */
final class PrefixMap
{
    /**
     * @param array<string, TResponder> $literals
     * @param array<string, PrefixMap<TResponder>> $prefixes
     * @param array<string, PrefixMapOrResponder<TResponder>> $regexps
     */
    public function __construct(
        private array $literals,
        private array $prefixes,
        private array $regexps,
    ) {
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
            if (Iter\is_empty($nodes)) {
                $literals[''] = $responder;
                continue;
            }

            $node = Iter\first($nodes);
            $nodes = Vec\values(Dict\drop($nodes, 1));
            if ($node instanceof LiteralNode) {
                if (Iter\is_empty($nodes)) {
                    $literals[$node->getText()] = $responder;
                } else {
                    $prefixes[] = [$node->getText(), $nodes, $responder];
                }

                continue;
            }

            if ($node instanceof ParameterNode && $node->getRegexp() === null) {
                $next = Iter\first($nodes);
                if ($next instanceof LiteralNode) {
                    if (Byte\starts_with($next->getText(), '/')) {
                        $regexps[] = [$node->asRegexp('#'), $nodes, $responder];
                        continue;
                    }
                }
            }

            $regexps[] = [
                Str\join(Vec\map(
                    Vec\concat([$node], $nodes),
                    static fn(Node $n): string => $n->asRegexp('#')
                ), ''),
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

        $grouped = self::groupByCommonPrefix(Vec\keys($by_first));
        $prefixes = Dict\map_with_key(
            $grouped,
            /**
             * @param list<string> $keys
             */
            static fn(string $prefix, array $keys) => self::fromFlatMapImpl(Vec\concat(...Vec\map(
                $keys,
                /**
                 * @return list<array{0: list<Node>, 1: Ts}>
                 */
                static fn(string $key) => Vec\map(
                    $by_first[$key],
                    /**
                     * @param array{0: string, 1: list<Node>, 2: Ts} $row
                     *
                     * @return array{0: list<Node>, 1: Ts}
                     */
                    static function (array $row) use ($prefix): array {
                        if ($row[0] === $prefix) {
                            return [$row[1], $row[2]];
                        }

                        $suffix = Byte\strip_prefix($row[0], $prefix);
                        return [
                            Vec\concat([new LiteralNode($suffix)], $row[1]),
                            $row[2],
                        ];
                    },
                ),
            ))),
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
            if (Iter\count($group_entries) === 1) {
                [, $nodes, $responder] = $group_entries[0];
                $rest = Str\join(Vec\map($nodes, static fn(Node $n): string => $n->asRegexp('#')), '');
                $regexps[$first . $rest] = new PrefixMapOrResponder(null, $responder);
                continue;
            }

            $regexps[$first] = new PrefixMapOrResponder(
                self::fromFlatMapImpl(Vec\map(
                    $group_entries,
                    /**
                     * @param array{0: string, 1: list<Node>, 2: Ts} $e
                     *
                     * @return array{0: list<Node>, 1: Ts}
                     */
                    static fn(array $e): array => [$e[1], $e[2]]
                )),
                null,
            );
        }

        // optimize prefixes[/foo] -> regexps[

        return new self($literals, $prefixes, $regexps);
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, list<string>>
     */
    private static function groupByCommonPrefix(array $keys): array
    {
        if (Iter\is_empty($keys)) {
            return [];
        }
        $lens = Vec\map($keys, static fn(string $key): int => Byte\length($key));
        $min = Math\min($lens);
        Psl\invariant($min !== 0, "Shouldn't have 0-length prefixes");

        return Dict\group_by($keys, static fn(string $key): string => Byte\slice($key, 0, $min));
    }

    /**
     * @return array{
     *   literals: array<string, TResponder>,
     *   prefixes: array<string, PrefixMap<TResponder>>,
     *   regexps: array<string, PrefixMapOrResponder<TResponder>>
     * }
     *
     * @internal
     */
    public function __serialize(): array
    {
        return ['literals' => $this->literals, 'prefixes' => $this->prefixes, 'regexps' => $this->regexps];
    }

    /**
     * @param array{
     *   literals: array<string, TResponder>,
     *   prefixes: array<string, PrefixMap<TResponder>>,
     *   regexps: array<string, PrefixMapOrResponder<TResponder>>
     * } $data
     *
     * @internal
     */
    public function __unserialize(array $data): void
    {
        ['literals' => $this->literals, 'prefixes' => $this->prefixes, 'regexps' => $this->regexps] = $data;
    }
}
