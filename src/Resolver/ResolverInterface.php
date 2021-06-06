<?php

declare(strict_types=1);

namespace HackRouting\Resolver;

use HackRouting\HttpException\NotFoundException;

/**
 * @template-covariant TResponder
 */
interface ResolverInterface
{
    /**
     * @param non-empty-string $method
     * @param non-empty-string $path
     *
     * @return array{0: TResponder, 1: array<string, string>}
     *
     * @throws NotFoundException
     */
    public function resolve(string $method, string $path): array;
}
