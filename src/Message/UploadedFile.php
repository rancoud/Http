<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class UploadedFile.
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var int[] */
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

    protected ?string $clientFilename;

    protected ?string $clientMediaType;

    protected int $error;

    protected ?string $file = null;

    protected bool $moved = false;

    protected ?int $size = null;

    protected ?StreamInterface $stream = null;

    /**
     * @param resource|StreamInterface|string $streamOrFile
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $streamOrFile,
        ?int $size,
        int $errorStatus,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
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
     */
    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        return Stream::createFromFile($this->file);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function moveTo(string $targetPath): void
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

            $destination = Stream::createFromFile($targetPath, 'w');
            $this->copyToStream($stream, $destination);
            $this->moved = true;
        }

        if (!$this->moved) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            throw new \RuntimeException(\sprintf('Uploaded file could not be moved to %s', $targetPath));
            // @codeCoverageIgnoreEnd
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    public function getFilename(): ?string
    {
        return $this->file;
    }

    /**
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

    /** @throws \InvalidArgumentException */
    protected function setError(int $error): void
    {
        if (!isset(static::ERRORS[$error])) {
            throw new \InvalidArgumentException('Invalid error status for UploadedFile');
        }

        $this->error = $error;
    }

    /** @throws \InvalidArgumentException */
    protected function setSize(?int $size): void
    {
        $this->size = $size;
    }

    protected function isStringNotEmpty($param): bool
    {
        return \is_string($param) === true && $param !== '';
    }

    /** @throws \InvalidArgumentException */
    protected function setClientFilename(?string $clientFilename): void
    {
        $this->clientFilename = $clientFilename;
    }

    /** @throws \InvalidArgumentException */
    protected function setClientMediaType(?string $clientMediaType): void
    {
        $this->clientMediaType = $clientMediaType;
    }

    protected function isUploadSuccess(): bool
    {
        return $this->error === \UPLOAD_ERR_OK;
    }

    /** @throws \RuntimeException */
    protected function validateActive(): void
    {
        if (!$this->isUploadSuccess()) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /** @throws \RuntimeException */
    protected function copyToStream(StreamInterface $source, StreamInterface $dest): void
    {
        while (!$source->eof()) {
            if (!$dest->write($source->read(static::DEFAULT_MAX_BYTES_LENGTH))) {
                // @codeCoverageIgnoreStart
                // Could not reach this statement without mocking the filesystem
                break;
                // @codeCoverageIgnoreEnd
            }
        }
    }
}
