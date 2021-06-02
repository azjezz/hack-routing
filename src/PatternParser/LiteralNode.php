<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl;
use function preg_quote;
use function var_export;

final class LiteralNode implements Node {
  public function __construct(private string $text) {
      Psl\invariant($text !== '', 'No empty literal nodes');
  }

  public function getText(): string {
    return $this->text;
  }

  public function toStringForDebug(): string {
    return var_export($this->getText(), true);
  }

  public function asRegexp(string $delimiter): string {
    return preg_quote($this->getText(), $delimiter);
  }
}
