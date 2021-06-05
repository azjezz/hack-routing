<?php

declare(strict_types=1);

namespace HackRouting\Tests\Fixture;

use HackRouting\BaseRouter;
use HackRouting\Cache\CacheInterface;
use HackRouting\IResolver;

/**
 * @template TResponder
 *
 * @extends BaseRouter<TResponder>
 */
final class TestRouter extends BaseRouter
{
    /**
     * @param array<non-empty-string, array<string, TResponder>> $routes
     * @param ?IResolver $resolver
     */
    public function __construct(
        private array $routes,
        private ?IResolver $resolver = null,
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
     * @param IResolver<TResponder>
     */
    public function setResolver(IResolver $resolver): TestRouter
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @return IResolver<TResponder>
     */
    public function getResolver(): IResolver
    {
        return $this->resolver ?? parent::getResolver();
    }
}
