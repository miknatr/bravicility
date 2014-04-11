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
    protected $server  = array();
    protected $rawBody;

    public static function createFromGlobals()
    {
        return new static($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_GET, $_POST, $_COOKIE, $_SERVER, file_get_contents('php://input'));
    }


    public function __construct($method = 'GET', $urlPath = '/', array $get = array(), array $post = array(), array $cookie = array(), array $server = array(), $rawBody = '')
    {
        $this->method  = $method;
        $this->urlPath = explode('?', $urlPath, 2)[0];
        $this->get     = $get;
        $this->post    = $post;
        $this->cookie  = $cookie;
        $this->server  = $server;
        $this->rawBody = $rawBody;
    }

    protected $trustedProxies = array();
    public function setTrustedProxies(array $trustedProxies)
    {
        $this->trustedProxies = $trustedProxies;
    }

    public function getUserAgent()
    {
        return $this->server('HTTP_USER_AGENT');
    }

    public function getContentType()
    {
        $header = $this->server('CONTENT_TYPE');
        if ($header === null) {
            $header = $this->server('HTTP_CONTENT_TYPE');
        }
        return $header;
    }

    public function getClientIp()
    {
        $ip = $this->server('REMOTE_ADDR');

        if (empty($this->trustedProxies)) {
            return $ip;
        }

        $forwardedFor = $this->server('X_FORWARDED_FOR');
        if (!$forwardedFor) {
            return $ip;
        }

        $clientIps = array_map('trim', explode(',', $forwardedFor));
        $clientIps[] = $ip;

        $clientIps = array_diff($clientIps, empty($this->trustedProxies) ? array($ip) : $this->trustedProxies);

        return array_pop($clientIps);
    }

    public function parseBodyAsJson()
    {
        $arr = json_decode($this->getRawBody(), true);
        $this->parsed = is_array($arr) ? $arr : array();
        return $this->parsed;
    }

    public function parseBodyAsUrlEncoded()
    {
        parse_str($this->getRawBody(), $this->parsed);
        return $this->parsed;
    }

    // TODO название
    public function getLocation()
    {
        $location = $this->getUrlPath();
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

    public function filterPostByFieldList(array $fieldList)
    {
        $r = array();
        foreach ($fieldList as $fieldName) {
            $r[$fieldName] = $this->post($fieldName);
        }
        return $r;
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

    public function addOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->options[$name] = $value;
        }

        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    public function server($name, $default = null)
    {
        return isset($this->server[$name]) ? $this->server[$name] : $default;
    }

    public function allServer()
    {
        return $this->server;
    }

    public function getRawBody()
    {
        return $this->rawBody;
    }
}
