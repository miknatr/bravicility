<?php

namespace Bravicility\Http;

class Request
{
    /**
     * @return Request
     */
    public static function createFromGlobals()
    {
        return (new static)
            ->setMethod($_SERVER['REQUEST_METHOD'])
            ->setUrlPath($_SERVER['REQUEST_URI'])
            ->setGet($_GET)
            ->setPost($_POST)
            ->setCookies($_COOKIE)
            ->setServer($_SERVER)
            ->setRawBody(function () {
                return file_get_contents('php://input');
            })
        ;
    }


    //
    // LOCATION
    //

    protected $method;
    protected $urlPath;

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

    public function setUrlPath($urlPath)
    {
        $this->urlPath = explode('?', $urlPath, 2)[0];
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }


    //
    // GET
    //

    protected $get = array();

    public function get($name, $default = null)
    {
        return isset($this->get[$name]) ? $this->get[$name] : $default;
    }

    public function allGet()
    {
        return $this->get;
    }

    public function setGet(array $get)
    {
        $this->get = $get;
        return $this;
    }


    //
    // POST
    //

    protected $post = array();

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

    public function setPost(array $post)
    {
        $this->post = $post;
        return $this;
    }


    //
    // COOKIES
    //

    protected $cookies = array();

    public function cookie($name, $default = null)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : $default;
    }

    public function allCookies()
    {
        return $this->cookies;
    }

    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;
        return $this;
    }


    //
    // OPTIONS
    //

    protected $options = array();

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

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }


    //
    // SERVER
    //

    protected $server  = array();

    public function server($name, $default = null)
    {
        return isset($this->server[$name]) ? $this->server[$name] : $default;
    }

    public function allServer()
    {
        return $this->server;
    }

    public function setServer(array $server)
    {
        $this->server = $server;
        if (!$this->headers) {
            $this->setHeaders(function () {
                $headers = array();
                foreach ($this->allServer() as $k => $v) {
                    if (substr($k, 0, 5) !== 'HTTP_') {
                        continue;
                    }

                    $name = str_replace('_', '-', substr($k, 5));
                    $headers[$name] = $v;
                }
                return $headers;
            });
        }
        return $this;
    }


    //
    // HEADERS
    //

    protected $headers;

    public function header($name, $default = null)
    {
        $name    = strtolower($name);
        $headers = $this->allHeaders();
        return isset($headers[$name]) ? $headers[$name] : $default;
    }

    public function allHeaders()
    {
        if (is_callable($this->headers)) {
            $this->setHeaders(call_user_func($this->headers));
        }
        return $this->headers;
    }

    public function getUserAgent()
    {
        return $this->server('HTTP_USER_AGENT');
    }

    public function getContentType()
    {
        $header = $this->header('Content-Type');
        if ($header === null) {
            $header = $this->server('HTTP_CONTENT_TYPE');
        }
        return $header;
    }

    /**
     * @param string[]|callable $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            $this->headers = array();
            foreach ($headers as $name => $value) {
                $this->headers[strtolower($name)] = $value;
            }
        } else {
            // callable (lazy loading of headers)
            $this->headers = $headers;
        }
        return $this;
    }


    //
    // RAW BODY
    //

    protected $rawBody;

    public function getRawBody()
    {
        if (is_callable($this->rawBody)) {
            $this->rawBody = call_user_func($this->rawBody);
        }
        return $this->rawBody;
    }

    /**
     * @param string|callable $rawBody
     * @return $this
     */
    public function setRawBody($rawBody)
    {
        $this->rawBody = $rawBody;
        return $this;
    }

    protected $parsed = array();

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


    //
    // CLIENT IP
    //

    protected $trustedProxies = array();

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

    public function setTrustedProxies(array $trustedProxies)
    {
        $this->trustedProxies = $trustedProxies;
    }
}
