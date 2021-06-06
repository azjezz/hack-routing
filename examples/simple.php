<?php

/***********
 * IF YOU EDIT THIS FILE also update the snippet in README.md
 ***********/

namespace HackRouting\Examples\BaseRouterExample;

use HackRouting\AbstractRouter;
use HackRouting\Cache;
use HackRouting\HttpException;
use HackRouting\HttpMethod;
use HackRouting\Router;
use Psl\IO;
use Psl\Str;

require_once(__DIR__ . '/../vendor/autoload.php');

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

    $cache = new Cache\ApcuCache();
    $router = new Router($cache);

    $router->addRoute(HttpMethod::GET, '/', function (): string {
        return 'Hello, World!';
    });

    $router->addRoute(HttpMethod::GET, '/user/{username}', function (array $parameters): string {
        return Str\format('Hello, %s!', $parameters['username']);
    });

    $router->addRoute(HttpMethod::POST, '/', function (): string {
        return 'Hello, POST world';
    });

    foreach (get_example_inputs() as [$method, $path]) {
        try {
            [$responder, $parameters] = $router->match($method, $path);

            $response = $responder($parameters);

            $method = Str\pad_right(Str\format('[%s]', $method), 8);
            $request = Str\pad_right(Str\format('%s %s', $method, $path), 25);

            $output->write(Str\format("%s -> %s\n", $request, $response));
        } catch (HttpException\MethodNotAllowedException $e) {
            $allowed_methods = $e->getAllowedMethods();

            // Handle 403.
        } catch (HttpException\NotFoundException) {
            // Handle 404.
        } catch (HttpException\InternalServerErrorException) {
            // Handle 500.
        }
    }

    exit(0);
})();
