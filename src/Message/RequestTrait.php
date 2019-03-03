<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * Trait RequestTrait.
 */
trait RequestTrait
{
    public static $methods = [
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

    /** @var string|null */
    protected $requestTarget;

    /** @var UriInterface|null */
    protected $uri;

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
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withRequestTarget($requestTarget): self
    {
        if (\preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
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
     * @param string $method
     *
     * @throws \InvalidArgumentException
     *
     * @return self
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
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if (!\is_bool($preserveHost)) {
            throw new \InvalidArgumentException('Preserve Host must be a boolean');
        }

        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    /**
     * @param string $method
     *
     * @throws \InvalidArgumentException
     */
    protected function filterMethod($method): void
    {
        if (!\is_string($method)) {
            throw new \InvalidArgumentException('Method must be a string');
        }

        if (!\in_array($method, static::$methods, true)) {
            throw new \InvalidArgumentException(\sprintf('Method %s is invalid', $method));
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

        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }

        $this->headers = [$header => [$host]] + $this->headers;
    }
}
