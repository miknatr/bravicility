<?php

namespace Bravicility\Http\Response;

class XmlResponse extends Response
{
    public function __construct($xml)
    {
        parent::__construct($xml);
        $this->addHeader('Content-Type: text/xml; charset=UTF-8');
    }
}
