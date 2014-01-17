<?php

namespace Bravicility\Http\Response;

class RedirectResponse extends Response
{
    public function __construct($location)
    {
        // TODO в Location должен быть полный урл с http://
        parent::__construct(302, array('Location: ' . $location));
    }
}
