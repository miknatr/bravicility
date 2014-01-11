<?php

namespace Bravicility\Http\Response;

class JsonResponse extends Response
{
    public function __construct($statusCode, $json = null)
    {
        parent::__construct($statusCode, array('Content-Type: application/json; charset=UTF-8'), json_encode($json));
    }
} 
