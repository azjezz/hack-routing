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
     * @var array<non-empty-string, PrefixMap<TResponder>>
     */
    private ?array $parse_cache = null;

    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $parser
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function parsing(callable $parser): array
    {
        if (null === $this->parse_cache) {
            $this->parse_cache = $parser();
        }

        return $this->parse_cache;
    }
}
