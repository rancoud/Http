<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Rancoud\Http\Message\UploadedFile;

/**
 * Class UploadedFileFactory.
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * @param      $file
     * @param null $size
     * @param int  $error
     * @param null $clientFilename
     * @param null $clientMediaType
     *
     * @return UploadedFile
     */
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface
    {
        if (\is_string($stream)) {
            $content = $stream;
            $filename = \sys_get_temp_dir() . '/' . \uniqid('uploaded_file', true);
            $file = \fopen($filename, 'w+b');
            \fwrite($file, $content);
        }

        if ($size === null) {
            $stats = \fstat($file);
            $size = $stats['size'];
        }

        return new UploadedFile($file, $size, $error, $clientFilename, $clientMediaType);
    }
}
