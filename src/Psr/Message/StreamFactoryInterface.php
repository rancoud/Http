<?php

namespace Psr\Http\Message;

interface StreamFactoryInterface
{
    public function createStream($content = '');
    public function createStreamFromFile($filename, $mode = 'r');
    public function createStreamFromResource($resource);
}
