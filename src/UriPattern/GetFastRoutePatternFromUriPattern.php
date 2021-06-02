<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

/**
 * @require-implements HasUriPattern
 */
trait GetFastRoutePatternFromUriPattern
{
    final public static function getFastRoutePattern(): string
    {
        return static::getUriPattern()->getFastRouteFragment();
    }

    abstract public static function getUriPattern(): UriPattern;
}
