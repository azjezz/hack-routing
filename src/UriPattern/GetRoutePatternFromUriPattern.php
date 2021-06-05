<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

/**
 * @psalm-require-implements HasUriPattern
 */
trait GetRoutePatternFromUriPattern
{
    final public static function getRoutePattern(): string
    {
        return static::getUriPattern()->getRouteFragment();
    }

    abstract public static function getUriPattern(): UriPattern;
}
