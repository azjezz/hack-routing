<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;

/**
 * @template TResponder
 */
interface CacheInterface
{
    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $callback
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function get(string $item, callable $callback): array;
}
