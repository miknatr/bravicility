<?php

namespace Bravicility\Http\Response;

class RedirectResponse extends Response
{
    public function __construct($location)
    {
        parent::__construct(302, array('Location: ' . $location));
    }
} 
