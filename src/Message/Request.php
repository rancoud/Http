<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Rancoud\Http\Message\Factory\StreamFactory;

/**
 * Class Request.
 */
class Request implements RequestInterface
{
    use Message;

    protected static $methods = [
        'ACL',
        'BASELINE-CONTROL',
        'BCOPY',
        'BDELETE',
        'BIND',
        'BMOVE',
        'BPROPFIND',
        'BPROPPATCH',
        'CHECKIN',
        'CHECKOUT',
        'CONNECT',
        'COPY',
        'DELETE',
        'GET',
        'HEAD',
        'LABEL',
        'LINK',
        'LOCK',
        'M-SEARCH',
        'MERGE',
        'MKACTIVITY',
        'MKCALENDAR',
        'MKCOL',
        'MKREDIRECTREF',
        'MKWORKSPACE',
        'MOVE',
        'NOTIFY',
        'OPTIONS',
        'ORDERPATCH',
        'PATCH',
        'POLL',
        'POST',
        'PRI',
        'PROPFIND',
        'PROPPATCH',
        'PURGE',
        'PUT',
        'REBIND',
        'REPORT',
        'SEARCH',
        'SUBSCRIBE',
        'TRACE',
        'UNBIND',
        'UNCHECKOUT',
        'UNLINK',
        'UNLOCK',
        'UNSUBSCRIBE',
        'UPDATE',
        'UPDATEREDIRECTREF',
        'VERSION-CONTROL',
        'VIEW',
        'X-MS-ENUMATTS'
    ];

    /** @var string */
    protected $method;

    /** @var null|string */
    protected $requestTarget;

    /** @var UriInterface */
    protected $uri;

    /**
     * Request constructor.
     *
     * @param        $method
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ) {
        if (($uri instanceof UriInterface) === false) {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $this->validateProtocolVersion($version);

        if ($this->hasHeader('Host') === false) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->stream = (new StreamFactory())->createStream($body);
        }
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * @param $requestTarget
     *
     * @throws InvalidArgumentException
     *
     * @return Request
     */
    public function withRequestTarget($requestTarget): self
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param $method
     *
     * @throws InvalidArgumentException
     *
     * @return Request
     */
    public function withMethod($method): self
    {
        $this->filterMethod($method);

        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param bool         $preserveHost
     *
     * @throws InvalidArgumentException
     *
     * @return Request
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if (is_bool($preserveHost) === false) {
            throw new InvalidArgumentException('Preserve Host must be a boolean');
        }

        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost === false || $this->hasHeader('Host') === false) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    /**
     * @param $method
     *
     * @throws InvalidArgumentException
     */
    protected function filterMethod($method): void
    {
        if (is_string($method) === false) {
            throw new InvalidArgumentException('Method must be a string');
        }

        if (in_array($method, self::$methods, true) === false) {
            throw new InvalidArgumentException(sprintf('Method %s is invalid', $method));
        }
    }

    protected function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return;
        }

        $port = $this->uri->getPort();
        if ($port !== null) {
            $host .= ':' . $port;
        }

        if (array_key_exists('host', $this->headerNames) === true) {
            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }

        $this->headers = [$header => [$host]] + $this->headers;
    }
}
