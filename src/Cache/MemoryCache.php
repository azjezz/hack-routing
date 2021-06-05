<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;

final class MemoryCache implements CacheInterface
{
    /**
     * @var array<string, array<non-empty-string, PrefixMap>>
     */
    private array $cache = [];

    /**
     * @template T
     *
     * @param (callable(): array<non-empty-string, PrefixMap<T>>) $factory
     *
     * @return array<non-empty-string, PrefixMap<T>>
     */
    public function fetch(string $item, callable $factory): array
    {
        /** @var array<non-empty-string, PrefixMap<T>> */
        return $this->cache[$item] ?? ($this->cache[$item] = $factory());
    }
}
