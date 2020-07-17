<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};

/**
 * Class Request.
 */
class Request implements RequestInterface
{
    use RequestTrait;

    /**
     * @param string                               $method  HTTP method
     * @param string|UriInterface                  $uri     URI
     * @param array                                $headers Request headers
     * @param string|resource|StreamInterface|null $body    Request body
     * @param string                               $version Protocol version
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
        if (($uri instanceof UriInterface) === false) {
            $uri = new Uri($uri);
        }

        $this->method = $this->filterMethod($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $this->validateProtocolVersion($version);

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->stream = Stream::create($body);
        }
    }
}
