<?php

namespace HackRouting;

use HackRouting\Cache\CacheInterface;
use HackRouting\Cache\NullCache;
use HackRouting\HttpException\InternalServerErrorException;
use HackRouting\HttpException\MethodNotAllowedException;
use HackRouting\HttpException\NotFoundException;
use HackRouting\PrefixMatching\PrefixMap;
use Throwable;

use function array_keys;
use function array_map;
use function strtoupper;
use function rawurldecode;

/**
 * @template-covariant TResponder
 */
abstract class AbstractRouter
{
    private CacheInterface $cache;

    /**
     * @var ?Resolver\ResolverInterface<TResponder>
     */
    private ?Resolver\ResolverInterface $resolver = null;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new NullCache();
    }

    /**
     * @return array<non-empty-string, array<non-empty-string, TResponder>>
     */
    abstract protected function getRoutes(): array;

    /**
     * @param non-empty-string $method
     * @param non-empty-string $path
     *
     * @return array{0: TResponder, 1: array<string, string>}
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @throws InternalServerErrorException
     */
    final public function match(string $method, string $path): array
    {
        /** @var non-empty-string $method */
        $method = strtoupper($method);
        $resolver = $this->getResolver();
        try {
            [$responder, $data] = $resolver->resolve($method, $path);
            $data = array_map(static fn(string $value): string => rawurldecode($value), $data);
            return [$responder, $data];
        } catch (NotFoundException $e) {
            $allowed = $this->getAllowedMethods($path);
            if (null === $allowed) {
                throw $e;
            }

            if ($method === HttpMethod::HEAD && $allowed === [HttpMethod::GET]) {
                [$responder, $data] = $resolver->resolve(HttpMethod::GET, $path);
                $data = array_map(static fn(string $value): string => rawurldecode($value), $data);
                return [$responder, $data];
            }

            throw new MethodNotAllowedException($allowed);
        } catch (Throwable $throwable) {
            if ($throwable instanceof MethodNotAllowedException || $throwable instanceof NotFoundException) {
                throw $throwable;
            }

            throw new InternalServerErrorException(
                'An Error accrued while resolving route.',
                (int)$throwable->getCode(),
                $throwable
            );
        }
    }

    /**
     * Return the list of HTTP Methods that are allowed for the given path.
     *
     * @param non-empty-string $path
     *
     * @return null|non-empty-list<non-empty-string>
     */
    private function getAllowedMethods(string $path): ?array
    {
        $resolver = $this->getResolver();
        $allowed = [];
        foreach (array_keys($this->getRoutes()) as $method) {
            try {
                $resolver->resolve($method, $path);

                $allowed[] = $method;
            } catch (NotFoundException) {
                continue;
            }
        }

        return $allowed === [] ? null : $allowed;
    }

    /**
     * @return Resolver\ResolverInterface<TResponder>
     */
    public function getResolver(): Resolver\ResolverInterface
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        $routes = $this->cache->get(__FILE__, function (): array {
            return array_map(
                static fn(array $method_routes): PrefixMap => PrefixMap::fromFlatMap($method_routes),
                $this->getRoutes(),
            );
        });

        return $this->resolver = new Resolver\PrefixMatchingResolver($routes);
    }
}
