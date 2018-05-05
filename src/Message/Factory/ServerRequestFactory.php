<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Rancoud\Http\Message\ServerRequest;
use Rancoud\Http\Message\UploadedFile;

/**
 * Class ServerRequestFactory.
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @param $method
     * @param $uri
     *
     * @return ServerRequestInterface
     */
    public function createServerRequest($method, $uri): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    /**
     * @param array $server
     *
     * @throws InvalidArgumentException
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
        if (array_key_exists('SERVER_PROTOCOL', $server)) {
            $protocol = str_replace('HTTP/', '', $server['SERVER_PROTOCOL']);
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
     *
     * @return ServerRequestInterface
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $server = $_SERVER;
        if (!array_key_exists('REQUEST_METHOD', $server)) {
            $server['REQUEST_METHOD'] = 'GET';
        }

        $headers = [];
        if (function_exists('\getallheaders')) {
            $headers = getallheaders();
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
        if (!array_key_exists('REQUEST_METHOD', $environment)) {
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
        $uri = (new UriFactory())->createUriFromArray($environment);
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
            } elseif (is_array($value) && array_key_exists('tmp_name', $value)) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
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
     * @return array|UploadedFile
     */
    protected static function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name'])) {
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
     * @return array
     */
    protected static function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
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
            if (mb_substr($name, 0, 5) === 'HTTP_') {
                $treatedName = mb_substr($name, 5);
                $treatedName = ucwords(mb_strtolower(str_replace('_', ' ', $treatedName)));
                $treatedName = str_replace(' ', '-', $treatedName);
                $headers[$treatedName] = $value;
            }
        }

        return $headers;
    }
}
