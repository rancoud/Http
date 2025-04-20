<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /**
     * @param resource|StreamInterface|string|null $body
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        array $headers = [],
        mixed $body = null,
        string $version = '1.1'
    ) {
        $this->method = $this->filterMethod($method);

        if (($uri instanceof UriInterface) === false) {
            $uri = new Uri($uri);
        }
        $this->uri = $uri;

        $this->setHeaders($headers);
        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->stream = Stream::create($body);
        }

        $this->protocol = $this->validateProtocolVersion($version);
    }
}
