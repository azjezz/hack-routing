<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl\Str ;
use Psl\Vec;

final class PatternNode implements Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(private array $children)
    {
    }

    /**
     * @return list<Node>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function toStringForDebug(): string
    {
        return '[' . Str\join(Vec\map(
            $this->children,
            static fn (Node $child): string => $child->toStringForDebug()
        ), ', ') . ']';
    }

    public function asRegexp(string $delimiter): string
    {
        return Str\join(Vec\map(
            $this->children,
            static fn (Node $child): string => $child->asRegexp($delimiter)
        ), '');
    }
}
