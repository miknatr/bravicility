<?php

namespace Bravicility\Http\Response;

class HtmlResponse extends Response
{
    public function __construct($html)
    {
        parent::__construct($html);
        $this->addHeader('Content-Type: text/html; charset=UTF-8');
    }
}
