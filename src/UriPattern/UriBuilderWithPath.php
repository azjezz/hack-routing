<?php

namespace HackRouting\UriPattern;

/**
 * @require-extends UriBuilderBase
 */
interface UriBuilderWithPath {
  public function getPath(): string;
}
