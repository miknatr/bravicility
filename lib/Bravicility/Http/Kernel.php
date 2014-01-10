<?php

namespace Bravicility\Http;

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
            $routes = static::generateRoutes($controllerDirs, $routesCacheDir);

            list($callback, $vars) = static::route($routes, $request->getMethod(), $request->getUri());
            foreach ($vars as $k => $v) {
                $request->addOption($k, $v);
            }

            return static::run($callback, $request, $container);
        } catch (\Exception $e) {
            var_dump($e);
            $request->addOption('exception', $e);
            return static::run($exceptionCallback, $request, $container);
        }
    }

    /**
     * @param array $controllerDirs
     * @param string $cacheDir
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

            if (!preg_match('/^class\s+(\S+)(?:(?:\s+extends|\s+implements|\s*\{)|$)/m', $content, $classMatches)) {
                continue;
            }
            $class = $classMatches[1];

            $classes[] = "\\$namespace\\$class";
        }

        $routes = array();
        foreach ($classes as $class) {
            $rClass = new \ReflectionClass($class);

            $classPattern    = '';
            $classDefaults = array();
            foreach (static::getPhpdocTags($rClass->getDocComment()) as $annotation) {
                if ($annotation['name'] != 'route') {
                    continue;
                }

                if (!empty($classPattern)) {
                    throw new \Exception("Class {$class} has more than one @route annotation");
                }

                if (empty($annotation['args'])) {
                    throw new \Exception("Class {$class} has empty @route annotation");
                }

                $classPattern    = array_shift($annotation['args']);
                $classDefaults = $annotation['args'];
            }

            $rMethods = $rClass->getMethods();
            foreach ($rMethods as $rMethod) {
                if ($rMethod->isAbstract() || !$rMethod->isPublic() || $rMethod->isStatic()) {
                    continue;
                }

                foreach (static::getPhpdocTags($rMethod->getDocComment()) as $annotation) {
                    if ($annotation['name'] != 'route') {
                        continue;
                    }

                    // TODO файлы и строки/методы в тексты исключений

                    if (count($annotation['args']) < 2) {
                        throw new \Exception('Incorrect annotation: ' . $annotation['string']);
                    }

                    $method = array_shift($annotation['args']);
                    if (!preg_match('/^[A-Z]+$/', $method)) {
                        throw new \Exception('Incorrect HTTP method in annotation: ' . $annotation['string']);
                    }

                    $pattern = $classPattern . array_shift($annotation['args']);

                    $defaultDefs = array_merge($classDefaults, $annotation['args']);

                    $defaults = array();
                    foreach ($defaultDefs as $def) {
                        $parts = explode('=', $def, 2);
                        if (count($parts) != 2) {
                            throw new \Exception("Incorrect route default: {$def} in " . $annotation['string']);
                        }
                        $defaults[$parts[0]] = $parts[1];
                    }

                    $routes[] = array(
                        'callback' => array($class, $rMethod->getName()),
                        'method'   => $method,
                        'regexp'   => static::makeRouteRegexp($pattern),
                        'defaults' => $defaults,
                    );
                }
            }
        }

        return $routes;
    }

    private static function makeRouteRegexp($pattern)
    {
        $regexp = '';

        $parts = preg_split('/(\{\w+\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i % 2) {
                // нечетная = {переменная}
                $name = preg_quote(substr($part, 1, -1), '#');
                $regexp .= '(?<' . $name . '>[^/]+)';
            } else {
                // четная = кусок урла
                $regexp .= preg_quote($part, '#');
            }
        }

        return "#^{$regexp}$#";
    }

    public static function getPhpdocTags($phpdoc)
    {
        preg_match_all(
            '{
                ^
                \s* \* \s+
                @ (\S+) ([^\n]*)
                $
            }xm',
            $phpdoc,
            $matches,
            PREG_SET_ORDER
        );

        $tags = array();
        foreach ($matches as $match) {
            // TODO поддержка закавыченных строк
            $args = preg_split('/\s+/', $match[2], -1, PREG_SPLIT_NO_EMPTY);

            $tags[] = array(
                'name'   => $match[1],
                'args'   => $args,
                'string' => '@' . $match[1] . $match[2],
            );
        }

        return $tags;
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
     * @return Response
     */
    protected static function run($callback, Request $request, $container)
    {
        list($controllerClass, $controllerMethod) = $callback;
        $controller = new $controllerClass($container);
        return $controller->$controllerMethod($request);
    }
}
