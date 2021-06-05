<?php

namespace HackRouting;

use Psl\{Dict, Iter, Vec};
use HackRouting\Cache\CacheInterface;
use HackRouting\Cache\NullCache;
use HackRouting\HttpException\MethodNotAllowedException;
use HackRouting\HttpException\NotFoundException;

use function rawurldecode;

/**
 * @template-covariant TResponder
 */
abstract class BaseRouter
{
    private CacheInterface $cache;

    /**
     * @var ?IResolver<TResponder>
     */
    private ?IResolver $resolver = null;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new NullCache();
    }

    /**
     * @return array<non-empty-string, array<string, TResponder>>
     */
    abstract protected function getRoutes(): array;

    /**
     * @param non-empty-string $method
     *
     * @return array{0: TResponder, 1: array<string, string>}
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */
    final public function routeMethodAndPath(string $method, string $path): array
    {
        $resolver = $this->getResolver();
        try {
            [$responder, $data] = $resolver->resolve($method, $path);
            $data = Dict\map($data, static fn(string $value): string => rawurldecode($value));
            return [$responder, $data];
        } catch (NotFoundException $e) {
            $allowed = $this->getAllowedMethods($path);
            if (Iter\is_empty($allowed)) {
                throw $e;
            }

            if ($method === HttpMethod::HEAD && $allowed === [HttpMethod::GET]) {
                [$responder, $data] = $resolver->resolve(HttpMethod::GET, $path);
                $data = Dict\map($data, static fn(string $value): string => rawurldecode($value));
                return [$responder, $data];
            }

            throw new MethodNotAllowedException($allowed);
        }
    }

    /**
     * Return the list of HTTP Methods that are allowed for the given path.
     *
     * @return list<non-empty-string>
     */
    private function getAllowedMethods(string $path): array
    {
        $resolver = $this->getResolver();
        $allowed = [];
        foreach (Vec\keys($this->getRoutes()) as $method) {
            try {
                $resolver->resolve($method, $path);

                $allowed[] = $method;
            } catch (NotFoundException) {
                continue;
            }
        }

        return $allowed;
    }

    /**
     * @return IResolver<TResponder>
     */
    public function getResolver(): IResolver
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        $routes = $this->cache->fetch(__FILE__, function(): array {
            return Dict\map(
                $this->getRoutes(),
                /**
                 * @param array<string, TResponder> $method_routes
                 */
                static fn(array $method_routes): PrefixMatching\PrefixMap => PrefixMatching\PrefixMap::fromFlatMap($method_routes),
            );
        });

        $this->resolver = new PrefixMatchingResolver($routes);
        return $this->resolver;
    }
}
