<?php

declare(strict_types=1);

namespace HackRouting\HttpException;

use Exception;

final class MethodNotAllowedException extends HttpException
{
    /**
     * @param list<string> $allowed
     */
    public function __construct(
        protected array $allowed,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowed;
    }
}
