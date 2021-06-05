<?php

declare(strict_types=1);

namespace HackRouting\Tests;

use HackRouting\AbstractMatcher;
use HackRouting\HttpException\MethodNotAllowedException;
use HackRouting\HttpException\NotFoundException;
use HackRouting\HttpMethod;
use HackRouting\IResolver;
use HackRouting\PrefixMatchingResolver;
use HackRouting\SimpleRegexpResolver;
use HackRouting\Tests\Fixture\TestMatcher;
use PHPUnit\Framework\TestCase;
use Psl\Dict;

final class MatcherTest extends TestCase
{
    /**
     * @var list<non-empty-string>
     */
    private const MAP = [
        '/foo',
        '/foo/',
        '/foo/bar',
        '/foo/bar/{baz}',
        '/foo/{bar}',
        '/foo/{bar}/baz',
        '/foo/{bar}{baz:.+}',
        '/food/{noms}',
        '/bar/{herp:\\d+}',
        '/bar/{herp}',
        '/unique/{foo}/bar',
        '/optional_suffix_[foo]',
        '/optional_suffix[/]',
        '/optional_suffixes/[herp[/derp]]',
        '/manual/en/{LegacyID}.php',
    ];

    /**
     * @return list<array{string, string, array<string, string>}>
     */
    public function expectedMatches(): array
    {
        return [
            array('/foo', '/foo', []),
            array('/foo/', '/foo/', []),
            array('/foo/bar', '/foo/bar', []),
            array('/foo/bar/herp', '/foo/bar/{baz}', ['baz' => 'herp']),
            array('/foo/herp', '/foo/{bar}', ['bar' => 'herp']),
            array('/foo/=%3Efoo', '/foo/{bar}', ['bar' => '=>foo']),
            array('/foo/herp/baz', '/foo/{bar}/baz', ['bar' => 'herp']),
            array(
                '/foo/herp/derp',
                '/foo/{bar}{baz:.+}',
                ['bar' => 'herp', 'baz' => '/derp'],
            ),
            array('/food/burger', '/food/{noms}', ['noms' => 'burger']),
            array('/bar/123', '/bar/{herp:\\d+}', ['herp' => '123']),
            array('/bar/derp', '/bar/{herp}', ['herp' => 'derp']),
            array('/bar/1derp', '/bar/{herp}', ['herp' => '1derp']),
            array('/unique/foo/bar', '/unique/{foo}/bar', ['foo' => 'foo']),
            array('/optional_suffix_', '/optional_suffix_[foo]', []),
            array('/optional_suffix_foo', '/optional_suffix_[foo]', []),
            array('/optional_suffix', '/optional_suffix[/]', []),
            array('/optional_suffix/', '/optional_suffix[/]', []),
            array('/optional_suffixes/', '/optional_suffixes/[herp[/derp]]', []),
            array(
                '/optional_suffixes/herp',
                '/optional_suffixes/[herp[/derp]]',
                [],
            ),
            array(
                '/optional_suffixes/herp/derp',
                '/optional_suffixes/[herp[/derp]]',
                [],
            ),
            array(
                '/manual/en/foo.php',
                '/manual/en/{LegacyID}.php',
                ['LegacyID' => 'foo'],
            ),
            array(
                '/manual/en/foo.bar.php',
                '/manual/en/{LegacyID}.php',
                ['LegacyID' => 'foo.bar'],
            ),
        ];
    }

    public function testCanGetExpectedMatchesWithResolvers(): void
    {
        $this->expectedMatchesWithResolvers();

        $this->addToAssertionCount(1);
    }

    /**
     * @return list<array{string, (function(array<non-empty-string, array<string, string>>): IResolver<string>)}>
     */
    public function getAllResolvers(): array
    {
        return [
            array('simple regexp', fn($map) => new SimpleRegexpResolver($map)),
            array(
                'prefix matching',
                fn($map) => PrefixMatchingResolver::fromFlatMap($map),
            ),
        ];
    }

    /**
     * @return list<array{string, IResolver<string>, string, string, array<string, string>}>
     */
    public function expectedMatchesWithResolvers(): array
    {
        $map = [HttpMethod::GET => Dict\associate(self::MAP, self::MAP)];
        $resolvers = Dict\from_entries($this->getAllResolvers());

        $out = [];
        $examples = $this->expectedMatches();
        foreach ($resolvers as $name => $resolver) {
            $resolver = $resolver($map);
            foreach ($examples as $ex) {
                $out[] = array($name, $resolver, $ex[0], $ex[1], $ex[2]);
            }
        }
        return $out;
    }

    /**
     * @dataProvider getAllResolvers
     *
     * @param (callable(array<non-empty-string, array<string, string >>): IResolver <string>) $factory
     */
    public function testMethodNotAllowedResponses(string $_name, callable $factory): void
    {
        $map = [
            HttpMethod::GET => ['/get' => 'get'],
            HttpMethod::HEAD => ['/head' => 'head'],
            HttpMethod::POST => ['/post' => 'post'],
        ];

        $router = $this->getRouter($map)->setResolver($factory($map));

        // HEAD -> GET ( re-routing )
        [$responder, $_data] = $router->match(HttpMethod::HEAD, '/get');

        self::assertSame('get', $responder);

        // GET -> HEAD
        try {
            $router->match(HttpMethod::GET, '/head');
            self::fail('GET -> HEAD');
        } catch (MethodNotAllowedException $e) {
            self::assertSame([HttpMethod::HEAD], $e->getAllowedMethods());
        }

        // HEAD -> POST
        try {
            $router->match(HttpMethod::HEAD, '/post');
            self::fail('HEAD -> POST');
        } catch (MethodNotAllowedException $e) {
            self::assertSame([HttpMethod::POST], $e->getAllowedMethods());
        }

        // GET -> POST
        try {
            $router->match(HttpMethod::GET, '/post');
            self::fail('GET -> POST');
        } catch (MethodNotAllowedException $e) {
            self::assertSame([HttpMethod::POST], $e->getAllowedMethods());
        }
    }


    /**
     * @dataProvider expectedMatches
     *
     * @param array<string, string> $expected_data
     */
    public function testMatchesPattern(string $in, string $expected_responder, array $expected_data): void
    {
        [$actual_responder, $actual_data] = $this->getRouter()->match(HttpMethod::GET, $in);

        self::assertSame($expected_data, $actual_data);
        self::assertSame($expected_responder, $actual_responder);
    }

    /**
     * @dataProvider expectedMatchesWithResolvers
     *
     * @param IResolver<string> $resolver
     * @param array<string, string> $expected_data
     */
    public function testAllResolvers(
        string $_resolver_name,
        IResolver $resolver,
        string $in,
        string $expected_responder,
        array $expected_data
    ): void {
        [$responder, $data] = $this->getRouter()->setResolver($resolver)->match(HttpMethod::GET, $in);

        self::assertSame($expected_responder, $responder);
        self::assertSame($expected_data, $data);

        // don't compare $_data to $expected_data as it is not url-decoded, that is done by the router.
        [$responder, $_data] = $resolver->resolve(HttpMethod::GET, $in);

        self::assertSame($expected_responder, $responder);

        [$responder, $data] = $this->getRouter()->setResolver($resolver)->match(HttpMethod::HEAD, $in);

        self::assertSame($expected_responder, $responder);
        self::assertSame($expected_data, $data);
    }

    /**
     * @dataProvider expectedMatches
     *
     * @param array<string, string> $expected_data
     */
    public function testSimpleRouting(
        string $path,
        string $expected_responder,
        array $expected_data
    ): void {
        $router = $this->getRouter();

        [$direct_responder, $direct_data] = $router->match(HttpMethod::GET, $path);

        self::assertSame($expected_data, $direct_data);
        self::assertSame($expected_responder, $direct_responder);
    }

    /**
     * @dataProvider getAllResolvers
     *
     * @param (callable(array<non-empty-string, array<string, string >>): IResolver <string>) $factory
     */
    public function testNotFoundEmpty(string $_resolver_name, callable $factory): void
    {
        $router = $this->getRouter()->setResolver($factory([]));

        $this->expectException(NotFoundException::class);

        $router->match(HttpMethod::GET, '/__404');
    }

    /**
     * @dataProvider getAllResolvers
     *
     * @param (callable(array<non-empty-string, array<string, string >>): IResolver <string>) $factory
     */
    public function testNotFound(string $_resolver_name, callable $factory): void
    {
        $router = $this->getRouter()
            ->setResolver($factory([HttpMethod::GET => ['/foo' => '/foo']]));

        $this->expectException(NotFoundException::class);

        $router->match(HttpMethod::GET, '/__404');
    }

    public function testMethodNotAllowed(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        $this->getRouter()->match(HttpMethod::POST, '/foo');
    }

    public function testCovariantTResponder(): void
    {
        $router = $this->getRouter();
        $this->typecheckCovariantTResponder($router, $router);
    }

    /**
     * @param AbstractMatcher<array-key> $_1
     * @param AbstractMatcher<string> $_2
     */
    private function typeCheckCovariantTResponder(AbstractMatcher $_1, AbstractMatcher $_2): void
    {
        $this->addToAssertionCount(1);
    }

    /**
     * @param null|array<non-empty-string, array<string, string>> $routes
     *
     * @return TestMatcher<string>
     */
    private function getRouter(?array $routes = null): TestMatcher
    {
        if (null === $routes) {
            $routes = [
                HttpMethod::GET => Dict\associate(self::MAP, self::MAP)
            ];
        }

        return new TestMatcher($routes);
    }
}
