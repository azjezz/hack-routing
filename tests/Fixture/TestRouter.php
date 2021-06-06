<?php

declare(strict_types=1);

namespace HackRouting\Tests\Fixture;

use HackRouting\AbstractRouter;
use HackRouting\Cache\CacheInterface;
use HackRouting\Resolver\ResolverInterface;

/**
 * @template-covariant TResponder
 *
 * @extends BaseRouter<TResponder>
 */
final class TestRouter extends AbstractRouter
{
    /**
     * @param array<non-empty-string, array<string, TResponder>> $routes
     * @param ?ResolverInterface $resolver
     */
    public function __construct(
        private array $routes,
        private ?ResolverInterface $resolver = null,
        ?CacheInterface $cache = null,
    ) {
        parent::__construct($cache);
    }

    /**
     * @return array<non-empty-string, array<string, TResponder>>
     */
    protected function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param ResolverInterface<TResponder>
     */
    public function setResolver(ResolverInterface $resolver): TestRouter
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @return ResolverInterface<TResponder>
     */
    public function getResolver(): ResolverInterface
    {
        return $this->resolver ?? parent::getResolver();
    }
}
