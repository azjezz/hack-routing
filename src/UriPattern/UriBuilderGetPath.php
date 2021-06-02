<?php

namespace HackRouting\UriPattern;

/**
 * @require-implements UriBuilderWithPath
 * @require-extends UriBuilderBase
 */
trait UriBuilderGetPath {
  final public function getPath(): string {
    return $this->getPathImpl();
  }
}
