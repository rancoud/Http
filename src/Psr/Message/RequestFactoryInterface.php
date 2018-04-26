<?php

namespace Psr\Http\Message;

interface RequestFactoryInterface
{
    public function createRequest($method, $uri);
}
