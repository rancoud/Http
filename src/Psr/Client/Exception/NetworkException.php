<?php

namespace Psr\Http\Client\Exception;

use Psr\Http\Client\ClientException;
use Psr\Http\Message\RequestInterface;

interface NetworkException extends ClientException
{
    public function getRequest(): RequestInterface;
}
