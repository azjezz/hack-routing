Hack-Routing [![Build Status](https://travis-ci.org/hhvm/hack-router.svg?branch=master)](https://travis-ci.org/hhvm/hack-router)
===========

Fast, type-safe request routing, parameter retrieval, and link generation.

It's a port of [hack-router](https://github.com/hhvm/hack-router) By Facebook, Inc.

Components
==========

HTTP Exceptions
---------------

Exception classes representing common situations in HTTP applications:

- `HackRouting\HttpException\InternalServerError`
- `HackRouting\HttpException\MethodNotAllowed`
- `HackRouting\HttpException\NotFoundException`

BaseRouter
----------

A simple typed request router. Example:

```php
<?php

use Psl\Str;
use HackRouting\BaseRouter;
use HackRouting\HttpMethod;

/**
 * TResponder can be whatever you want; in this case, it's a
 * callable, but classname<MyWebControllerBase> is also a
 * common choice.
 *
 * @extends BaseRouter<(function(ImmMap<string, string>):string)>
 */
final class BaseRouterExample extends BaseRouter {
  /**
   * @return array<non-empty-string, array<string, (function(ImmMap<string, string>):string)>>
   */
  protected function getRoutes(): array {
    return [
      HttpMethod::GET => [
        '/' => static fn($parameters): string => 'Hello, World!',
        '/user/{username}/' => static fn($parameters): string => Str\format('Hello, %s!', $parameters['username']),
      ],

      HttpMethod::POST => [
        '/' => static fn($parameters): string => 'Hello, POST world',
      ],
    ];
  }
}
```

Simplified for conciseness - see [`examples/BaseRouterExample.php`](examples/BaseRouterExample.php) for full executable
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
