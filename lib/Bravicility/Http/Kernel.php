<?php

namespace Bravicility\Http;

class Kernel
{
    /**
     * @param array $routes
     * array(
     *     'METHOD regex' => 'controllerClass::controllerMethod',
     *     'GET /^\/api\/(?<version>[0-9]+)\/Primes\/(?<id>[0-9]+)$/' => '\\Primes\\PrimesController::getPrimeNumber',
     * );
     * @param string $uri 'POST /api/v42/Primes/73'
     * @throws NotFoundException
     * @return mixed
     */
    public static function route(array $routes, $uri)
    {
        $uri = explode('?', $uri, 2)[0];

        /** @var Response|null $response */
        $response = null;
        foreach ($routes as $regex => $alias) {
            if (preg_match($regex, $uri, $matches)) {
                return array($alias, $matches);
            }
        }

        throw new NotFoundException;
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
