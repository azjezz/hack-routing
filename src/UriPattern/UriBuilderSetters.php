<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

use HackRouting\Parameter\EnumRequestParameter;
use HackRouting\Parameter\IntRequestParameter;
use HackRouting\Parameter\StringRequestParameter;

/**
 * @psalm-require-extends UriBuilderBase
 */
trait UriBuilderSetters
{
    final public function setString(string $name, string $value): static
    {
        return $this->setValue(StringRequestParameter::class, $name, $value);
    }

    final public function setInt(string $name, int $value): static
    {
        return $this->setValue(IntRequestParameter::class, $name, $value);
    }

    final public function setEnum(string $name, string $value): static
    {
        return $this->setValue(EnumRequestParameter::class, $name, $value);
    }
}
