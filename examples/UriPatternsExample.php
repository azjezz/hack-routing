<?php

/***********
 * IF YOU EDIT THIS FILE also update the snippet in README.md
 ***********/

namespace HackRouting\Examples\UrlPatternsExample;

require_once(__DIR__ . '/../vendor/autoload.php');

use HackRouting\AbstractRouter;
use HackRouting\Cache\FileCache;
use HackRouting\Cache\MemoryCache;
use HackRouting\HttpMethod;
use HackRouting\Parameter\RequestParameters;
use HackRouting\UriPattern\GetRoutePatternFromUriPattern;
use HackRouting\UriPattern\GetUriBuilderFromUriPattern;
use HackRouting\UriPattern\HasUriPattern;
use HackRouting\UriPattern\UriPattern;
use Psl\IO;
use Psl\Str;

/**
 * @consistent-construct
 */
abstract class WebController implements HasUriPattern
{
    use GetRoutePatternFromUriPattern;
    use GetUriBuilderFromUriPattern;

    abstract public function getResponse(): string;

    private RequestParameters $uriParameters;

    final protected function getRequestParameters(): RequestParameters
    {
        return $this->uriParameters;
    }

    /**
     * @param array<string, string> $uri_parameter_values
     */
    public function __construct(array $uri_parameter_values)
    {
        $this->uriParameters = new RequestParameters(
            static::getUriPattern()->getParameters(),
            [],
            $uri_parameter_values,
        );
    }
}

final class HomePageController extends WebController
{
    public static function getUriPattern(): UriPattern
    {
        return (new UriPattern())->literal('/');
    }

    public function getResponse(): string
    {
        return 'Hello, world';
    }
}

final class UserPageController extends WebController
{
    public static function getUriPattern(): UriPattern
    {
        return (new UriPattern())
            ->literal('/users/')
            ->string('user_name');
    }

    public function getResponse(): string
    {
        return 'Hello, ' . $this->getRequestParameters()->getString('user_name');
    }
}

final class PageController extends WebController
{
    public static function getUriPattern(): UriPattern
    {
        return (new UriPattern())
            ->slash()
            ->enum('page', ['about', 'contact'])
            ->literal('-us');
    }

    public function getResponse(): string
    {
        $parameters = $this->getRequestParameters();
        if ($parameters->getEnum('page') === 'about') {
            return 'Learn more about us.';
        }

        return 'Contact us';
    }
}

/**
 * @extends BaseRouter<class-string<WebController>>
 */
final class UriPatternsExample extends AbstractRouter
{
    /**
     * @return list<class-string<WebController>>
     */
    public static function getControllers(): array
    {
        return [
            HomePageController::class,
            UserPageController::class,
            PageController::class
        ];
    }

    /**
     * @return array<non-empty-string, array<string, class-string<WebController>>>
     */
    public function getRoutes(): array
    {
        $urls_to_controllers = [];
        /** @var \HackRouting\Examples\UrlPatternsExample\WebController $controller */
        foreach (self::getControllers() as $controller) {
            $pattern = $controller::getRoutePattern();
            $urls_to_controllers[$pattern] = $controller;
        }

        return [
            HttpMethod::GET => $urls_to_controllers
        ];
    }
}

/**
 * @return iterable<string>
 */
function get_example_paths(): iterable
{
    yield HomePageController::getUriBuilder()->getPath(); // "/"
    yield UserPageController::getUriBuilder()->setString('user_name', 'Mr Hankey')->getPath(); // "/users/Mr Hankey"
    yield PageController::getUriBuilder()->setEnum('page', 'about')->getPath(); // "/about-us"
    yield PageController::getUriBuilder()->setEnum('page', 'contact')->getPath(); // "/contact-us"
}

(static function (): void {
    $output = IO\output_handle();
    $router = new UriPatternsExample(new MemoryCache());
    foreach (get_example_paths() as $path) {
        [$controller, $params] = $router->match(
            HttpMethod::GET,
            $path,
        );

        $method = Str\pad_right(Str\format('[%s]', 'GET'), 8);
        $request = Str\pad_right(Str\format('%s %s', $method, $path), 25);
        $output->write(Str\format("%s -> %s\n", $request, (new $controller($params))->getResponse()));
    }

    exit(0);
})();
