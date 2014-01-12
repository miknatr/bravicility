<?php

namespace Bravicility\Http;

use Bravicility\Http\Response\Response;

class Kernel
{
    /**
     * @param Request $request
     * @param string[] $controllerDirs
     * @param string $routesCacheDir
     * @param string[] $exceptionCallback
     * @param $container
     * @return Response
     */
    public static function handleRequest(Request $request, array $controllerDirs, $routesCacheDir, $exceptionCallback, $container)
    {
        try {
            // не делаю инькцию, чтобы не усложнять index.php и вообще вряд ли аннатации будут из других источников
            // понятно, что тогда RouteProvider тогда надо делать без состояния, но пока не до конца понятно, будет так
            $routes = (new RouteProvider($controllerDirs, $routesCacheDir))->generateRoutes();

            list($callback, $vars) = static::route($routes, $request->getMethod(), $request->getUri());
            foreach ($vars as $k => $v) {
                $request->addOption($k, $v);
            }

            return static::run($callback, $request, $container);
        } catch (\Exception $e) {
            $request->addOption('exception', $e);
            return static::run($exceptionCallback, $request, $container);
        }
    }

    /**
     * @param array $routes
     * array(
     *     array(
     *         'method'   => 'GET',
     *         'regexp'   => '#^/$#',
     *         'callback' => array('\\Controller\\IndexController', 'index'),
     *         'defaults' => array('var' => 'val'),
     *     ),
     * ),
     * @param $method
     * @param string $uri without get parameters
     * @throws RouteNotFoundException
     * @return array
     */
    protected static function route(array $routes, $method, $uri)
    {
        foreach ($routes as $route) {
            if ($method != $route['method']) {
                continue;
            }

            if (!preg_match($route['regexp'], $uri, $match)) {
                continue;
            }

            $vars = $route['defaults'];
            foreach ($match as $k => $v) {
                if (!is_numeric($k)) {
                    $vars[$k] = $v;
                }
            }

            return array($route['callback'], $vars);
        }

        throw new RouteNotFoundException();
    }

    /**
     * @param string[] $callback
     * @param Request $request
     * @param $container
     * @return \Bravicility\Http\Response\Response
     */
    protected static function run($callback, Request $request, $container)
    {
        list($controllerClass, $controllerMethod) = $callback;
        $controller = new $controllerClass($container);
        return $controller->$controllerMethod($request);
    }
}
