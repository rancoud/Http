<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Class UploadedFile.
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var int[] */
    protected static $errors = [
        \UPLOAD_ERR_OK,
        \UPLOAD_ERR_INI_SIZE,
        \UPLOAD_ERR_FORM_SIZE,
        \UPLOAD_ERR_PARTIAL,
        \UPLOAD_ERR_NO_FILE,
        \UPLOAD_ERR_NO_TMP_DIR,
        \UPLOAD_ERR_CANT_WRITE,
        \UPLOAD_ERR_EXTENSION
    ];

    /** @var string */
    protected $clientFilename;

    /** @var string */
    protected $clientMediaType;

    /** @var int */
    protected $error;

    /** @var null|string */
    protected $file;

    /** @var bool */
    protected $moved = false;

    /** @var null|int */
    protected $size;

    /** @var null|StreamInterface */
    protected $stream;

    /** @var int */
    protected $defaultMaxBytesLength = 1048576;

    /**
     * UploadedFile constructor.
     *
     * @param      $streamOrFile
     * @param      $size
     * @param      $errorStatus
     * @param null $clientFilename
     * @param null $clientMediaType
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $streamOrFile,
        $size,
        $errorStatus,
        $clientFilename = null,
        $clientMediaType = null
    ) {
        $this->setError($errorStatus);
        $this->setSize($size);
        $this->setClientFilename($clientFilename);
        $this->setClientMediaType($clientMediaType);

        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $resource = \fopen($this->file, 'r');

        return Stream::create($resource);
    }

    /**
     * @param $targetPath
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function moveTo($targetPath): void
    {
        $this->validateActive();

        if (!$this->isStringNotEmpty($targetPath)) {
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if ($this->file !== null) {
            if (\PHP_SAPI === 'cli') {
                $this->moved = \rename($this->file, $targetPath);
            } else {
                $this->moved = \move_uploaded_file($this->file, $targetPath);
            }
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable() === true) {
                $stream->rewind();
            }

            $this->copyToStream($stream, Stream::create(\fopen($targetPath, 'w')));
            $this->moved = true;
        }

        if (!$this->moved) {
            throw new RuntimeException(\sprintf('Uploaded file could not be moved to %s', $targetPath));
        }
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @return null|string
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * @return null|string
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * @param $streamOrFile
     *
     * @throws InvalidArgumentException
     */
    protected function setStreamOrFile($streamOrFile): void
    {
        if (\is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (\is_resource($streamOrFile)) {
            $this->stream = Stream::create($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    /**
     * @param $error
     *
     * @throws InvalidArgumentException
     */
    protected function setError($error): void
    {
        if (!\is_int($error)) {
            throw new InvalidArgumentException('Upload file error status must be an integer');
        }

        if (!\in_array($error, self::$errors, true)) {
            throw new \InvalidArgumentException('Invalid error status for UploadedFile');
        }

        $this->error = $error;
    }

    /**
     * @param $size
     *
     * @throws InvalidArgumentException
     */
    protected function setSize($size): void
    {
        if (!\is_int($size)) {
            throw new InvalidArgumentException('Upload file size must be an integer');
        }

        $this->size = $size;
    }

    /**
     * @param $param
     *
     * @return bool
     */
    protected function isStringOrNull($param): bool
    {
        return \in_array(\gettype($param), ['string', 'NULL'], true);
    }

    /**
     * @param $param
     *
     * @return bool
     */
    protected function isStringNotEmpty($param): bool
    {
        return \is_string($param) === true && $param !== '';
    }

    /**
     * @param $clientFilename
     *
     * @throws InvalidArgumentException
     */
    protected function setClientFilename($clientFilename): void
    {
        if (!$this->isStringOrNull($clientFilename)) {
            throw new InvalidArgumentException('Upload file client filename must be a string or null');
        }

        $this->clientFilename = $clientFilename;
    }

    /**
     * @param $clientMediaType
     *
     * @throws InvalidArgumentException
     */
    protected function setClientMediaType($clientMediaType): void
    {
        if (!$this->isStringOrNull($clientMediaType)) {
            throw new InvalidArgumentException('Upload file client media type must be a string or null');
        }

        $this->clientMediaType = $clientMediaType;
    }

    /**
     * @return bool
     */
    protected function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * @throws RuntimeException
     */
    protected function validateActive(): void
    {
        if (!$this->isOk()) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * @param StreamInterface $source
     * @param StreamInterface $dest
     * @param int             $maxLen
     */
    protected function copyToStream(StreamInterface $source, StreamInterface $dest, $maxLen = -1)
    {
        if ($maxLen === -1) {
            while (!$source->eof()) {
                if (!$dest->write($source->read($this->defaultMaxBytesLength))) {
                    break;
                }
            }

            return;
        }
        $bytes = 0;
        while (!$source->eof()) {
            $buf = $source->read($maxLen - $bytes);
            if (!($len = \mb_strlen($buf))) {
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
