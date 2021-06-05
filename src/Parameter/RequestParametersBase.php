<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use Psl;
use Psl\Dict;
use Psl\Iter;
use Psl\Type;

abstract class RequestParametersBase
{
    /**
     * @var array<string, RequestParameter> $requiredSpecs
     */
    private array $requiredSpecs;

    /**
     * @var array<string, RequestParameter> $optionalSpecs
     */
    private array $optionalSpecs;

    /**
     * @var array<string, string> $values
     */
    protected array $values;

    /**
     * @param iterable<RequestParameter> $required_specs
     * @param iterable<RequestParameter> $optional_specs
     * @param iterable<string, string> $values
     */
    public function __construct(
        iterable $required_specs,
        iterable $optional_specs,
        iterable $values,
    ) {
        $this->values = Dict\from_iterable($values);

        $spec_vector_to_map =
            /**
             * @param iterable<RequestParameter> $specs
             *
             * @return array<string, RequestParameter>
             */
            static fn (iterable $specs) => Dict\pull(
                $specs,
                static fn (RequestParameter $it): RequestParameter => $it,
                static fn (RequestParameter $it): string => $it->getName()
            );

        $this->requiredSpecs = $spec_vector_to_map($required_specs);
        $this->optionalSpecs = $spec_vector_to_map($optional_specs);
    }

    /**
     * @template T of RequestParameter
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    final protected function getRequiredSpec(string $class, string $name): object
    {
        Psl\invariant(
            Iter\contains_key($this->requiredSpecs, $name),
            '%s is not a required parameter',
            $name,
        );

        return self::getSpec($this->requiredSpecs, $class, $name);
    }

    /**
     * @template T of RequestParameter
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    final protected function getOptionalSpec(string $class, string $name): object
    {
        Psl\invariant(
            Iter\contains_key($this->optionalSpecs, $name),
            '%s is not an optional parameter',
            $name,
        );

        return self::getSpec($this->optionalSpecs, $class, $name);
    }

    /**
     * @template T of RequestParameter
     *
     * @param array<string, RequestParameter> $specs
     * @param class-string<T> $class
     *
     * @return T
     */
    private static function getSpec(array $specs, string $class, string $name): object
    {
        $spec = $specs[$name] ?? null;
        Psl\invariant($spec instanceof $class, 'Expected %s to be a %s, got %s', $name, $class, $spec::class);

        return $spec;
    }

    /**
     * @template T
     *
     * @param class-string<RequestParameter&TypedRequestParameter<T>> $class
     *
     * @return T
     */
    final protected function getSimpleTyped(string $class, string $name): mixed
    {
        $spec = $this->getRequiredSpec($class, $name);
        $value = $this->values[$name];

        return $spec->assert($value);
    }

    /**
     * @template T
     *
     * @param class-string<RequestParameter&TypedRequestParameter<T>> $class
     *
     * @return T|null
     */
    final protected function getSimpleTypedOptional(string $class, string $name): mixed
    {
        $spec = $this->getOptionalSpec($class, $name);
        if (!Iter\contains_key($this->values, $name)) {
            return null;
        }

        $value = $this->values[$name];
        return $spec->assert($value);
    }
}
