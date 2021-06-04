<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;
use Psl\Env;
use Psl\Filesystem;

final class FileCache implements CacheInterface
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
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
            $result = require $file;
        } else {
            $result = $factory();
            Filesystem\write_file($file, "<?php return " . var_export($result, true) . ';');
        }

        return $result;
    }
}
