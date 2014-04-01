<?php

namespace Bravicility\Http\Response;

class JsonCallbackResponse extends Response
{
    public function __construct($json, $callback)
    {
        $content = $callback . '(' . json_encode($json) . ')';
        parent::__construct(200, array('Content-Type: application/json; charset=UTF-8'), $content);
    }
}
