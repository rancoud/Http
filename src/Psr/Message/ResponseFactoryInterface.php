<?php

namespace Psr\Http\Message;

interface ResponseFactoryInterface
{
    public function createResponse($code = 200);
}
