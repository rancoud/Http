<?php

/** @noinspection FopenBinaryUnsafeUsageInspection */

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /** @var array */
    protected const array READ_WRITE_HASH = [
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

    /** @var resource|null */
    protected $stream;

    protected bool $seekable;

    protected bool $readable;

    protected bool $writable;

    /** @var array|bool|mixed|void|null */
    protected $uri;

    protected ?int $size = null;

    private function __construct() {}

    public function __destruct()
    {
        $this->close();
    }

    /** @throws \Throwable */
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

    /** @return resource|null */
    public function detach()
    {
        if (!isset($this->stream)) {
            return;
        }

        $result = $this->stream;
        $this->stream = null;
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

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
        // Could not reach this statement without mocking the filesystem
        return null;
        // @codeCoverageIgnoreEnd
    }

    /** @throws \RuntimeException */
    public function tell(): int
    {
        $positionInFile = \ftell($this->stream);

        if ($positionInFile === false) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            throw new \RuntimeException('Unable to determine stream position');
            // @codeCoverageIgnoreEnd
        }

        return $positionInFile;
    }

    public function eof(): bool
    {
        return $this->stream === false || \feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function seek(int $offset, int $whence = \SEEK_SET): void
    {
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

    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function write(string $string): int
    {
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $this->size = null;
        $bytesWritten = \fwrite($this->stream, $string);

        if ($bytesWritten === false) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            throw new \RuntimeException('Unable to write to stream');
            // @codeCoverageIgnoreEnd
        }

        return $bytesWritten;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function read(int $length): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        $contents = \fread($this->stream, $length);

        if ($contents === false) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            throw new \RuntimeException('Unable to read from stream');
            // @codeCoverageIgnoreEnd
        }

        return $contents;
    }

    /**
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        $exception = null;

        \set_error_handler(static function ($type, $message) use (&$exception): void {
            throw $exception = new \RuntimeException('Unable to read stream contents: ' . $message);
        });

        try {
            $contents = \stream_get_contents($this->stream);

            if ($contents === false) {
                // @codeCoverageIgnoreStart
                /* Could not reach this statement without changing php-src
                 * @info: https://github.com/php/php-src/blob/311cae03e730c76aed343312319ed8cf1c37ade0/main/streams/streams.c#L1512
                 */
                $exception = new \RuntimeException('Unable to read stream contents');
                // @codeCoverageIgnoreEnd
            }
        } catch (\Throwable $e) {
            $exception = new \RuntimeException('Unable to read stream contents: ' . $e->getMessage(), 0, $e);
        }

        \restore_error_handler();

        if ($exception) {
            throw $exception;
        }

        return $contents;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return array|mixed|null
     */
    public function getMetadata(?string $key = null)
    {
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
     * @param resource|StreamInterface|string $content
     *
     * @throws \InvalidArgumentException
     */
    public static function create(mixed $content = ''): StreamInterface
    {
        if ($content instanceof StreamInterface) {
            return $content;
        }

        if (\is_string($content)) {
            $resource = \fopen('php://temp', 'rw+');
            if ($resource === false) {
                // @codeCoverageIgnoreStart
                // Could not reach this statement without mocking the filesystem
                throw new \InvalidArgumentException('Error fopen in php://temp');
                // @codeCoverageIgnoreEnd
            }
            $bytesWritten = \fwrite($resource, $content);
            if ($bytesWritten === false) {
                // @codeCoverageIgnoreStart
                // Could not reach this statement without mocking the filesystem
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
        } catch (\Throwable) {
            // Could not reach this statement without mocking the filesystem
            throw new \RuntimeException(\sprintf('The file %s cannot be opened.', $filename));
            // @codeCoverageIgnoreEnd
        }

        if ($resource === false) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            throw new \RuntimeException(\sprintf('The file %s cannot be opened.', $filename));
            // @codeCoverageIgnoreEnd
        }

        return static::create($resource);
    }
}
