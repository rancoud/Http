<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest.
 */
class ServerRequest implements ServerRequestInterface
{
    use MessageTrait;
    use RequestTrait;

    protected array $attributes = [];

    protected array $cookieParams = [];

    /** @var array|object|null */
    protected $parsedBody;

    protected array $queryParams = [];

    protected array $serverParams;

    /** @var UploadedFileInterface[] */
    protected array $uploadedFiles = [];

    /**
     * @param string|UriInterface                  $uri
     * @param resource|StreamInterface|string|null $body
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
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

        $this->serverParams = $serverParams;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /** @return array|object|null */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @param array|object|null $data
     *
     * @throws \InvalidArgumentException
     */
    public function withParsedBody($data): self
    {
        $this->validateData($data);

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param  mixed|null                $default
     * @throws \InvalidArgumentException
     * @return mixed|null
     */
    public function getAttribute(string $name, $default = null)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withAttribute(string $name, $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /** @throws \InvalidArgumentException */
    public function withoutAttribute(string $name): self
    {
        if (!\array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function validateData($data): void
    {
        if (!\is_array($data) && !\is_object($data) && $data !== null) {
            throw new \InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
        }
    }
}
