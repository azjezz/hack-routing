<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

use HackRouting\Parameter\EnumRequestParameter;
use HackRouting\Parameter\IntRequestParameter;
use HackRouting\Parameter\StringRequestParameter;
use HackRouting\Parameter\UriParameter;
use Psl\Str;
use Psl\Vec;

class UriPattern implements HasRouteFragment
{
    /**
     * @var list<UriPatternPart>
     */
    private array $parts = [];

    final public function appendPart(UriPatternPart $part): static
    {
        $this->parts[] = $part;
        return $this;
    }

    final public function getRouteFragment(): string
    {
        $fragments = Vec\map($this->parts, fn(UriPatternPart $part): string => $part->getRouteFragment());

        return Str\join($fragments, '');
    }

    /**
     * @return list<UriPatternPart>
     */
    final public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * @return list<UriParameter>
     */
    final public function getParameters(): array
    {
        $out = [];
        foreach ($this->parts as $part) {
            if ($part instanceof UriParameter) {
                $out[] = $part;
            }
        }

        return $out;
    }

    final public function literal(string $part): static
    {
        return $this->appendPart(new UriPatternLiteral($part));
    }

    final public function slash(): static
    {
        return $this->literal('/');
    }

    final public function string(string $name): static
    {
        return $this->appendPart(new StringRequestParameter(
            false,
            $name,
        ));
    }

    final public function stringWithSlashes(string $name): static
    {
        return $this->appendPart(new StringRequestParameter(true, $name));
    }

    final public function int(string $name): static
    {
        return $this->appendPart(new IntRequestParameter($name));
    }

    /**
     * @param non-empty-list<non-empty-string> $values
     */
    final public function enum(string $name, array $values): static
    {
        return $this->appendPart(new EnumRequestParameter($name, $values));
    }
}
