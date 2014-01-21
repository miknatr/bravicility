<?php

namespace Bravicility\Router;

class RouteProvider
{
    /** @var string[] */
    private $controllerDirs = array();

    public function __construct(array $controllerDirs)
    {
        $this->controllerDirs = $controllerDirs;
    }

    /** @return array */
    public function generateRoutes()
    {
        $controllerFiles = array();
        foreach ($this->controllerDirs as $dir) {
            $controllerFiles = array_merge($controllerFiles, getFilesRecursively($dir));
        }

        $classes = array();
        foreach ($controllerFiles as $file) {
            $content = file_get_contents($file);

            $namespace = '';
            if (preg_match('/^namespace\s+(\S+);$/m', $content, $namespaceMatch)) {
                $namespace = '\\' . $namespaceMatch[1];
            }

            if (!preg_match('/^class\s+(\S+)(?:(?:\s+extends|\s+implements|\s*\{)|$)/m', $content, $classMatch)) {
                continue;
            }
            $class = $classMatch[1];

            $classes[] = $namespace . '\\' . $class;
        }

        $routes = array();
        foreach ($classes as $class) {
            $rClass = new \ReflectionClass($class);

            $classPattern  = '';
            $classDefaults = array();
            foreach (getPhpdocTags($rClass->getDocComment()) as $annotation) {
                if ($annotation['name'] != 'route') {
                    continue;
                }

                if (!empty($classPattern)) {
                    throw new \Exception("Class {$class} has more than one @route annotation");
                }

                if (empty($annotation['args'])) {
                    throw new \Exception("Class {$class} has empty @route annotation");
                }

                $classPattern  = array_shift($annotation['args']);
                $classDefaults = $annotation['args'];
            }

            $rMethods = $rClass->getMethods();
            foreach ($rMethods as $rMethod) {
                if ($rMethod->isAbstract() || !$rMethod->isPublic() || $rMethod->isStatic()) {
                    continue;
                }

                foreach (getPhpdocTags($rMethod->getDocComment()) as $annotation) {
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

        return "#^{$regexp}/?$#";
    }
}
