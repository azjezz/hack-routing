<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use Psl;
use HackRouting\PrefixMatching\PrefixMap;

/**
 * @template TResponder
 *
 * @implements CacheInterface<TResponder>
 */
final class ApcuCache implements CacheInterface
{
    public function __construct()
    {
        Psl\invariant(function_exists('apcu_fetch'), 'APCU extension is required to use "%s".', __CLASS__);
    }

    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $callback
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function get(string $item, callable $callback): array
    {
        /** @var false|array<non-empty-string, PrefixMap<TResponder>> $result */
        $result = apcu_fetch($item, $success);
        if ($success && false !== $result) {
            return $result;
        }

        $result = $callback();
        apcu_add($item, $result);

        return $result;
    }
}
