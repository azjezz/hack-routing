<?php

declare(strict_types=1);

namespace HackRouting\Parameter;

/**
 * @template T
 *
 * @psalm-require-extends RequestParameter
 */
interface TypedRequestParameter
{
    /**
     * @return T
     */
    public function assert(string $input): mixed;
}
