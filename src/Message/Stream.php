<?php

/** @noinspection FopenBinaryUnsafeUsageInspection */

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * Class Stream.
 */
class Stream implements StreamInterface
{
    /** @var resource|null */
    protected $stream;

    protected bool $seekable;

    protected bool $readable;

    protected bool $writable;

    /** @var array|mixed|void|bool|null */
    protected $uri;

    protected ?int $size = null;

    /** @var array */
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
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
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

        // @codeCoverageIgnoreStart
        /* Could not reach this statement without mocking the filesystem
         */
        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws \RuntimeException
     *
     * @return int
     */
    public function tell(): int
    {
        $positionInFile = \ftell($this->stream);

        if ($positionInFile === false) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the filesystem
             */
            throw new \RuntimeException('Unable to determine stream position');
            // @codeCoverageIgnoreEnd
        }

        return $positionInFile;
    }

    /**
     * @return bool
     */
    public function eof(): bool
    {
        return $this->stream === false || \feof($this->stream);
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
        }

        if (\fseek($this->stream, $offset, $whence) === -1) {
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
     * @return int
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
        $bytesWritten = \fwrite($this->stream, $string);

        if ($bytesWritten === false) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the filesystem
             */
            throw new \RuntimeException('Unable to write to stream');
            // @codeCoverageIgnoreEnd
        }

        return $bytesWritten;
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

        $contents = \fread($this->stream, $length);

        if ($contents === false) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the filesystem
             */
            throw new \RuntimeException('Unable to read from stream');
            // @codeCoverageIgnoreEnd
        }

        return $contents;
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
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the filesystem
             */
            throw new \RuntimeException('Unable to read stream contents');
            // @codeCoverageIgnoreEnd
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
            return $key ? null : [];
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
            if ($resource === false) {
                // @codeCoverageIgnoreStart
                /* Could not reach this statement without mocking the filesystem
                 */
                throw new \InvalidArgumentException('Error fopen in php://temp');
                // @codeCoverageIgnoreEnd
            }
            $bytesWritten = \fwrite($resource, $content);
            if ($bytesWritten === false) {
                // @codeCoverageIgnoreStart
                /* Could not reach this statement without mocking the filesystem
                 */
                throw new \InvalidArgumentException('Error fwrite in php://temp');
                // @codeCoverageIgnoreEnd
            }
            $content = $resource;
        }

        if (!\is_resource($content)) {
            throw new \InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface.');
        }

        $obj = new self();
        $obj->stream = $content;
        $meta = \stream_get_meta_data($obj->stream);
        $obj->seekable = $meta['seekable'] && \fseek($obj->stream, 0, \SEEK_CUR) === 0;
        $obj->readable = isset(static::READ_WRITE_HASH['read'][$meta['mode']]);
        $obj->writable = isset(static::READ_WRITE_HASH['write'][$meta['mode']]);
        $obj->uri = $meta['uri'] ?? null;

        return $obj;
    }

    /**
     * @param string $filename
     * @param string $mode
     *
     * @return StreamInterface
     */
    public static function createFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!\file_exists($filename)) {
            throw new \RuntimeException(\sprintf('The file %s doesn\'t exist.', $filename));
        }

        if (!\in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
            throw new \InvalidArgumentException(\sprintf('The mode %s is invalid.', $mode));
        }

        try {
            $resource = \fopen($filename, $mode);
            // @codeCoverageIgnoreStart
        } catch (\Throwable $e) {
            /* Could not reach this statement without mocking the filesystem
             */
            throw new \RuntimeException(\sprintf('The file %s cannot be opened.', $filename));
            // @codeCoverageIgnoreEnd
        }

        if ($resource === false) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the filesystem
             */
            throw new \RuntimeException(\sprintf('The file %s cannot be opened.', $filename));
            // @codeCoverageIgnoreEnd
        }

        return static::create($resource);
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
