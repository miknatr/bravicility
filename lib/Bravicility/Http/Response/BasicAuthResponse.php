<?php

namespace Bravicility\Http\Response;

class BasicAuthResponse extends Response
{
    public function __construct($description)
    {
        parent::__construct(401, array(
            'Content-Type: text/html; charset=UTF-8',
            'WWW-Authenticate: Basic realm="' . urlencode($description) . '"',
        ));
    }
}
