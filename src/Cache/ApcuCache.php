<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use Psl;
use HackRouting\PrefixMatching\PrefixMap;

final class ApcuCache implements CacheInterface
{
    public function __construct()
    {
        Psl\invariant(function_exists('apcu_fetch'), 'APCU extension is required to use "%s".', __CLASS__);
    }

    /**
     * @template T
     *
     * @param (callable(): array<non-empty-string, PrefixMap<T>>) $factory
     *
     * @return array<non-empty-string, PrefixMap<T>>
     */
    public function fetch(string $item, callable $factory): array
    {
        /** @var false|array<non-empty-string, PrefixMap<T>> $result */
        $result = apcu_fetch($item, $success);
        if ($success && false !== $result) {
            return $result;
        }

        $result = $factory();
        apcu_add($item, $result);

        return $result;
    }
}
