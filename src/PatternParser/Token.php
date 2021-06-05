<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl\Str;
use Psl\Iter;

final class Token
{
    public const TYPE_STRING = 'string';
    public const TYPE_COLON = ':';
    public const TYPE_OPEN_BRACE = '{';
    public const TYPE_CLOSE_BRACE = '}';
    public const TYPE_OPEN_BRACKET = '[';
    public const TYPE_CLOSE_BRACKET = ']';

    private const TYPES = [self::TYPE_STRING, self::TYPE_COLON, self::TYPE_OPEN_BRACE, self::TYPE_CLOSE_BRACE, self::TYPE_OPEN_BRACKET, self::TYPE_CLOSE_BRACKET];

    /**
     * @param Token::TYPE_* $type
     */
    public function __construct(private string $type, private string $value)
    {
    }

    /**
     * @psalm-assert-if-true Token::TYPE_* $type
     */
    public static function isValidType(string $type): bool
    {
        return Iter\contains(self::TYPES, $type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return Str\format('"%s" (%s)', $this->value, $this->type);
    }
}
