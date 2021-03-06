<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

interface HasUriPattern
{
    public static function getUriPattern(): UriPattern;
}
