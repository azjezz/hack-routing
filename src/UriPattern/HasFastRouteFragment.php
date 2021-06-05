<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

interface HasFastRouteFragment
{
    public function getFastRouteFragment(): string;
}
