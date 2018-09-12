<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use InvalidArgumentException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Response;
use Rancoud\Http\Message\ServerRequest;
use Rancoud\Http\Message\Stream;
use Rancoud\Http\Message\UploadedFile;
use Rancoud\Http\Message\Uri;
use RuntimeException;

class Factory implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
{
    /**
     * @param string $method
     * @param        $uri
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return RequestInterface
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    /**
     * @param string $content
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    /**
     * @param string $filename
     * @param string $mode
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return StreamInterface
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!\file_exists($filename)) {
            throw new RuntimeException(\sprintf('The file %s doesn\'t exist.', $filename));
        }

        $resource = \fopen($filename, $mode);
        if ($resource === false) {
            if (!\in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
                throw new InvalidArgumentException(\sprintf('The mode %s is invalid.', $mode));
            }
            throw new RuntimeException(\sprintf('The file %s cannot be opened.', $filename));
        }

        return Stream::create($resource);
    }

    /**
     * @param $resource
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return Stream::create($resource);
    }

    /**
     * @param StreamInterface $stream
     * @param int|null        $size
     * @param int             $error
     * @param string|null     $clientFilename
     * @param string|null     $clientMediaType
     *
     * @throws \InvalidArgumentException
     *
     * @return UploadedFileInterface
     */
    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        if ($size === null) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /**
     * @param string $uri
     *
     * @throws \InvalidArgumentException
     *
     * @return UriInterface
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    /**
     * @param array $server
     *
     * @throws \InvalidArgumentException
     *
     * @return UriInterface
     */
    public function createUriFromArray(array $server): UriInterface
    {
        $uri = new Uri('');

        if (isset($server['REQUEST_SCHEME'])) {
            $uri = $uri->withScheme($server['REQUEST_SCHEME']);
        } elseif (isset($server['HTTPS'])) {
            if ($server['HTTPS'] === 'on') {
                $uri = $uri->withScheme('https');
            } else {
                $uri = $uri->withScheme('http');
            }
        }

        if (isset($server['HTTP_HOST'])) {
            $uri = $uri->withHost($server['HTTP_HOST']);
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['SERVER_PORT'])) {
            $uri = $uri->withPort($server['SERVER_PORT']);
        }

        if (isset($server['REQUEST_URI'])) {
            $parts = \explode('?', $server['REQUEST_URI']);
            $uri = $uri->withPath($parts[0]);
        }

        if (isset($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * @param string $method
     * @param        $uri
     * @param array  $serverParams
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }
}
