<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * Trait RequestTrait.
 */
trait RequestTrait
{
    public static array $methods = [
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

    protected string $method;

    protected ?string $requestTarget = null;

    protected ?UriInterface $uri = null;

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        $query = $this->uri->getQuery();

        if ($target === '') {
            $target = '/';
        }

        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    /**
     * @param string $requestTarget
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withRequestTarget(string $requestTarget): self
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
    public function withMethod(string $method): self
    {
        $method = $this->filterMethod($method);

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
    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        if ($this->uri === $uri) {
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
     *
     * @return string
     */
    protected function filterMethod(string $method): string
    {
        if (!\in_array($method, static::$methods, true)) {
            throw new \InvalidArgumentException(\sprintf('Method %s is invalid', $method));
        }

        return $method;
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
