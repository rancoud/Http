<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\{StreamInterface, UploadedFileInterface};

/**
 * Class UploadedFile.
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var array */
    protected const ERRORS = [
        \UPLOAD_ERR_OK         => 1,
        \UPLOAD_ERR_INI_SIZE   => 1,
        \UPLOAD_ERR_FORM_SIZE  => 1,
        \UPLOAD_ERR_PARTIAL    => 1,
        \UPLOAD_ERR_NO_FILE    => 1,
        \UPLOAD_ERR_NO_TMP_DIR => 1,
        \UPLOAD_ERR_CANT_WRITE => 1,
        \UPLOAD_ERR_EXTENSION  => 1,
    ];

    /** @var int */
    protected const DEFAULT_MAX_BYTES_LENGTH = 1048576;

    /** @var string|null */
    protected ?string $clientFilename;

    /** @var string|null */
    protected ?string $clientMediaType;

    /** @var int */
    protected int $error;

    /** @var string|null */
    protected ?string $file = null;

    /** @var bool */
    protected bool $moved = false;

    /** @var int|null */
    protected ?int $size = null;

    /** @var StreamInterface|null */
    protected ?StreamInterface $stream = null;

    /**
     * @param StreamInterface|string|resource $streamOrFile
     * @param int                             $size
     * @param int                             $errorStatus
     * @param string|null                     $clientFilename
     * @param string|null                     $clientMediaType
     *
     * @throws \InvalidArgumentException
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

        if ($this->isUploadSuccess()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
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
     * @param string $targetPath
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function moveTo($targetPath): void
    {
        $this->validateActive();

        if (!$this->isStringNotEmpty($targetPath)) {
            throw new \InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if ($this->file !== null) {
            if (\PHP_SAPI === 'cli') {
                $this->moved = \rename($this->file, $targetPath);
            } else {
                $this->moved = \move_uploaded_file($this->file, $targetPath);
            }
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            $destination = Stream::create(\fopen($targetPath, 'w'));
            $this->copyToStream($stream, $destination);
            $this->moved = true;
        }

        if (!$this->moved) {
            throw new \RuntimeException(\sprintf('Uploaded file could not be moved to %s', $targetPath));
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
     * @return string|null
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * @return string|null
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * @param $streamOrFile
     *
     * @throws \InvalidArgumentException
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
            throw new \InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    /**
     * @param int $error
     *
     * @throws \InvalidArgumentException
     */
    protected function setError($error): void
    {
        if (!\is_int($error)) {
            throw new \InvalidArgumentException('Upload file error status must be an integer');
        }

        if (!isset(static::ERRORS[$error])) {
            throw new \InvalidArgumentException('Invalid error status for UploadedFile');
        }

        $this->error = $error;
    }

    /**
     * @param int $size
     *
     * @throws \InvalidArgumentException
     */
    protected function setSize($size): void
    {
        if (!\is_int($size)) {
            throw new \InvalidArgumentException('Upload file size must be an integer');
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
        return $param === null || \is_string($param);
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
     * @param string|null $clientFilename
     *
     * @throws \InvalidArgumentException
     */
    protected function setClientFilename($clientFilename): void
    {
        if (!$this->isStringOrNull($clientFilename)) {
            throw new \InvalidArgumentException('Upload file client filename must be a string or null');
        }

        $this->clientFilename = $clientFilename;
    }

    /**
     * @param string|null $clientMediaType
     *
     * @throws \InvalidArgumentException
     */
    protected function setClientMediaType($clientMediaType): void
    {
        if (!$this->isStringOrNull($clientMediaType)) {
            throw new \InvalidArgumentException('Upload file client media type must be a string or null');
        }

        $this->clientMediaType = $clientMediaType;
    }

    /**
     * @return bool
     */
    protected function isUploadSuccess(): bool
    {
        return $this->error === \UPLOAD_ERR_OK;
    }

    /**
     * @throws \RuntimeException
     */
    protected function validateActive(): void
    {
        if (!$this->isUploadSuccess()) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * @param StreamInterface $source
     * @param StreamInterface $dest
     *
     * @throws \RuntimeException
     */
    protected function copyToStream(StreamInterface $source, StreamInterface $dest): void
    {
        while (!$source->eof()) {
            if (!$dest->write($source->read(static::DEFAULT_MAX_BYTES_LENGTH))) {
                break;
            }
        }
    }
}
