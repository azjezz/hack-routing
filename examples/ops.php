<?php

declare(strict_types=1);

namespace HackRouting\Examples\BaseRouterExample;

use HackRouting\HttpMethod;
use HackRouting\PrefixMatchingResolver;

require __DIR__ . '/../vendor/autoload.php';

$resolver = PrefixMatchingResolver::fromFlatMap([
    HttpMethod::GET => [
        '/greet/{username}' => 'GreetingController',
        '/user/settings/passwords' => 'GreetingController',
    ],
]);

$map = $resolver->getMap()[HttpMethod::GET];
