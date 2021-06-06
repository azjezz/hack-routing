<?php

declare(strict_types=1);

namespace HackRouting\Cache;

use HackRouting\PrefixMatching\PrefixMap;
use Psl;
use Psl\Env;
use Psl\Filesystem;
use Psl\SecureRandom;

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

        $this->directory = $directory ?? (Env\temp_dir() . '/hack-routing-' . SecureRandom\string(8));
    }

    /**
     * @param (callable(): array<non-empty-string, PrefixMap<TResponder>>) $parser
     *
     * @return array<non-empty-string, PrefixMap<TResponder>>
     */
    public function parsing(callable $parser): array
    {
        $file = $this->directory . '/parsing.php';
        if (Filesystem\exists($file)) {
            /**
             * @psalm-suppress UnresolvableInclude
             * @var array<non-empty-string, PrefixMap<TResponder>> $result
             */
            $result = require $file;
        } else {
            $result = $parser();
            Filesystem\write_file($file, "<?php return unserialize('" . serialize($result) . "');");
        }

        return $result;
    }
}
