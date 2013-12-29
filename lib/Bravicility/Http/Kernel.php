<?php

namespace Bravicility\Http;

class Kernel
{
    /**
     * @param Request $request
     * @param array $routes
     * @param $exceptionControllerAlias
     * @param $container
     * @return Response
     */
    public static function handleRequest(Request $request, array $routes, $exceptionControllerAlias, $container)
    {
        try {
            $match = Kernel::route($routes, $request->method . ' ' . $request->uri);
            $request->params['matches'] = $match[1];
            return Kernel::run($match[0], $request, $container);
        } catch (\Exception $e) {
            $request->params['exception'] = $e;
            return Kernel::run($exceptionControllerAlias, $request, $container);
        }
    }

    /**
     * @param array $routes
     * array(
     *     'METHOD regex' => 'controllerClass::controllerMethod',
     *     'GET /^\/api\/(?<version>[0-9]+)\/Primes\/(?<id>[0-9]+)$/' => '\\Primes\\PrimesController::getPrimeNumber',
     * );
     * @param string $uri 'POST /api/v42/Primes/73'
     * @throws RouteNotFoundException
     * @return array
     */
    public static function route(array $routes, $uri)
    {
        $uri = explode('?', $uri, 2)[0];

        foreach ($routes as $regex => $controllerAlias) {
            if (preg_match($regex, $uri, $matches)) {
                return array($controllerAlias, $matches);
            }
        }

        throw new RouteNotFoundException;
    }

    /**
     * @param $controllerAlias
     * @param Request $request
     * @param $container
     * @return Response
     */
    public static function run($controllerAlias, Request $request, $container)
    {
        list($controllerClass, $controllerMethod) = explode('::', $controllerAlias, 2);
        $controller = new $controllerClass($container);
        return $controller->$controllerMethod($request);
    }
}
