<?php

namespace Psr\Http\Message;

interface UploadedFileFactoryInterface
{
    public function createUploadedFile(
        $file,
        $size = null,
        $error = \UPLOAD_ERR_OK,
        $clientFilename = null,
        $clientMediaType = null
    );
}
