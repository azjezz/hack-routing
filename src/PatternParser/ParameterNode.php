<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl\Str;
use function preg_quote;

final class ParameterNode implements Node
{
    public function __construct(private string $name, private ?string $regexp)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegexp(): ?string
    {
        return $this->regexp;
    }

    public function toStringForDebug(): string
    {
        $re = $this->getRegexp();
        if ($re === null) {
            return '{' . $this->getName() . '}';
        }

        return Str\format('{%s: #%s#}', $this->getName(), $re);
    }

    public function asRegexp(string $delimiter): string
    {
        $re = $this->getRegexp();
        if ($re === null) {
            $re = '[^/]+';
        }
        return '(?<' . preg_quote($this->getName(), $delimiter) . '>' . $re . ')';
    }
}
