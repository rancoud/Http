<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use Psr\Http\Message\UploadedFileFactoryInterface;
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
        $file,
        $size = null,
        $error = \UPLOAD_ERR_OK,
        $clientFilename = null,
        $clientMediaType = null
    ): UploadedFile {
        if (is_string($file)) {
            $content = $file;
            $filename = sys_get_temp_dir() . '/' . uniqid('uploaded_file', true);
            $file = fopen($filename, 'w+');
            fwrite($file, $content);
        }

        if ($size === null) {
            $stats = fstat($file);
            $size = $stats['size'];
        }

        return new UploadedFile($file, $size, $error, $clientFilename, $clientMediaType);
    }
}
