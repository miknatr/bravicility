<?php

namespace Bravicility\Http\Response;

class TextResponse extends Response
{
    public function __construct($text)
    {
        parent::__construct($text);
        $this->addHeader('Content-Type: text/plain; charset=UTF-8');
    }
}
