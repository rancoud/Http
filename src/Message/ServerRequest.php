<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class ServerRequest.
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array */
    protected $attributes = [];

    /** @var array */
    protected $cookieParams = [];

    /** @var null|array|object */
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
     * @param        $method
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     * @param array  $serverParams
     *
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1',
        array $serverParams = []
    ) {
        $this->serverParams = $serverParams;

        parent::__construct($method, $uri, $headers, $body, $version);
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
     * @return ServerRequest
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
     * @return ServerRequest
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
     * @return ServerRequest
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * @return array|null|object
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @param $data
     *
     * @throws InvalidArgumentException
     *
     * @return ServerRequest
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
     * @param      $name
     * @param null $default
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

        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @param $name
     * @param $value
     *
     * @throws InvalidArgumentException
     *
     * @return ServerRequest
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
     * @param $name
     *
     * @throws InvalidArgumentException
     *
     * @return ServerRequest
     */
    public function withoutAttribute($name): self
    {
        if (!\is_string($name)) {
            throw new InvalidArgumentException('Name must be a string');
        }

        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * @param $data
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
