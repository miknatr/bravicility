<?php

namespace Bravicility\Http;

class Request
{
    protected $method;
    protected $urlPath;
    protected $get     = array();
    protected $post    = array();
    protected $options = array();
    protected $parsed  = array();
    protected $cookie  = array();
    protected $rawBody;

    public static function createFromGlobals()
    {
        return new static($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_GET, $_POST, $_COOKIE, file_get_contents('php://input'));
    }


    public function __construct($method = 'GET', $urlPath = '/', array $get = array(), array $post = array(), array $cookie = array(), $rawBody = '')
    {
        $this->method  = $method;
        $this->urlPath = explode('?', $urlPath, 2)[0];
        $this->get     = $get;
        $this->post    = $post;
        $this->cookie  = $cookie;
        $this->rawBody = $rawBody;
    }


    public function parseBodyAsJson()
    {
        $arr = json_decode($this->rawBody, true);
        $this->parsed = is_array($arr) ? $arr : array();
    }

    public function parseBodyAsUrlEncoded()
    {
        parse_str($this->rawBody, $this->parsed);
    }

    // TODO название
    public function getLocation()
    {
        $location = $this->urlPath;
        if (!empty($this->get)) {
            $location .= '?' . http_build_query($this->get);
        }
        return $location;
    }

    public function getUrlPath()
    {
        return $this->urlPath;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function get($name, $default = null)
    {
        return isset($this->get[$name]) ? $this->get[$name] : $default;
    }

    public function allGet()
    {
        return $this->get;
    }

    public function post($name, $default = null)
    {
        return isset($this->post[$name]) ? $this->post[$name] : $default;
    }

    public function allPost()
    {
        return $this->post;
    }

    public function cookie($name, $default = null)
    {
        return isset($this->cookie[$name]) ? $this->cookie[$name] : $default;
    }

    public function allCookie()
    {
        return $this->cookie;
    }

    public function option($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function allOptions()
    {
        return $this->options;
    }

    public function addOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }
}
