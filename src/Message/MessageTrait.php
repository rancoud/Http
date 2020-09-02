<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * Trait MessageTrait.
 */
trait MessageTrait
{
    /** @var string */
    protected static string $patternHeaderName = "@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@";

    /** @var string */
    protected static string $patternHeaderValue = "@^[ \t\x21-\x7E\x80-\xFF]*$@";

    /** @var array */
    protected static array $validProtocols = ['0.9', '1.0', '1.1', '2', '2.0'];

    /** @var array */
    protected array $headers = [];

    /** @var array */
    protected array $headerNames = [];

    /** @var string */
    protected string $protocol = '1.1';

    /** @var StreamInterface|null */
    protected ?StreamInterface $stream = null;

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $version
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withProtocolVersion($version): self
    {
        $this->validateProtocolVersion($version);

        if ($this->protocol === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    public function hasHeader($name): bool
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException('Header name must be a string');
        }

        return isset($this->headerNames[\mb_strtolower($name)]);
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function getHeader($name): array
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException('Header name must be a string');
        }

        $name = \mb_strtolower($name);

        if (!isset($this->headerNames[$name])) {
            return [];
        }

        $name = $this->headerNames[$name];

        return $this->headers[$name];
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getHeaderLine($name): string
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException('Header name must be a string');
        }

        return \implode(', ', $this->getHeader($name));
    }

    /**
     * @param string                 $name
     * @param string|int|float|array $value
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withHeader($name, $value): self
    {
        if (!\is_string($name) || $name === '') {
            throw new \InvalidArgumentException('Header name must be non-empty string');
        }

        $value = $this->validateAndTrimHeader($name, $value);
        $normalized = \mb_strtolower($name);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }
        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;

        return $new;
    }

    /**
     * @param string                 $name
     * @param string|int|float|array $value
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withAddedHeader($name, $value): self
    {
        if (!\is_string($name) || $name === '') {
            throw new \InvalidArgumentException('Header name must be non-empty string');
        }

        $new = clone $this;
        $new->setHeaders([$name => $value]);

        return $new;
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withoutHeader($name): self
    {
        if (!\is_string($name) || $name === '') {
            throw new \InvalidArgumentException('Header name must be non-empty string');
        }

        $normalized = \mb_strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $name = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$name], $new->headerNames[$normalized]);

        return $new;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        if ($this->stream === null) {
            $this->stream = Stream::create('');
        }

        return $this->stream;
    }

    /**
     * @param StreamInterface $body
     *
     * @return self
     */
    public function withBody(StreamInterface $body): self
    {
        if ($this->stream === $body) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    /**
     * @param array $headers
     *
     * @throws \InvalidArgumentException
     */
    protected function setHeaders(array $headers): void
    {
        foreach ($headers as $header => $value) {
            $value = $this->validateAndTrimHeader($header, $value);
            $normalized = \mb_strtolower($header);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = \array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
    }

    /**
     * @param string $header
     * @param mixed  $values
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function validateAndTrimHeader($header, $values): array
    {
        if (!\is_string($header) || \preg_match(static::$patternHeaderName, $header) !== 1) {
            throw new \InvalidArgumentException('Header name must be RFC 7230 compatible string.');
        }

        if (!\is_array($values)) {
            if ((!\is_numeric($values) && !\is_string($values)) || \preg_match(static::$patternHeaderValue, (string) $values) !== 1) {
                throw new \InvalidArgumentException('Header value must be RFC 7230 compatible string.');
            }

            return [\trim((string) $values, " \t")];
        }

        if (empty($values)) {
            throw new \InvalidArgumentException('Header values must be a string or an array of strings, empty array given.');
        }

        $returnValues = [];
        foreach ($values as $v) {
            if ((!\is_numeric($v) && !\is_string($v)) || \preg_match(static::$patternHeaderValue, (string) $v) !== 1) {
                throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }

            $returnValues[] = \trim((string) $v, " \t");
        }

        return $returnValues;
    }

    /**
     * @param string $protocolVersion
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function validateProtocolVersion(string $protocolVersion): string
    {
        if (!\in_array($protocolVersion, static::$validProtocols, true)) {
            throw new \InvalidArgumentException('Protocol Version must be ' . \implode(' or ', static::$validProtocols));
        }

        return $protocolVersion;
    }
}
