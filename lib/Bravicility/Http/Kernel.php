<?php

namespace Bravicility\Http;

use Doctrine\Common\Annotations\SimpleAnnotationReader;

class Kernel
{
    /**
     * @param Request $request
     * @param array $controllerDirs
     * @param $routesCacheDir
     * @param $exceptionAction
     * @param $container
     * @return Response
     */
    public static function handleRequest(Request $request, array $controllerDirs, $routesCacheDir, $exceptionAction, $container)
    {
        try {
            $routes = static::generateRoutes($controllerDirs, $routesCacheDir);

            $match = static::route($routes, $request->getMethod(), $request->getUri());
            foreach ($match[1] as $k => $v) {
                if (is_string($k)) {
                    $request->addOption($k, $v);
                }
            }

            return static::run($match[0], $request, $container);
        } catch (\Exception $e) {
            $request->addOption('exception', $e);
            return static::run($exceptionAction, $request, $container);
        }
    }

    /**
     * @route GET adsf/adfs
     * @Route('./adsdf/adfs{name}')
     * @Method('POST')
     * @Method('GET')
     * @param array $controllerDirs
     * @param $cacheDir
     * @return array
     */
    protected static function generateRoutes(array $controllerDirs, $cacheDir)
    {
        // STOPPER кэширование

        $controllerFiles = array();
        foreach ($controllerDirs as $dir) {
            $controllerFiles = array_merge($controllerFiles, static::getAllFileInDirRecursively($dir));
        }

        $classes = array();
        foreach ($controllerFiles as $file) {
            $content = file_get_contents($file);

            preg_match("/^namespace\s+(\S+);$/m", $content, $namespaceMatches);
            $namespace = $namespaceMatches[1];

            preg_match('^class\s+(\S+)(?:(?:\s+extends|\s+implements|\s*\{)|$)/m', $content, $classMatches);
            $class = $classMatches[1];

            $classes[] = "\\$namespace\\$class";
        }

        $routes = array();
        foreach ($classes as $class) {
            $rClass = new \ReflectionClass($class);
            $rMethods = $rClass->getMethods();
            foreach ($rMethods as $rMethod) {
                if (!$rMethod->isAbstract() && $rMethod->isPublic()) {
                    $phpdoc = $rMethod->getDocComment();
                    $annotations = '';//..mgic;
                    $routes = ..
                }
            }
        }
    }

    protected static function getAllFileInDirRecursively($dir)
    {
        $files = array();
        $directories = array($dir);

        while (sizeof($directories)) {
            $curDir = array_pop($directories);
            $handle = opendir($curDir);
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $file  = $curDir . '/' . $file;
                if (is_dir($file)) {
                    $directory_path = $file;
                    array_push($directories, $directory_path);
                } elseif (is_file($file)) {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }

        return $files;
    }


    /**
     * @param array $routes
     * array(
     *     array(
     *         'methods' => 'GET|POST',
     *         'routes'  => '#^/$#',
     *         'action'  => '\\Controller\\IndexController::index',
     *     ),
     *     array(
     *         'methods' => 'GET|POST',
     *         'routes'  => '#^/forgot_password$#',
     *         'action'  => '\\Controller\\IndexController::forgot_password',
     *     ),
     *     array(
     *         'methods' => 'POST',
     *         'routes'  => '#^/order_message/(?<id>\w+)',
     *         'action'  => '\\Controller\\OrderController::order_message',
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
            foreach ($route['routes'] as $routeRegex) {
                if (in_array($method, $route['methods']) && preg_match($routeRegex, $uri, $matches)) {
                    return array($route['action'], $matches);
                }
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
