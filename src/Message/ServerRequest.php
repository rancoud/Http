<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest.
 */
class ServerRequest implements ServerRequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /** @var array */
    protected $attributes = [];

    /** @var array */
    protected $cookieParams = [];

    /** @var array|object|null */
    protected $parsedBody;

    /** @var array */
    protected $queryParams = [];

    /** @var array */
    protected $serverParams;

    /** @var UploadedFileInterface[] */
    protected $uploadedFiles = [];

    /**
     * ServerRequest constructor.
     *
     * @param string $method
     * @param mixed  $uri
     * @param array  $headers
     * @param mixed  $body
     * @param string $version
     * @param array  $serverParams
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
        $this->serverParams = $serverParams;

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

    /**
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param array $cookies
     *
     * @return self
     */
    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param array $query
     *
     * @return self
     */
    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array $uploadedFiles
     *
     * @return self
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * @return array|object|null
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @param array|object|null $data
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public function withParsedBody($data): self
    {
        $this->validateData($data);

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @throws InvalidArgumentException
     *
     * @return mixed|null
     */
    public function getAttribute($name, $default = null)
    {
        if (!\is_string($name)) {
            throw new InvalidArgumentException('Name must be a string');
        }

        if (!\array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public function withAttribute($name, $value): self
    {
        if (!\is_string($name)) {
            throw new InvalidArgumentException('Name must be a string');
        }

        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public function withoutAttribute($name): self
    {
        if (!\is_string($name)) {
            throw new InvalidArgumentException('Name must be a string');
        }

        if (!\array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * @param mixed $data
     *
     * @throws InvalidArgumentException
     */
    protected function validateData($data): void
    {
        if (!\is_array($data) && !\is_object($data) && $data !== null) {
            throw new InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
        }
    }
}
