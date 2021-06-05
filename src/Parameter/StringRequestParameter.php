<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use Psl;
use Psl\Str\Byte as Str;

/**
 * @extends TypedUriParameter<string>
 */
final class StringRequestParameter extends TypedUriParameter
{
    public function __construct(
        private bool $allow_slashes,
        string $name,
    ) {
        parent::__construct($name);
    }

    public function assert(string $input): string
    {
        if (!$this->allow_slashes) {
            Psl\invariant(!Str\contains($input, '/'), 'Parameter %s contains slashes', $this->getName());
        }

        return $input;
    }

    /**
     * @return null|non-empty-string
     */
    public function getRegExpFragment(): ?string
    {
        if (!$this->allow_slashes) {
            return null;
        }

        return '.+';
    }
}
