<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use Psl;
use Psl\Iter;
use Psl\Str;
use Psl\Vec;

use function preg_quote;

/**
 * @extends TypedUriParameter<non-empty-string>
 */
final class EnumRequestParameter extends TypedUriParameter
{
    /**
     * @param non-empty-list<non-empty-string> $enum_values
     */
    public function __construct(string $name, private array $enum_values)
    {
        parent::__construct($name);
    }

    /**
     * @return non-empty-list<string>
     */
    public function getEnumValues(): array
    {
        return $this->enum_values;
    }

    /**
     * @param non-empty-string $input
     *
     * @return non-empty-string
     */
    public function getUriFragment(mixed $input): string
    {
        return $this->assert((string) $input);
    }

    /**
     * @return non-empty-string
     */
    public function assert(string $input): string
    {
        Psl\invariant(
            Iter\contains($this->enum_values, $input),
            'Invalid Enum value "%s" given, expected one of: "%s".',
            $input,
            Str\join($this->enum_values, '", "'),
        );

        /** @var non-empty-string */
        return $input;
    }

    /**
     * @return non-empty-string
     */
    public function getRegExpFragment(): string
    {
        $sub_fragments = Vec\map($this->enum_values, fn(string $value): string => preg_quote($value));

        return '(?:' . Str\join($sub_fragments, '|') . ')';
    }
}
