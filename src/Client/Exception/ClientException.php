<?php

declare(strict_types=1);

namespace Rancoud\Http\Client\Exception;

use Psr\Http\Message\RequestInterface;

abstract class ClientException extends \Exception
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    public function __construct(RequestInterface $request, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->request = $request;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the request.
     *
     * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
