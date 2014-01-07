<?php

namespace Bravicility\Templater;

class Templater
{
    private $templateDir;
    private $commonVars;

    public function __construct($templateDir, array $commonVars = array())
    {
        $this->templateDir = rtrim($templateDir . '/');
        $this->commonVars  = $commonVars;
    }
    public function render($file, array $vars = array())
    {
        extract($vars);
        extract($this->commonVars);
        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->templateDir . $file . '.html.php';
        $r = ob_get_contents();
        ob_end_clean();
        return $r;
    }
} 
