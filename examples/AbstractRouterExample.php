<?php

/***********
 * IF YOU EDIT THIS FILE also update the snippet in README.md
 ***********/

namespace HackRouting\Examples\BaseRouterExample;

require_once(__DIR__ . '/../vendor/autoload.php');

use HackRouting\AbstractRouter;
use HackRouting\Cache\MemoryCache;
use HackRouting\HttpMethod;
use Psl\IO;
use Psl\Str;

/**
 * @extends BaseRouter<(function(array<string, string>):string)>
 */
final class AbstractRouterExample extends AbstractRouter
{
    /**
     * @return array<non-empty-string, array<string, (function(array<string, string>):string)>>
     */
    protected function getRoutes(): array
    {
        return [
            HttpMethod::GET => [
                '/' => fn($_params): string => 'Hello, world',
                '/user/settings' => fn($_params): string => 'User settings!',
                '/user/{user_name}' => fn(array $params): string => 'Hello, ' . $params['user_name'],
            ],

            HttpMethod::POST => [
                '/' => fn($_params) => 'Hello, POST world',
            ],
        ];
    }
}

/**
 * @return iterable<array{0: non-empty-string, 1: string}>
 */
function get_example_inputs(): iterable
{
    yield array(HttpMethod::GET, '/');
    yield array(HttpMethod::GET, '/user/foo');
    yield array(HttpMethod::GET, '/user/bar');
    yield array(HttpMethod::POST, '/');
}

(static function (): void {
    $output = IO\output_handle();
    $router = new AbstractRouterExample(new MemoryCache());
    foreach (get_example_inputs() as $input) {
        [$method, $path] = $input;

        [$responder, $params] = $router->match($method, $path);

        $method = Str\pad_right(Str\format('[%s]', $method), 8);
        $request = Str\pad_right(Str\format('%s %s', $method, $path), 25);
        $output->write(Str\format("%s -> %s\n", $request, $responder($params)));
    }
})();
