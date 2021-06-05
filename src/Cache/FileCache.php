<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;
use Psl;
use Psl\Env;
use Psl\Filesystem;

final class FileCache implements CacheInterface
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        Psl\invariant(
            null === $directory || Filesystem\is_writable($directory),
            'Cache directory "%s" must be writable.',
            (string) $directory
        );

        $this->directory = $directory ?? (Env\temp_dir() . '/hack_routing');
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
        $file = $this->directory . '/' . md5($item) . '.php';
        if (Filesystem\exists($file)) {
            /**
             * @psalm-suppress UnresolvableInclude
             * @var array<non-empty-string, PrefixMap<T>> $result
             */
            $result = require $file;
        } else {
            $result = $factory();
            Filesystem\write_file($file, '<?php return unserialize("' . serialize($result) . '");');
        }

        return $result;
    }
}
