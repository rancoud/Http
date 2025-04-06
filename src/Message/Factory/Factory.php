<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

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

/**
 * Class Factory.
 */
class Factory implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
{
    /**
     * @param string|UriInterface $uri
     *
     * @throws \InvalidArgumentException
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    /**
     * @param string|resource|StreamInterface|null $body
     *
     * @throws \InvalidArgumentException
     */
    public function createResponseBody(int $code = 200, $body = null): Response
    {
        return new Response($code, [], $body, '1.1');
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createRedirection(string $location): Response
    {
        return new Response(301, ['Location' => $location], null, '1.1');
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return Stream::createFromFile($filename, $mode);
    }

    /**
     * @param resource $resource
     *
     * @throws \InvalidArgumentException
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return Stream::create($resource);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        if ($size === null) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createUriFromArray(array $server): Uri
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
            $uri = $uri->withPort((int) $server['SERVER_PORT']);
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
     * @param string|UriInterface $uri
     *
     * @throws \InvalidArgumentException
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createServerRequestFromArray(array $server): ServerRequest
    {
        return new ServerRequest(
            $this->getMethodFromEnvironment($server),
            $this->getUriFromEnvironmentWithHTTP($server),
            [],
            null,
            '1.1',
            $server
        );
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @noinspection PhpTooManyParametersInspection
     */
    public function createServerRequestFromArrays(
        array $server,
        array $headers,
        array $cookie,
        array $get,
        array $post,
        array $files
    ): ServerRequest {
        $method = $this->getMethodFromEnvironment($server);
        $uri = $this->getUriFromEnvironmentWithHTTP($server);

        $protocol = '1.1';
        if (\array_key_exists('SERVER_PROTOCOL', $server)) {
            $protocol = \str_replace('HTTP/', '', $server['SERVER_PROTOCOL']);
        }

        $serverRequest = new ServerRequest($method, $uri, $headers, null, $protocol, $server);

        return $serverRequest
            ->withCookieParams($cookie)
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withUploadedFiles(static::normalizeFiles($files));
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createServerRequestFromGlobals(): ServerRequest
    {
        $server = $_SERVER;
        $server['REQUEST_METHOD'] = $server['REQUEST_METHOD'] ?? 'GET';

        if (\function_exists('\getallheaders')) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the function
             */
            $headers = \getallheaders();
        // @codeCoverageIgnoreEnd
        } else {
            $headers = $this->getAllHeaders();
        }

        return $this->createServerRequestFromArrays($server, $headers, $_COOKIE, $_GET, $_POST, $_FILES);
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getMethodFromEnvironment(array $environment): string
    {
        if (!isset($environment['REQUEST_METHOD'])) {
            throw new \InvalidArgumentException('Cannot determine HTTP method');
        }

        return $environment['REQUEST_METHOD'];
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getUriFromEnvironmentWithHTTP(array $environment): UriInterface
    {
        $uri = $this->createUriFromArray($environment);
        if ($uri->getScheme() === '') {
            $uri = $uri->withScheme('http');
        }

        return $uri;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (\is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = static::createUploadedFileFromSpec($value);
            } elseif (\is_array($value)) {
                $normalized[$key] = static::normalizeFiles($value);
            } else {
                throw new \InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return array|UploadedFile
     */
    protected static function createUploadedFileFromSpec(array $value)
    {
        if (\is_array($value['tmp_name'])) {
            return static::normalizeNestedFileSpec($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected static function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (\array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = static::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    protected function getAllHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (\mb_strpos($name, 'HTTP_') === 0) {
                $treatedName = \mb_substr($name, 5);
                $treatedName = \ucwords(\mb_strtolower(\str_replace('_', ' ', $treatedName)));
                $treatedName = \str_replace(' ', '-', $treatedName);
                $headers[$treatedName] = $value;
            }
        }

        return $headers;
    }
}
