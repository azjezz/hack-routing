<?php

declare(strict_types=1);

namespace HackRouting\Tests;

use HackRouting\UriPattern\UriBuilder;
use HackRouting\UriPattern\UriPattern;
use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;

final class UriBuilderTest extends TestCase
{
    public function testLiteral(): void
    {
        $parts = (new UriPattern())
            ->literal('/foo')
            ->getParts();

        self::assertSame('/foo', (new UriBuilder($parts))->getPath());
    }

    public function testStringParameter(): void
    {
        $parts = (new UriPattern())
            ->literal('/herp/')
            ->string('foo')
            ->getParts();

        $path = (new UriBuilder($parts))
            ->setString('foo', 'derp')
            ->getPath();

        self::assertSame('/herp/derp', $path);
    }

    public function testParameterAsFirstPart(): void
    {
        $parts = (new UriPattern())
            ->string('herp')
            ->getParts();

        $path = (new UriBuilder($parts))
            ->setString('herp', 'derp')
            ->getPath();

        self::assertSame('/derp', $path);
    }

    public function testIntParameter(): void
    {
        $parts = (new UriPattern())
            ->literal('/post/')
            ->int('post_id')
            ->getParts();

        $path = (new UriBuilder($parts))
            ->setInt('post_id', 123)
            ->getPath();

        self::assertSame('/post/123', $path);
    }

    public function testIntAsString(): void
    {
        $parts = (new UriPattern())->int('foo')->getParts();
        $this->expectException(InvariantViolationException::class);
        (new UriBuilder($parts))->setString('foo', 'bar');
    }

    public function testSetTwice(): void
    {
        $parts = (new UriPattern())->int('foo')->getParts();

        $this->expectException(InvariantViolationException::class);

        (new UriBuilder($parts))
            ->setInt('foo', 123)
            ->setInt('foo', 123);
    }

    public function testMissingValue(): void
    {
        $parts = (new UriPattern())->int('foo')->getParts();
        $this->expectException(InvariantViolationException::class);

        (new UriBuilder($parts))->getPath();
    }

    public function testSetInvalidParameter(): void
    {
        $parts = (new UriPattern())->int('foo')->getParts();
        $this->expectException(InvariantViolationException::class);

        (new UriBuilder($parts))->setInt('bar', 123);
    }
}
