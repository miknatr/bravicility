<?php

namespace Bravicility\Http\Response;

class RedirectResponse extends Response
{
    public function __construct($location)
    {
        if (!preg_match('#^https?://#', $location)) {
            throw new \LogicException('Location must be absolute');
        }

        parent::__construct(302, array('Location: ' . $location));
    }
}
