<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

/**
 * @psalm-require-extends RequestParametersBase
 */
trait RequestParameterGetters
{
    final public function getString(string $name): string
    {
        /** @var string */
        return $this->getSimpleTyped(StringRequestParameter::class, $name);
    }

    final public function getOptionalString(string $name): ?string
    {
        /** @var string|null */
        return $this->getSimpleTypedOptional(StringRequestParameter::class, $name);
    }

    final public function getInt(string $name): int
    {
        /** @var int */
        return $this->getSimpleTyped(IntRequestParameter::class, $name);
    }

    final public function getOptionalInt(string $name): ?int
    {
        /** @var int|null */
        return $this->getSimpleTypedOptional(IntRequestParameter::class, $name);
    }

    /**
     * @return non-empty-string
     */
    final public function getEnum(string $name): string
    {
        $spec = $this->getRequiredSpec(EnumRequestParameter::class, $name);
        $value = $this->values[$name];

        return $spec->assert($value);
    }

    /**
     * @return null|non-empty-string
     */
    final public function getOptionalEnum(string $name): ?string
    {
        $spec = $this->getOptionalSpec(EnumRequestParameter::class, $name);
        $value = $this->values[$name] ?? null;
        if (null === $value) {
            return null;
        }

        return $spec->assert($value);
    }
}
