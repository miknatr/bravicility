<?php

namespace Bravicility\Http;

class Request
{
    protected $method;
    protected $uri;
    protected $get = array();
    protected $post = array();
    protected $options = array();
    protected $rawBody;
    protected $parsed   = array();

    public function __construct($method = 'GET', $uri = '/', array $get = array(), array $post = array(), $rawBody = '')
    {
        $this->method  = $method;
        $this->uri     = explode('?', $uri, 2)[0];
        $this->get     = $get;
        $this->post    = $post;
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

    public function getUri()
    {
        return $this->uri;
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
