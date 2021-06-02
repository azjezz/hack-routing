<?php

namespace HackRouting\PrefixMatching;

use HackRouting\PatternParser\{LiteralNode, Node, ParameterNode, Parser};
use Psl\{Dict, Iter, Math, Str, Vec};
use Psl;

/**
 * @template T
 */
final class PrefixMap
{
    /**
     * @param array<string, T> $literals
     * @param array<string, PrefixMap<T>> $prefixes
     * @param array<string, PrefixMapOrResponder<T>> $regexps
     */
    public function __construct(
        private array $literals,
        private array $prefixes,
        private array $regexps,
    ) {
    }

    /**
     * @return array<string, T>
     */
    public function getLiterals(): array
    {
        return $this->literals;
    }

    /**
     * @return array<string, PrefixMap<T>>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * @return array<string, PrefixMapOrResponder<T>>
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
            fn ($pattern, $responder) => [Parser::parse($pattern)->getChildren(), $responder],
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
    private static function fromFlatMapImpl(
        array $entries,
    ): PrefixMap {
        $literals = [];
        $prefixes = [];
        $regexps = [];
        foreach ($entries as [$nodes, $responder]) {
            if (Iter\is_empty($nodes)) {
                $literals[''] = $responder;
                continue;
            }

            /** @var Node $node */
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
                if (
                    $next instanceof LiteralNode && Str\starts_with($next->getText(), '/')
                ) {
                    $regexps[] = array($node->asRegexp('#'), $nodes, $responder);
                    continue;
                }
            }
            $regexps[] = array(
                Str\join(Vec\map(Vec\concat([$node], $nodes), fn ($n) => $n->asRegexp('#')), ''),
                [],
                $responder,
            );
        }

        $by_first = Dict\group_by($prefixes, fn ($entry) => $entry[0]);
        $grouped = self::groupByCommonPrefix(Vec\keys($by_first));
        $prefixes = Dict\map_with_key(
            $grouped,
            static fn ($prefix, $keys) => self::fromFlatMapImpl(Vec\concat(...Vec\map(
                $keys,
                fn ($key) => Vec\map(
                    $by_first[$key],
                    static function (array $row) use ($prefix) {
                        if ($row[0] === $prefix) {
                            return array($row[1], $row[2]);
                        }

                        $suffix = Str\strip_prefix($row[0], $prefix);
                        return array(
                            Vec\concat([new LiteralNode($suffix)], $row[1]),
                            $row[2],
                        );
                    },
                ),
            ))),
        );

        $by_first = Dict\group_by($regexps, fn ($entry) => $entry[0]);
        $regexps = [];
        foreach ($by_first as $first => $group_entries) {
            if (Iter\count($group_entries) === 1) {
                [, $nodes, $responder] = $group_entries[0];
                $rest = Str\join(Vec\map($nodes, fn ($n) => $n->asRegexp('#')), '');
                $regexps[$first . $rest] = new PrefixMapOrResponder(null, $responder);
                continue;
            }

            $regexps[$first] = new PrefixMapOrResponder(
                self::fromFlatMapImpl(Vec\map($group_entries, fn ($e) => array($e[1], $e[2]))),
                null,
            );
        }

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
        $lens = Vec\map($keys, static fn (string $key): int => Str\length($key));
        $min = Math\min($lens);
        Psl\invariant($min !== 0, "Shouldn't have 0-length prefixes");

        return Dict\group_by($keys, static fn (string $key): string => Str\slice($key, 0, $min));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getSerializable(): array
    {
        return Dict\filter([
            'literals' => $this->literals,
            'prefixes' => Dict\map($this->prefixes, fn ($it) => $it->getSerializable()),
            'regexps' => Dict\map($this->regexps, fn ($it) => $it->getSerializable()),
        ], fn ($it) => !Iter\is_empty($it));
    }

    /**
     * @internal
     */
    public static function __set_state($state): PrefixMap
    {
        return new self(
            $state['literals'],
            $state['prefixes'],
            $state['regexps'],
        );
    }
    
    public function __serialize(): array
    {
        return [$this->literals, $this->prefixes, $this->regexps];
    }
    
    public function __unserialize(array $data): void
    {
        [$this->literals, $this->prefixes, $this->regexps] = $data;
    }
}
