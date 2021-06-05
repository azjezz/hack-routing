Hack-Routing
===========

![Unit tests status](https://github.com/azjezz/hack-routing/workflows/unit%20tests/badge.svg)
![Static analysis status](https://github.com/azjezz/hack-routing/workflows/static%20analysis/badge.svg)
![Coding standards status](https://github.com/azjezz/hack-routing/workflows/coding%20standards/badge.svg)
[![Type Coverage](https://shepherd.dev/github/azjezz/hack-routing/coverage.svg)](https://shepherd.dev/github/azjezz/hack-routing)
[![Total Downloads](https://poser.pugx.org/azjezz/hack-routing/d/total.svg)](https://packagist.org/packages/azjezz/hack-routing)
[![Latest Stable Version](https://poser.pugx.org/azjezz/hack-routing/v/stable.svg)](https://packagist.org/packages/azjezz/hack-routing)
[![License](https://poser.pugx.org/azjezz/hack-routing/license.svg)](https://packagist.org/packages/azjezz/hack-routing)

Fast, type-safe request routing, parameter retrieval, and link generation.

It's a port of [hack-router](https://github.com/hhvm/hack-router) By Facebook, Inc.

Components
==========

HTTP Exceptions
---------------

Exception classes representing common situations in HTTP applications:

- `HackRouting\HttpException\InternalServerErrorException`
- `HackRouting\HttpException\MethodNotAllowedException`
- `HackRouting\HttpException\NotFoundException`

BaseRouter
----------

A simple typed request router. Example:

```php
<?php

use Psl\Str;
use HackRouting\AbstractMatcher;
use HackRouting\HttpMethod;

/**
 * @extends BaseRouter<(function(array<string, string>):string)>
 */
final class Matcher extends AbstractMatcher {
  /**
   * @return array<non-empty-string, array<string, (function(array<string, string>):string)>>
   */
  protected function getRoutes(): array {
    return [
      HttpMethod::GET => [
        '/' => static fn(array $parameters): string => 'Hello, World!',
        '/user/{username}/' => static fn(array $parameters): string => Str\format('Hello, %s!', $parameters['username']),
      ],

      HttpMethod::POST => [
        '/' => static fn(arrray $parameters): string => 'Hello, POST world',
      ],
    ];
  }
}
```

Simplified for conciseness - see [`examples/MatcherExample.php`](examples/MatcherExample.php) for full executable
example.

UriPatterns
-----------

Generate route fragments, URIs (for linking), and retrieve URI parameters in a consistent and type-safe way:

```php
<?php

use HackRouting\UriPattern\UriPattern;

final class UserPageController extends WebController {
  public static function getUriPattern(): UriPattern {
    return (new UriPattern())
      ->literal('/users/')
      ->string('user_name');
  }

  // ...
}
```

Parameters can be retrieved, with types checked at runtime both against the values, and the definition:

```php
public function getResponse(): string {
  return 'Hello, '.$this->getUriParameters()->getString('user_name');
}
```

You can also generate links to controllers:

```php
$link = UserPageController::getUriBuilder()
  ->setString('user_name', 'Mr Hankey')
  ->getPath();
```

These examples are simplified for conciseness - see [`examples/UriPatternsExample.php`](examples/UriPatternsExample.php)
for full executable example.

Contributing
============

We welcome GitHub issues and pull requests - please see CONTRIBUTING.md for details.

License
=======

hack-routing is MIT-licensed.
