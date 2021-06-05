<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

final class UriBuilder extends UriBuilderBase implements UriBuilderWithPath {
  use UriBuilderSetters;
  use UriBuilderGetPath;
}
