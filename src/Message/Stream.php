<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * Class Stream.
 */
class Stream implements StreamInterface
{
    /** @var resource */
    protected $stream;

    /** @var bool */
    protected $seekable;

    /** @var bool */
    protected $readable;

    /** @var bool */
    protected $writable;

    /** @var array|mixed|void|null */
    protected $uri;

    /** @var int|null */
    protected $size;

    /** @var array Hash of readable and writable stream types */
    protected const READ_WRITE_HASH = [
        'read' => [
            'r'   => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb'  => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w'   => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+'  => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    private function __construct()
    {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }

            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            if (\is_resource($this->stream)) {
                \fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        if ($this->uri) {
            \clearstatcache(true, $this->uri);
        }

        $stats = \fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    /**
     * @throws \RuntimeException
     *
     * @return int
     */
    public function tell(): int
    {
        $result = \ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function eof(): bool
    {
        return $this->stream === false || \feof($this->stream) === true;
    }

    /**
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @param int $offset
     * @param int $whence
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function seek($offset, $whence = \SEEK_SET): void
    {
        if (!\is_int($offset)) {
            throw new \InvalidArgumentException('Offset must be a int');
        }

        if (!\is_int($whence)) {
            throw new \InvalidArgumentException('Whence must be a int');
        }

        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (\fseek($this->stream, $offset, $whence) === -1) {
            $whenceStr = \var_export($whence, true);
            $message = \sprintf('Unable to seek to stream position %d with whence %d', $offset, $whenceStr);
            throw new \RuntimeException($message);
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @param string $string
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return bool|int
     */
    public function write($string)
    {
        if (!\is_string($string)) {
            throw new \InvalidArgumentException('Data must be a string');
        }

        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $this->size = null;
        $result = \fwrite($this->stream, $string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * @param $length
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return string
     */
    public function read($length): string
    {
        if (!\is_int($length)) {
            throw new \InvalidArgumentException('Length must be a int');
        }

        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        return \fread($this->stream, $length);
    }

    /**
     * @throws \RuntimeException
     *
     * @return string
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        $contents = \stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @param string|null $key
     *
     * @throws \InvalidArgumentException
     *
     * @return array|null
     */
    public function getMetadata($key = null)
    {
        if (!$this->isStringOrNull($key)) {
            throw new \InvalidArgumentException('Key must be a string or NULL');
        }

        if (!isset($this->stream)) {
            if ($key) {
                return null;
            }

            return [];
        }

        $meta = \stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * @param string|resource|StreamInterface $content
     *
     * @throws \InvalidArgumentException
     *
     * @return StreamInterface
     */
    public static function create($content = ''): StreamInterface
    {
        if ($content instanceof StreamInterface) {
            return $content;
        }

        if (\is_string($content)) {
            $resource = \fopen('php://temp', 'rw+');
            \fwrite($resource, $content);
            $content = $resource;
        }

        if (\is_resource($content)) {
            $obj = new self();
            $obj->stream = $content;
            $meta = \stream_get_meta_data($obj->stream);
            $obj->seekable = $meta['seekable'];
            $obj->readable = isset(static::READ_WRITE_HASH['read'][$meta['mode']]);
            $obj->writable = isset(static::READ_WRITE_HASH['write'][$meta['mode']]);
            $obj->uri = $obj->getMetadata('uri');

            return $obj;
        }

        throw new \InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface.');
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param $param
     *
     * @return bool
     */
    protected function isStringOrNull($param): bool
    {
        return $param === null || \is_string($param);
    }
}
