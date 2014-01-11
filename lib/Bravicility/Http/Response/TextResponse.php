<?php

namespace Bravicility\Http\Response;

class TextResponse extends Response
{
    public function __construct($statusCode, $text)
    {
        parent::__construct($statusCode, array('Content-Type: text/plain; charset=UTF-8'), $text);
    }
} 
