<?php

namespace Bravicility\Http\Response;

class XmlResponse extends Response
{
    public function __construct($statusCode, $xml)
    {
        parent::__construct($statusCode, array('Content-Type: text/xml; charset=UTF-8'), $xml);
    }
} 
