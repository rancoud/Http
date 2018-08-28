<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Rancoud\Http\Message\Stream;

/**
 * Class StreamFactory.
 */
class StreamFactory implements StreamFactoryInterface
{
    /** @var int */
    protected $defaultMaxBytesLength = 1048576;

    /**
     * @param resource|string|null $body
     *
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return null|StreamInterface|Stream
     */
    public function createStream($body = null)
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (\gettype($body) === 'resource') {
            return Stream::createFromResource($body);
        }

        if ($body !== null) {
            return Stream::create($body);
        }

        return Stream::create('');
    }

    /**
     * @param        $file
     * @param string $mode
     *
     * @throws InvalidArgumentException
     *
     * @return Stream
     */
    public function createStreamFromFile($file, $mode = 'r'): Stream
    {
        $resource = fopen($file, $mode);

        return Stream::createFromResource($resource);
    }

    /**
     * @param $resource
     *
     * @throws InvalidArgumentException
     *
     * @return Stream
     */
    public function createStreamFromResource($resource): Stream
    {
        return Stream::createFromResource($resource);
    }

    /**
     * @param StreamInterface $source
     * @param StreamInterface $dest
     * @param int             $maxLen
     */
    public function copyToStream(StreamInterface $source, StreamInterface $dest, $maxLen = -1): void
    {
        if ($maxLen === -1) {
            while ($source->eof() === false) {
                if (!$dest->write($source->read($this->defaultMaxBytesLength))) {
                    break;
                }
            }

            return;
        }

        $bytes = 0;
        while ($source->eof() === false) {
            $buf = $source->read($maxLen - $bytes);
            if (!($len = mb_strlen($buf))) {
                break;
            }
            $bytes += $len;
            $dest->write($buf);
            if ($bytes === $maxLen) {
                break;
            }
        }
    }
}
