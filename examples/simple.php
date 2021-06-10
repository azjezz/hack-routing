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
    yield [HttpMethod::GET, '/'];
    yield [HttpMethod::GET, '/user/foo'];
    yield [HttpMethod::GET, '/user/bar'];
    yield [HttpMethod::GET, '/contact-us'];
    yield [HttpMethod::GET, '/about-us'];
    yield [HttpMethod::POST, '/'];

    // known 404
    yield [HttpMethod::GET, '/user/31'];
    yield [HttpMethod::GET, '/user/HELLO'];
    yield [HttpMethod::GET, '/user/Hans8'];

    // known 403
    yield [HttpMethod::PUT, '/user/azjezz'];
}

(static function (): void {
    $output = IO\output_handle();

    $cache = new Cache\ApcuCache();
    $router = new Router($cache);

    $router->addRoute(HttpMethod::GET, '/', function (): string {
        return 'Hello, World!';
    });

    $router->addRoute(HttpMethod::GET, '/user/{username:[a-z]+}', function (array $parameters): string {
        return Str\format('Hello, %s!', $parameters['username']);
    });

    $router->addRoute(HttpMethod::POST, '/', function (): string {
        return 'Hello, POST world';
    });

    $router->addRoute(HttpMethod::GET, '/{page:about|contact}-us', static function (array $parameters): string {
        if ($parameters['page'] === 'about') {
            return 'Learn about us';
        }

        return 'Contact us';
    });

    foreach (get_example_inputs() as [$method, $path]) {
        try {
            [$responder, $parameters] = $router->match($method, $path);

            $response = $responder($parameters);
        } catch (HttpException\MethodNotAllowedException $e) {
            $response = 'Error[403]: allowed methods "' . Str\join($e->getAllowedMethods(), '", "') . '"';
        } catch (HttpException\NotFoundException) {
            $response = 'Error[404]';
        } catch (HttpException\InternalServerErrorException) {
            $response = 'Error[500]';
        }

        $method = Str\pad_right(Str\format('[%s]', $method), 8);
        $request = Str\pad_right(Str\format('%s %s', $method, $path), 25);

        $output->write(Str\format("%s -> %s\n", $request, $response));
    }

    exit(0);
})();
