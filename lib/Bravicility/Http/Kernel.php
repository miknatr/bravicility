<?php

namespace Bravicility\Http;

class Kernel
{
    /**
     * @param Request $request
     * @param array $routes
     * @param $exceptionAction
     * @param $container
     * @return Response
     */
    public static function handleRequest(Request $request, array $routes, $exceptionAction, $container)
    {
        try {
            $match = Kernel::route($routes, $request->method, $request->uri);
            $request->params['matches'] = $match[1];
            return Kernel::run($match[0], $request, $container);
        } catch (\Exception $e) {
            $request->params['exception'] = $e;
            return Kernel::run($exceptionAction, $request, $container);
        }
    }

    /**
     * @param array $routes
     * array(
     *     'index' => array(
     *         'method' => 'GET|POST',
     *         'uri'    => '#^/$#',
     *         'action' => '\\Controller\\IndexController::index',
     *     ),
     *     'forgot_password' => array(
     *         'method' => 'GET|POST',
     *         'uri'    => '#^/forgot_password$#',
     *         'action' => '\\Controller\\IndexController::forgot_password',
     *     ),
     *
     *     'order_message' => array(
     *         'method' => 'POST',
     *         'uri'    => '#^/order_message/(?<id>\w+)',
     *         'action' => '\\Controller\\OrderController::order_message',
     *     ),
     * ),
     * @param $method
     * @param string $uri
     * @throws RouteNotFoundException
     * @return array
     */
    protected static function route(array $routes, $method, $uri)
    {
        $uri = explode('?', $uri, 2)[0];

        foreach ($routes as $route) {
            if (in_array($method, $route['methods']) && preg_match($route['regex'], $uri, $matches)) {
                return array($route['action'], $matches);
            }
        }

        throw new RouteNotFoundException;
    }

    /**
     * @param $action
     * @param Request $request
     * @param $container
     * @return Response
     */
    protected static function run($action, Request $request, $container)
    {
        list($controllerClass, $controllerMethod) = explode('::', $action, 2);
        $controller = new $controllerClass($container);
        return $controller->$controllerMethod($request);
    }
}
