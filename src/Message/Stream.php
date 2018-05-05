<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

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

    /** @var array|mixed|null|void */
    protected $uri;

    /** @var int */
    protected $size;

    /** @var array Hash of readable and writable stream types */
    protected static $readWriteHash = [
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
        } catch (Exception $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * @return null|resource
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
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (is_array($stats) && array_key_exists('size', $stats)) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    /**
     * @throws RuntimeException
     *
     * @return int
     */
    public function tell(): int
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function eof(): bool
    {
        return $this->stream === false || feof($this->stream) === true;
    }

    /**
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @param     $offset
     * @param int $whence
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Offset must be a int');
        }

        if (!is_int($whence)) {
            throw new InvalidArgumentException('Whence must be a int');
        }

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            $whenceStr = var_export($whence, true);
            $message = sprintf('Unable to seek to stream position %d with whence %d', $offset, $whenceStr);
            throw new RuntimeException($message);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
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
     * @param $string
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return bool|int
     */
    public function write($string)
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Data must be a string');
        }

        if (!$this->writable) {
            throw new RuntimeException('Cannot write to a non-writable stream');
        }

        $this->size = null;
        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
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
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return string
     */
    public function read($length): string
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('Length must be a int');
        }

        if (!$this->readable) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        return fread($this->stream, $length);
    }

    /**
     * @throws RuntimeException
     *
     * @return string
     */
    public function getContents(): string
    {
        if (isset($this->stream) === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @param null $key
     *
     * @throws InvalidArgumentException
     *
     * @return array|null
     */
    public function getMetadata($key = null)
    {
        if (!$this->isStringOrNull($key)) {
            throw new InvalidArgumentException('Key must be a string or NULL');
        }

        if (!isset($this->stream)) {
            if ($key) {
                return null;
            }

            return [];
        } elseif ($key === null) {
            return stream_get_meta_data($this->stream);
        }

        $meta = stream_get_meta_data($this->stream);

        if (!array_key_exists($key, $meta)) {
            return null;
        }

        return $meta[$key];
    }

    /**
     * @param $resource
     *
     * @throws InvalidArgumentException
     *
     * @return Stream
     */
    public static function createFromResource($resource): self
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        $obj = new self();
        $obj->stream = $resource;
        $meta = stream_get_meta_data($obj->stream);
        $obj->seekable = $meta['seekable'];
        $obj->readable = isset(self::$readWriteHash['read'][$meta['mode']]);
        $obj->writable = isset(self::$readWriteHash['write'][$meta['mode']]);
        $obj->uri = $obj->getMetadata('uri');

        return $obj;
    }

    /**
     * @param string $content
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return Stream
     */
    public static function create(string $content): self
    {
        $resource = fopen('php://temp', 'rw+');
        $stream = self::createFromResource($resource);
        $stream->write($content);

        return $stream;
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
        return in_array(gettype($param), ['string', 'NULL'], true);
    }
}
