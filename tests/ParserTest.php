<?php

declare(strict_types=1);

namespace HackRouting\Tests;

use HackRouting\PatternParser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public function getExamplePatterns(): array
    {
        return [
            array('/foo', "['/foo']"),
            array('/foo/{bar}', "['/foo/', {bar}]"),
            array('/foo/[{bar}]', "['/foo/', ?[{bar}]]"),
            array("/foo/{bar:\\d+}", "['/foo/', {bar: #\\d+#}]"),
            array('/foo/{bar:[0-9]+}', "['/foo/', {bar: #[0-9]+#}]"),
            array('/foo/{bar:[0-9]{1,3}}', "['/foo/', {bar: #[0-9]{1,3}#}]"),
        ];
    }

    /**
     * @dataProvider getExamplePatterns
     */
    public function testPattern(string $pattern, string $expected): void
    {
        self::assertSame($expected, Parser::parse($pattern)->toStringForDebug());
    }
}
