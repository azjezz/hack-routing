<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;
use Psl;
use Psl\Env;
use Psl\Filesystem;

use function md5;
use function file_exists;

/**
 * @template TResponder
 *
 * @implements CacheInterface<TResponder>
 */
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

        $this->directory = $directory ?? Env\temp_dir() . '/hack-routing';
    }

    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $callback
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function get(string $item, callable $callback): array
    {
        $file = $this->directory . '/' . md5($item) . '/prefix-map.php';
        if (file_exists($file)) {
            /**
             * @psalm-suppress UnresolvableInclude
             * @var array<non-empty-string, PrefixMap<TResponder>> $result
             */
            $result = require $file;
        } else {
            $result = $callback();
            Filesystem\write_file($file, "<?php return unserialize('" . serialize($result) . "');");
        }

        return $result;
    }
}
