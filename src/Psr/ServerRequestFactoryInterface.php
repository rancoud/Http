<?php

namespace Psr\Http\Message;

interface ServerRequestFactoryInterface
{
    public function createServerRequest($method, $uri);
    public function createServerRequestFromArray(array $server);
}
