<?php

declare(strict_types=1);

namespace HackRouting;

use HackRouting\HttpException\InternalServerErrorException;

use function var_export;

class UnknownRouterException extends InternalServerErrorException
{
    /**
     * @param list<mixed> $fastRouteData
     */
    public function __construct(private array $fastRouteData)
    {
        parent::__construct(
            'Unknown FastRoute response: ' . var_export($fastRouteData, true),
        );
    }

    /**
     * @return list<mixed> $fastRouteData
     */
    public function getFastRouteData(): array
    {
        return $this->fastRouteData;
    }
}
