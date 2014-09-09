<?php

namespace Bravicility\Http\Response;

class JavaScriptResponse extends Response
{
    public function __construct($source)
    {
        parent::__construct($source);
        $this->addHeader('Content-Type: application/javascript; charset=UTF-8');
    }
}
