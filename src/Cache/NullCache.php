<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;

/**
 * @template TResponder
 *
 * @implements CacheInterface<TResponder>
 */
final class NullCache implements CacheInterface
{
    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $parser
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function parsing(callable $parser): array
    {
        return $parser();
    }
}
