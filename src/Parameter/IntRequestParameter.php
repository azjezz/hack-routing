<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use Psl\Type;

/**
 * @extends TypedUriParameter<int>
 */
final class IntRequestParameter extends TypedUriParameter  {
  public function assert(string $input): int {
      return Type\int()->assert($input);
  }

  public function getRegExpFragment(): ?string {
    return '\d+';
  }
}
