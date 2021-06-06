<?php

declare(strict_types=1);

namespace HackRouting;

use Psl\Str;

/**
 * @template TResponder
 *
 * @extends AbstractRouter<TResponder>
 */
final class Router extends AbstractRouter
{
    /**
     * @var array<non-empty-string, array<non-empty-string, TResponder>>
     */
    private array $routes = [];

    /**
     * @return array<non-empty-string, array<non-empty-string, TResponder>>
     */
    protected function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Add a route to this router's internal collection.
     *
     * @param non-empty-string  $method     The HTTP Method.
     * @param non-empty-string  $route      The Routes Path.
     * @param TResponder        $responder  The Responder.
     */
    public function route(string $method, string $route, mixed $responder): Router
    {
        $this->routes[Str\uppercase($method)][$route] = $responder;

        return $this;
    }
}
