<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use HackRouting\UriPattern\UriPatternPart;

abstract class UriParameter extends RequestParameter implements UriPatternPart
{
    abstract public function getRegExpFragment(): ?string;

    final public function getRouteFragment(): string
    {
        $name = $this->getName();
        $re = $this->getRegExpFragment();
        if ($re === null) {
            return '{' . $name . '}';
        }

        return '{' . $name . ':' . $re . '}';
    }
}
