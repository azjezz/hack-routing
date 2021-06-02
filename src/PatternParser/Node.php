<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

interface Node {
  public function toStringForDebug(): string;
  public function asRegexp(string $delimiter): string;
}
