<?php

namespace HackRouting\UriPattern;

/**
 * @psalm-require-implements UriBuilderWithPath
 * @psalm-require-extends UriBuilderBase
 */
trait UriBuilderGetPath {
  final public function getPath(): string {
    return $this->getPathImpl();
  }
}
