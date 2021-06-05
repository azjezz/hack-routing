<?php

declare(strict_types=1);

namespace HackRouting\Tests;

use HackRouting\Parameter\IntRequestParameter;
use HackRouting\Parameter\StringRequestParameter;
use HackRouting\UriPattern\UriPattern;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\CoercionException;

final class UriPatternTest extends TestCase
{
    public function testLiteral(): void
    {
        $pattern = (new UriPattern())
            ->literal('/foo')
            ->getFastRouteFragment();

        self::assertSame('/foo', $pattern);
    }

    public function testMultipleLiterals(): void
    {
        $pattern = (new UriPattern())
            ->literal('/foo')
            ->literal('/bar')
            ->getFastRouteFragment();

        self::assertSame('/foo/bar', $pattern);
    }

    public function testStringParamFragment(): void
    {
        $pattern = (new UriPattern())
            ->literal('/~')
            ->string('username')
            ->getFastRouteFragment();

        self::assertSame('/~{username}', $pattern);
    }

    public function testStringWithSlashesParamFragment(): void
    {
        $pattern = (new UriPattern())
            ->literal('/~')
            ->stringWithSlashes('username')
            ->getFastRouteFragment();

        self::assertSame('/~{username:.+}', $pattern);
    }

    public function testStringParamAssertSucceeds(): void
    {
        self::assertSame('foo', (new StringRequestParameter(false, 'foo'))->assert('foo'));
    }

    public function testIntParamFragment(): void
    {
        $pattern = (new UriPattern())
            ->literal('/blog/')
            ->int('post_id')
            ->getFastRouteFragment();

        self::assertSame('/blog/{post_id:\d+}', $pattern);
    }

    public function testIntParamAssertSucceeds(): void
    {
        self::assertSame(123, (new IntRequestParameter('foo'))->assert('123'));
    }

    /**
     * @return list<list<string>>
     */
    public function exampleInvalidInts(): array
    {
        return [['foo'], ['0123foo'], ['0.123foo'], ['0.123'], ['0x1e3']];
    }

    /**
     * @dataProvider exampleInvalidInts
     */
    public function testIntParamAssertThrows(string $input): void
    {
        $this->expectException(CoercionException::class);
        
        (new IntRequestParameter('foo'))->assert($input);
    }
}
