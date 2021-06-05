<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

use HackRouting\Parameter\IntRequestParameter;
use HackRouting\Parameter\StringRequestParameter;

/**
 * @psalm-require-extends UriBuilderBase
 */
trait UriBuilderSetters
{
    final public function setString(string $name, string $value): static
    {
        return $this->setValue(StringRequestParameter::class, $name, $value);
    }

    final public function setInt(string $name, int $value): static
    {
        return $this->setValue(IntRequestParameter::class, $name, $value);
    }

    // final public function setEnum<T>(
  //   /* HH_FIXME[2053] */ classname<\HH\BuiltinEnum<T>> $class,
  //   string $name,
  //   T $value,
  // ): this {
  //   $spec = $this->parameters[$name] ?? null;
  //   if ($spec && $spec is EnumRequestParameter<_>) {
  //     // Null case is handled by standard checks in setValue()
  //     $expected_class = $spec->getEnumName();
  //     invariant(
  //       $class === $expected_class,
  //       'Parameter "%s" is a %s, not a %s',
  //       $name,
  //       $expected_class,
  //       $class,
  //     );
  //   }
  //   return $this->setValue(
  //     EnumRequestParameter::class,
  //     $name,
  //     $class::assert($value),
  //   );
  // }
}
