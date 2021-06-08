<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use function array_filter;
use function str_split;

/**
 * @return array<int, Token>
 */
function tokenize(string $pattern): array
{
    $tokens = [];
    $buffer = '';
    foreach (str_split($pattern) as $byte) {
        if (Token::isValidType($byte)) {
            $tokens[] = new Token(Token::TYPE_STRING, $buffer);
            $buffer = '';
            $tokens[] = new Token($byte, $byte);
        } else {
            $buffer .= $byte;
        }
    }

    if ($buffer !== '') {
        $tokens[] = new Token(Token::TYPE_STRING, $buffer);
    }

    return array_filter(
        $tokens,
        static fn (Token $t): bool => !($t->getType() === Token::TYPE_STRING && $t->getValue() === '')
    );
}
