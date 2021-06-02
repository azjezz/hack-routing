<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl\{Str, Vec};

/**
 * @return list<Token>
 */
function tokenize(string $pattern): array {
  $tokens = [];
  $buffer = '';
  foreach (Str\split($pattern, '') as $byte) {
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

  return Vec\filter($tokens, static fn(Token $t): bool => !($t->getType() === Token::TYPE_STRING && $t->getValue() === ''));
}
