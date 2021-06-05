<?php

declare(strict_types=1);

namespace HackRouting\Tests;

use HackRouting\Parameter\IntRequestParameter;
use HackRouting\Parameter\RequestParameters;
use HackRouting\Parameter\StringRequestParameter;
use HackRouting\UriPattern\UriPattern;
use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;

final class RequestParametersTest extends TestCase
{
    public function testStringParam(): void
    {
        $parts = [new StringRequestParameter(false, 'foo')];

        $data = ['foo' => 'bar'];

        self::assertSame('bar', (new RequestParameters($parts, [], $data))->getString('foo'));
    }

    public function testIntParam(): void
    {
        $parts = [new IntRequestParameter('foo')];

        $data = ['foo' => '123'];

        self::assertSame(123, (new RequestParameters($parts, [], $data))->getInt('foo'));
    }

    public function testFetchingStringAsInt(): void
    {
        $parts = [new StringRequestParameter(false, 'foo')];

        $data = ['foo' => 'bar'];

        $this->expectException(InvariantViolationException::class);

        (new RequestParameters($parts, [], $data))->getInt('foo');
    }

    public function testFromPattern(): void
    {
        $parts = (new UriPattern())
            ->literal('/')
            ->string('foo')
            ->literal('/')
            ->int('bar')
            ->literal('/')
            ->getParameters();

        $data = ['foo' => 'some string',
            'bar' => '123',
        ];

        $params = new RequestParameters($parts, [], $data);

        self::assertSame('some string', $params->getString('foo'));
        self::assertSame(123, $params->getInt('bar'));
    }

    public function testGetOptional(): void
    {
        $params = new RequestParameters(
            [],
            [new StringRequestParameter(false, 'foo')],
            ['foo' => 'bar'],
        );

        self::assertSame('bar', $params->getOptionalString('foo'));
    }

    public function testGetMissingOptional(): void
    {
        $params = new RequestParameters(
            [],
            [new StringRequestParameter(
                false,
                'foo',
            )],
            [],
        );

        self::assertNull($params->getOptionalString('foo'));
    }

    public function testGetOptionalAsRequired(): void
    {
        $params = new RequestParameters(
            [],
            [new StringRequestParameter(
                false,
                'foo',
            )],
            ['foo' => 'bar'],
        );

        $this->expectException(InvariantViolationException::class);

        $params->getString('foo');
    }

    public function testGetRequiredAsOptional(): void
    {
        $params = new RequestParameters(
            [new StringRequestParameter(false, 'foo')],
            [],
            ['foo' => 'bar'],
        );

        $this->expectException(InvariantViolationException::class);

        $params->getOptionalString('foo');
    }
}
