<?php

namespace Bravicility\Http;

class Request
{
    public $method;
    public $uri;
    protected $body;
    public $params = array();
    public $data = array();

    public function __construct($method = 'GET', $uri = '/', array $getParams = array(), $body = '')
    {
        $this->method = $method;
        $this->uri    = $uri;
        $this->params = $getParams;
        $this->body   = $body;
    }

    public function parseBodyAsJson()
    {
        $arr = json_decode($this->body, true);
        $this->data = is_array($arr) ? $arr : array();
    }

    public function parseBodyAsUrlEncoded()
    {
        parse_str($this->body, $this->data);
    }
}
