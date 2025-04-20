<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    protected static string $patternHeaderName = "@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@";

    protected static string $patternHeaderValue = "@^[ \t\x21-\x7E\x80-\xFF]*$@";

    protected static array $validProtocols = ['0.9', '1.0', '1.1', '2', '2.0', '3'];

    protected array $headers = [];

    protected array $headerNames = [];

    protected string $protocol = '1.1';

    protected ?StreamInterface $stream = null;

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /** @throws \InvalidArgumentException */
    public function withProtocolVersion(string $version): self
    {
        $this->validateProtocolVersion($version);

        if ($this->protocol === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** @throws \InvalidArgumentException */
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[\mb_strtolower($name)]);
    }

    /** @throws \InvalidArgumentException */
    public function getHeader(string $name): array
    {
        $name = \mb_strtolower($name);

        if (!isset($this->headerNames[$name])) {
            return [];
        }

        $name = $this->headerNames[$name];

        return $this->headers[$name];
    }

    /** @throws \InvalidArgumentException */
    public function getHeaderLine(string $name): string
    {
        return \implode(', ', $this->getHeader($name));
    }

    /**
     * @param array|float|int|string $value
     *
     * @throws \InvalidArgumentException
     */
    public function withHeader(string $name, $value): self
    {
        if ($name === '') {
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
     * @param array|float|int|string $value
     *
     * @throws \InvalidArgumentException
     */
    public function withAddedHeader(string $name, $value): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Header name must be non-empty string');
        }

        $new = clone $this;
        $new->setHeaders([$name => $value]);

        return $new;
    }

    /** @throws \InvalidArgumentException */
    public function withoutHeader(string $name): self
    {
        if ($name === '') {
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

    /** @throws \InvalidArgumentException */
    public function getBody(): StreamInterface
    {
        if ($this->stream === null) {
            $this->stream = Stream::create();
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body): self
    {
        if ($this->stream === $body) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
    protected function validateAndTrimHeader(array|string $header, $values): array
    {
        if (!\is_string($header) || \preg_match(static::$patternHeaderName, $header) !== 1) {
            throw new \InvalidArgumentException('Header name must be RFC 7230 compatible string.');
        }

        if (!\is_array($values)) {
            if ((!\is_numeric($values) && !\is_string($values)) || \preg_match(static::$patternHeaderValue, (string) $values) !== 1) {
                throw new \InvalidArgumentException('Header value must be RFC 7230 compatible string.');
            }

            return [\mb_trim((string) $values, " \t")];
        }

        if (empty($values)) {
            throw new \InvalidArgumentException('Header values must be a string or an array of strings, empty array given.');
        }

        $returnValues = [];
        foreach ($values as $v) {
            if ((!\is_numeric($v) && !\is_string($v)) || \preg_match(static::$patternHeaderValue, (string) $v) !== 1) {
                throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }

            $returnValues[] = \mb_trim((string) $v, " \t");
        }

        return $returnValues;
    }

    /** @throws \InvalidArgumentException */
    protected function validateProtocolVersion(string $protocolVersion): string
    {
        if (!\in_array($protocolVersion, static::$validProtocols, true)) {
            throw new \InvalidArgumentException('Protocol Version must be ' . \implode(' or ', static::$validProtocols));
        }

        return $protocolVersion;
    }
}
