<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;

final class MemoryCache implements CacheInterface
{
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
        return $this->cache[$item] ?? ($this->cache[$item] = $factory());
    }
}
