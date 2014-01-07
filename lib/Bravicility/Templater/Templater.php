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

        // STOPPER какое-то не очень место (очень не очень)
        // но если в шаблонах объявлять неймспейс, тогда там нельзя отключить подчеркивание необъявленных переменных (а так можно для глобального неймспеса)
        // отключать подчеркивание необъявленных переменных для всего файла вроде нет способа (тогда можно для шаблонов бы тупо отключить)
        // так тоже магия, но этот способ можно допилить до плагинов?
        $e     = 'static::e';
        $eAttr = 'static::eAttr';
        $eUrl  = 'static::eUrl';

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->templateDir . $file . '.html.php';
        $r = ob_get_contents();
        ob_end_clean();
        return $r;
    }

    public static function e($value)
    {
        return htmlspecialchars($value);
    }

    public static function eAttr($value)
    {
        // STOPPER здесь че вще
        return $value;
    }

    public static function eUrl($value)
    {
        return urlencode($value);
    }
} 
