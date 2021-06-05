<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

interface HasRouteFragment
{
    public function getRouteFragment(): string;
}
