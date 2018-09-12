<?php

namespace Psr\Http\Message;

use Psr\Http\Message\UriInterface;

interface UriFactoryInterface
{
    public function createUri(string $uri = '') : UriInterface;
}
