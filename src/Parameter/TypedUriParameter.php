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
     * @param T $input
     */
    public function getUriFragment(mixed $input): string
    {
        return (string) $input;
    }
}
