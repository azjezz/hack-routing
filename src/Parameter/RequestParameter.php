<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

abstract class RequestParameter
{
    /** Convert to T or throw an exception if failed. */
    abstract public function assert(string $input): mixed;

    public function __construct(private string $name)
    {
    }

    final public function getName(): string
    {
        return $this->name;
    }
}
