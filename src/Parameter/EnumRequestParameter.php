<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use Psl\{Str, Vec};

/**
 * @template T
 * 
 * @extends TypedUriParameter<T>
 * 
 * @TODO(azjezz): add support for enums.
 */
abstract class EnumRequestParameter  extends TypedUriParameter {
   //public function __construct(
   //  /* HH_FIXME[2053] */
   //  private classname<\HH\BuiltinEnum<T>> $enumClass,
   //  string $name,
   //) {
   //  parent::__construct($name);
   //}

   ///* HH_FIXME[2053] */
   //final public function getEnumName(): classname<\HH\BuiltinEnum<T>> {
   //  return $this->enumClass;
   //}

   //<<__Override>>
   //final public function getUriFragment(T $value): string {
   //  $class = $this->enumClass;
   //  return (string)$class::assert($value);
   //}

   //<<__Override>>
   //public function assert(string $input): T {
   //  $class = $this->enumClass;
   //  return $class::assert($input);
   //}

   //<<__Override>>
   //public function getRegExpFragment(): ?string {
   //  $class = $this->enumClass;
   //  $values = $class::getValues();
   //  $sub_fragments = Vec\map($values, $value ==> \preg_quote((string) $value));
   //  return '(?:'.Str\join($sub_fragments, '|').')';
   //}
}
