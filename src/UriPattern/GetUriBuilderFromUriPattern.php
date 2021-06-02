<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

/**
 * @require-implements HasUriPattern
 */
trait GetUriBuilderFromUriPattern
{
    final public static function getUriBuilder(): UriBuilder
    {
        return (new UriBuilder(static::getUriPattern()->getParts()));
    }

    abstract public static function getUriPattern(): UriPattern;
}
