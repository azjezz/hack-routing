<?php

declare(strict_types=1);

namespace HackRouting\Tests\Fixture;

use HackRouting\AbstractMatcher;
use HackRouting\Cache\CacheInterface;
use HackRouting\IResolver;

/**
 * @template-covariant TResponder
 *
 * @extends BaseRouter<TResponder>
 */
final class TestMatcher extends AbstractMatcher
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
    public function setResolver(IResolver $resolver): TestMatcher
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
