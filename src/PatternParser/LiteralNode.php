<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl;

use function preg_quote;
use function var_export;

final class LiteralNode implements Node
{
    /**
     * @var non-empty-string
     */
    private string $text;

    /**
     * @psalm-assert non-empty-string $text
     */
    public function __construct(string $text)
    {
        Psl\invariant($text !== '', 'No empty literal nodes');

        $this->text = $text;
    }

    /**
     * @return non-empty-string
     */
    public function getText(): string
    {
        return $this->text;
    }

    public function toStringForDebug(): string
    {
        return var_export($this->getText(), true);
    }

    public function asRegexp(string $delimiter): string
    {
        return preg_quote($this->getText(), $delimiter);
    }

    /**
     * @return array{text: string}
     *
     * @internal
     */
    public function __serialize(): array
    {
        return ['text' => $this->text];
    }

    /**
     * @param array{text: string} $data
     *
     * @internal
     */
    public function __unserialize(array $data): void
    {
        ['text' => $this->text] = $data;
    }
}
