<?php

declare(strict_types=1);

namespace HackRouting;

use Psl\{Dict, Iter, Type};
use HackRouting\HttpException\NotFoundException;

/**
 * @template-covariant TResponder
 *
 * @implements IResolver<TResponder>
 */
final class SimpleRegexpResolver implements IResolver
{
    /**
     * @var array<non-empty-string, array<string, TResponder>>
     */
    private array $map;

    /**
     * @param array<non-empty-string, array<string, TResponder>> $map
     */
    public function __construct(array $map)
    {
        $this->map = Dict\map(
            $map,
            fn(array $routes) => Dict\map_keys(
                $routes,
                fn($route) => self::fastRouteToRegexp($route),
            ),
        );
    }

    /**
     * @param non-empty-string $method
     *
     * @return array{0: TResponder, array<string, string>}
     *
     * @throws NotFoundException
     */
    public function resolve(string $method, string $path): array
    {
        if (!Iter\contains_key($this->map, $method)) {
            throw new NotFoundException();
        }

        $map = $this->map[$method];
        foreach ($map as $regexp => $responder) {
            if (preg_match($regexp, $path, $matches) !== 1) {
                continue;
            }

            $parameters = Dict\filter_keys($matches, fn($key) => Type\string()->matches($key));
            $parameters = Type\dict(Type\string(), Type\string())->coerce($parameters);

            return [$responder, $parameters];
        }

        throw new NotFoundException();
    }

    private static function fastRouteToRegexp(string|int $route): string
    {
        $pattern = PatternParser\Parser::parse((string) $route);

        return '#^' . $pattern->asRegexp('#') . '$#';
    }
}