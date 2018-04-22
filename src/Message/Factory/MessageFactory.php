<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Response;

/**
 * Class MessageFactory.
 */
class MessageFactory implements RequestFactoryInterface, ResponseFactoryInterface
{
    /**
     * @param        $method
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $protocolVersion
     *
     * @return RequestInterface
     */
    public function createRequest(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ): RequestInterface {
        return new Request($method, $uri, $headers, $body, $protocolVersion);
    }

    /**
     * @param int    $statusCode
     * @param null   $reasonPhrase
     * @param array  $headers
     * @param null   $body
     * @param string $protocolVersion
     *
     * @return ResponseInterface
     */
    public function createResponse(
        $statusCode = 200,
        $reasonPhrase = null,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ): ResponseInterface {
        return new Response((int) $statusCode, $headers, $body, $protocolVersion, $reasonPhrase);
    }
}
