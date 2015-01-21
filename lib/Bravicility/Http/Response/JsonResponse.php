<?php

namespace Bravicility\Http\Response;

class JsonResponse extends Response
{
    public function __construct($json = null)
    {
        parent::__construct(json_encode($json, JSON_UNESCAPED_UNICODE));
        $this->addHeader('Content-Type: application/json; charset=UTF-8');
    }
}
