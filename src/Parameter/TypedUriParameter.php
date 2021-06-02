<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

use Psl\Type;

/**
 * @template T
 *
 * @implements TypedRequestParameter<T>
 */
abstract class TypedUriParameter extends UriParameter implements TypedRequestParameter
{
    /**
     * @param T $value
     */
    public function getUriFragment(mixed $value): string
    {
        return (string) $value;
    }
}
