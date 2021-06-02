<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

final class OptionalNode implements Node {
  public function __construct(private PatternNode $pattern) {
  }

  public function getPattern(): PatternNode {
    return $this->pattern;
  }

  public function toStringForDebug(): string {
    return '?'.$this->pattern->toStringForDebug();
  }

  public function asRegexp(string $delimiter): string {
    return '(?:'.$this->pattern->asRegexp($delimiter).')?';
  }
}
