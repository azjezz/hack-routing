<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackRouter;

use namespace HH\Lib\Dict;
use function Facebook\AutoloadMap\Generated\is_dev;

abstract class BaseRouter<+TResponder> {
  abstract protected function getRoutes(
  ): ImmMap<HttpMethod, ImmMap<string, TResponder>>;

  final public function routeMethodAndPath(
    HttpMethod $method,
    string $path,
  ): (TResponder, ImmMap<string, string>) {
    $resolver = $this->getResolver();
    try {
      list($responder, $data) = $resolver->resolve($method, $path);
      $data = Dict\map($data, $value ==> \urldecode($value));
      return tuple($responder, new ImmMap($data));
    } catch (NotFoundException $e) {
      foreach (HttpMethod::getValues() as $next) {
        if ($next === $method) {
          continue;
        }
        try {
          list($responder, $data) = $resolver->resolve($next, $path);
          if ($method === HttpMethod::HEAD && $next === HttpMethod::GET) {
            $data = Dict\map($data, $value ==> \urldecode($value));
            return tuple($responder, new ImmMap($data));
          }
          throw new MethodNotAllowedException();
        } catch (NotFoundException $_) {
          continue;
        }
      }
      throw $e;
    }
  }

  final public function routeRequest(
    \Facebook\Experimental\Http\Message\RequestInterface $request,
  ): (TResponder, ImmMap<string, string>) {
    $method = HttpMethod::coerce($request->getMethod());
    if ($method === null) {
      throw new MethodNotAllowedException();
    }
    return $this->routeMethodAndPath($method, $request->getUri()->getPath());
  }

  private ?IResolver<TResponder> $resolver = null;

  protected function getResolver(): IResolver<TResponder> {
    if ($this->resolver !== null) {
      return $this->resolver;
    }

    if (is_dev()) {
      $routes = null;
    } else {
      $_success = null;
      $routes = \apc_fetch(__FILE__.'/cache', inout $_success);
      if ($routes === false) {
        $routes = null;
      }
    }

    if ($routes === null) {
      $routes = Dict\map(
        $this->getRoutes(),
        $method_routes ==> PrefixMatching\PrefixMap::fromFlatMap(
          dict($method_routes),
        ),
      );

      if (!is_dev()) {
        \apc_store(__FILE__.'/cache', $routes);
      }
    }
    $this->resolver = new PrefixMatchingResolver($routes);
    return $this->resolver;
  }
}
