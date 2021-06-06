<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use Psl;
use HackRouting\PrefixMatching\PrefixMap;
use Psl\SecureRandom;

/**
 * @template TResponder
 *
 * @implements CacheInterface<TResponder>
 */
final class ApcuCache implements CacheInterface
{
    private string $identifier;

    public function __construct()
    {
        Psl\invariant(function_exists('apcu_fetch'), 'APCU extension is required to use "%s".', __CLASS__);

        $this->identifier = SecureRandom\string(8);
    }

    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $parser
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function parsing(callable $parser): array
    {
        $item = '/hack-routing/' . $this->identifier . '/parsing';
        /** @var false|array<non-empty-string, PrefixMap<TResponder>> $result */
        $result = apcu_fetch($item, $success);
        if ($success && false !== $result) {
            return $result;
        }

        $result = $parser();
        apcu_add($item, $result);

        return $result;
    }
}
