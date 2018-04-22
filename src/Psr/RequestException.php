<?php

namespace Psr\Http\Client\Exception;

use Psr\Http\Client\ClientException;
use Psr\Http\Message\RequestInterface;

interface RequestException extends ClientException
{
    public function getRequest(): RequestInterface;
}
