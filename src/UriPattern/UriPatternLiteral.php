<?php

namespace HackRouting\UriPattern;

use Psl;
use Psl\Str;

final class UriPatternLiteral implements UriPatternPart {
  public function __construct(private string $value) {
  }

  public function getFastRouteFragment(): string {
    $value = $this->value;
    // No escaping required :)
    Psl\invariant(
      !Str\contains($value, '{'),
      '{ is not valid in a URI - see nikic/FastRoute#6',
    );

    return $value;
  }

  public function getValue(): string {
    return $this->value;
  }
}
