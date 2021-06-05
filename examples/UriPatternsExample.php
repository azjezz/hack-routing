<?php

/***********
 * IF YOU EDIT THIS FILE also update the snippet in README.md
 ***********/

namespace HackRouting\Examples\UrlPatternsExample;

require_once(__DIR__ . '/../vendor/autoload.php');

use HackRouting\BaseRouter;
use HackRouting\Cache\FileCache;
use HackRouting\HttpMethod;
use HackRouting\Parameter\RequestParameters;
use HackRouting\UriPattern\GetFastRoutePatternFromUriPattern;
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
    use GetFastRoutePatternFromUriPattern;
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

/**
 * @extends BaseRouter<class-string<WebController>>
 */
final class UriPatternsExample extends BaseRouter
{
    /**
     * @return list<class-string<WebController>>
     */
    public static function getControllers(): array
    {
        return [
            HomePageController::class,
            UserPageController::class,
        ];
    }

    /**
     * @return array<non-empty-string, array<string, class-string<WebController>>>
     */
    public function getRoutes(): array
    {
        $urls_to_controllers = [];
        foreach (self::getControllers() as $controller) {
            $pattern = $controller::getFastRoutePattern();
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
    yield HomePageController::getUriBuilder()->getPath();
    yield UserPageController::getUriBuilder()->setString('user_name', 'Mr Hankey')->getPath();
}

(static function (): void {
    $output = IO\output_handle();
    $router = new UriPatternsExample(new FileCache());
    foreach (get_example_paths() as $path) {
        [$controller, $params] = $router->routeMethodAndPath(
            HttpMethod::GET,
            $path,
        );

        $method = Str\pad_right(Str\format('[%s]', 'GET'), 8);
        $request = Str\pad_right(Str\format('%s %s', $method, $path), 25);
        $output->write(Str\format("%s -> %s\n", $request, (new $controller($params))->getResponse()));
    }

    exit(0);
})();
