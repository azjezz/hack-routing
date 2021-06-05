<?php

namespace HackRouting\UriPattern;

/**
 * @psalm-require-extends UriBuilderBase
 */
interface UriBuilderWithPath {
  public function getPath(): string;
}
