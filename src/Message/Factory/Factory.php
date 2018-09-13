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

        if (!\in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
            throw new InvalidArgumentException(\sprintf('The mode %s is invalid.', $mode));
        }

        $resource = \fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException(\sprintf('The file %s cannot be opened.', $filename));
        }

        return Stream::create($resource);
    }

    /**
     * @param $resource
     *
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    /**
     * @param array $server
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return ServerRequestInterface
     */
    public function createServerRequestFromArray(array $server): ServerRequestInterface
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
     * @param array $server
     * @param array $headers
     * @param array $cookie
     * @param array $get
     * @param array $post
     * @param array $files
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return ServerRequestInterface
     */
    public function createServerRequestFromArrays(
        array $server,
        array $headers,
        array $cookie,
        array $get,
        array $post,
        array $files
    ): ServerRequestInterface {
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
            ->withUploadedFiles(self::normalizeFiles($files));
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return ServerRequestInterface
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $server = $_SERVER;
        if (!\array_key_exists('REQUEST_METHOD', $server)) {
            $server['REQUEST_METHOD'] = 'GET';
        }

        $headers = [];
        if (\function_exists('\getallheaders')) {
            $headers = \getallheaders();
        } else {
            $headers = $this->getAllHeaders();
        }

        return $this->createServerRequestFromArrays($server, $headers, $_COOKIE, $_GET, $_POST, $_FILES);
    }

    /**
     * @param array $environment
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function getMethodFromEnvironment(array $environment): string
    {
        if (!isset($environment['REQUEST_METHOD'])) {
            throw new InvalidArgumentException('Cannot determine HTTP method');
        }

        return $environment['REQUEST_METHOD'];
    }

    /**
     * @param array $environment
     *
     * @throws InvalidArgumentException
     *
     * @return UriInterface
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
     * @param array $files
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    protected static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (\is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (\is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);

                continue;
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    /**
     * @param array $value
     *
     * @throws InvalidArgumentException
     *
     * @return array|UploadedFile
     */
    protected static function createUploadedFileFromSpec(array $value)
    {
        if (\is_array($value['tmp_name'])) {
            return self::normalizeNestedFileSpec($value);
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
     * @param array $files
     *
     * @throws InvalidArgumentException
     *
     * @return array
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
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    /**
     * @return array
     */
    protected function getAllHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (\mb_substr($name, 0, 5) === 'HTTP_') {
                $treatedName = \mb_substr($name, 5);
                $treatedName = \ucwords(\mb_strtolower(\str_replace('_', ' ', $treatedName)));
                $treatedName = \str_replace(' ', '-', $treatedName);
                $headers[$treatedName] = $value;
            }
        }

        return $headers;
    }
}
