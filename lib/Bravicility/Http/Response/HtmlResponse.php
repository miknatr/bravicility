<?php

namespace Bravicility\Http\Response;

class HtmlResponse extends Response
{
    public function __construct($statusCode, $html)
    {
        parent::__construct($statusCode, array('Content-Type: text/html; charset=UTF-8'), $html);
    }
} 
