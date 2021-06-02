<?php

declare(strict_types=1);

namespace HackRouting;

/**
 * @template-covariant TResponder
 */
interface IResolver
{
    /**
     * @param non-empty-string $method
     *
     * @return array{0: TResponder, array<string, string>}
     * 
     * @throws \HackRouting\HttpException\NotFoundException
     */
    public function resolve(string $method, string $path): array;
}
