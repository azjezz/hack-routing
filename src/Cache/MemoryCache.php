<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;

/**
 * @template TResponder
 *
 * @implements CacheInterface<TResponder>
 */
final class MemoryCache implements CacheInterface
{
    /**
     * @var array<string, array<non-empty-string, PrefixMap<TResponder>>>
     */
    private array $parse_cache = [];

    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $callback
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function get(string $item, callable $callback): array
    {
        return $this->parse_cache[$item] ?? ($this->parse_cache[$item] = $callback());
    }
}
