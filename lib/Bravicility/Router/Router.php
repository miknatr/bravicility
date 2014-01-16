<?php

namespace Bravicility\Router;

use Bravicility\Http\Request;

class Router
{
    /** @var string[] */
    private $controllerDirs = array();
    private $routesCacheDir;

    public function __construct(array $controllerDirs, $routesCacheDir)
    {
        $this->controllerDirs = $controllerDirs;
        $this->routesCacheDir = $routesCacheDir;
    }

    /**
     * @param string $method
     * @param string $urlPath
     * @return Route
     */
    public function route($method, $urlPath)
    {
        // STOPPER кэширование
        $routeRules = (new RouteProvider($this->controllerDirs, $this->routesCacheDir))->generateRoutes();

        return static::findMatchingRoute($routeRules, $method, $urlPath);
    }

    /**
     * @param array $routeRules
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
     * @return Route
     */
    protected static function findMatchingRoute(array $routeRules, $method, $uri)
    {
        foreach ($routeRules as $rule) {
            if ($method != $rule['method']) {
                continue;
            }

            if (!preg_match($rule['regexp'], $uri, $match)) {
                continue;
            }

            $vars = $rule['defaults'];
            foreach ($match as $k => $v) {
                if (!is_numeric($k)) {
                    $vars[$k] = $v;
                }
            }

            $route = new Route();
            $route->class = $rule['callback'][0];
            $route->method = $rule['callback'][1];
            $route->vars = $vars;

            return $route;
        }

        throw new RouteNotFoundException();
    }
}
