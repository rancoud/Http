<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request.
 */
class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /**
     * Request constructor.
     *
     * @param string $method
     * @param mixed  $uri
     * @param array  $headers
     * @param mixed  $body
     * @param string $version
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

        $this->method = $method;
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
