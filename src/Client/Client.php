<?php

declare(strict_types=1);

namespace Rancoud\Http\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rancoud\Http\Client\Exception\NetworkException;
use Rancoud\Http\Client\Exception\RequestException;
use Rancoud\Http\Message\Response;

class Client implements ClientInterface
{
    protected array $CAInfosPath = ['info' => null, 'path' => null];
    protected bool $hasSSLVerification = true;
    protected static array $curlHTTPVersionMap = [
        '1.0' => \CURL_HTTP_VERSION_1_0,
        '1.1' => \CURL_HTTP_VERSION_1_1,
        '2'   => \CURL_HTTP_VERSION_2_0,
        '2.0' => \CURL_HTTP_VERSION_2_0,
    ];

    public function disableSSLVerification(): self
    {
        $this->hasSSLVerification = false;

        return $this;
    }

    public function enableSSLVerification(): self
    {
        $this->hasSSLVerification = true;

        return $this;
    }

    public function setCAInfosPath(?string $infos = null, ?string $path = null): self
    {
        $this->CAInfosPath = ['info' => $infos, 'path' => $path];

        return $this;
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws RequestException
     * @throws NetworkException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $curlHandle = $this->createCurlSimple($request);
        $infos = $this->sendCurlSimple($curlHandle, $request);

        return $this->convertCurlSimpleResponse($infos);
    }

    /**
     * @param RequestInterface $request
     *
     * @throws \RuntimeException
     *
     * @return false|resource
     */
    protected function createCurlSimple(RequestInterface $request)
    {
        $curlHandle = \curl_init();

        \curl_setopt($curlHandle, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($curlHandle, \CURLOPT_HEADER, true);

        $this->setProtocolVersion($curlHandle, $request)
            ->setMethod($curlHandle, $request)
            ->setUrl($curlHandle, $request)
            ->setBody($curlHandle, $request)
            ->setHeaders($curlHandle, $request)
            ->setSsl($curlHandle, $request);

        return $curlHandle;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @return Client
     */
    protected function setProtocolVersion($curlHandle, RequestInterface $request): self
    {
        $version = $request->getProtocolVersion();

        $curlVersion = static::$curlHTTPVersionMap[$version] ?? null;
        if ($curlVersion !== null) {
            \curl_setopt($curlHandle, \CURLOPT_HTTP_VERSION, $curlVersion);
        }

        return $this;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @return Client
     */
    protected function setMethod($curlHandle, RequestInterface $request): self
    {
        $method = $request->getMethod();
        if ($method === 'HEAD') {
            \curl_setopt($curlHandle, \CURLOPT_NOBODY, true);
        } elseif ($method === 'GET') {
            \curl_setopt($curlHandle, \CURLOPT_HTTPGET, true);
        } else {
            \curl_setopt($curlHandle, \CURLOPT_CUSTOMREQUEST, $method);
        }

        return $this;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @return Client
     */
    protected function setUrl($curlHandle, RequestInterface $request): self
    {
        \curl_setopt($curlHandle, \CURLOPT_URL, $request->getUri()->__toString());

        return $this;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @throws \RuntimeException
     *
     * @return Client
     */
    protected function setBody($curlHandle, RequestInterface $request): self
    {
        $body = $request->getBody();
        $bodySize = $body->getSize();
        if ($bodySize !== 0) {
            if ($body->isSeekable()) {
                $body->rewind();
            }

            if ($bodySize === null || $bodySize > 1024 * 1024) {
                \curl_setopt($curlHandle, \CURLOPT_UPLOAD, true);
                if ($bodySize !== null) {
                    \curl_setopt($curlHandle, \CURLOPT_INFILESIZE, $bodySize);
                }
                \curl_setopt($curlHandle, \CURLOPT_READFUNCTION, static function ($curlRes, $streamRes, $length) use ($body) {
                    return $body->read($length);
                });
            } else {
                \curl_setopt($curlHandle, \CURLOPT_POSTFIELDS, (string) $body);
            }
        }

        return $this;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @return Client
     */
    protected function setHeaders($curlHandle, RequestInterface $request): self
    {
        $headersCurl = [];

        $headers = $request->getHeaders();
        foreach ($headers as $key => $values) {
            foreach ($values as $value) {
                $headersCurl[] = \sprintf('%s: %s', $key, $value);
            }
        }

        \curl_setopt($curlHandle, \CURLOPT_HTTPHEADER, $headersCurl);

        return $this;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @return Client
     */
    protected function setSsl($curlHandle, RequestInterface $request): self
    {
        if ($request->getUri()->getScheme() === 'https') {
            if ($this->CAInfosPath['info'] !== null) {
                \curl_setopt($curlHandle, \CURLOPT_CAINFO, $this->CAInfosPath['info']);
            }

            if ($this->CAInfosPath['path'] !== null) {
                \curl_setopt($curlHandle, \CURLOPT_CAPATH, $this->CAInfosPath['path']);
            }

            if (!$this->hasSSLVerification) {
                /* @noinspection CurlSslServerSpoofingInspection */
                \curl_setopt($curlHandle, \CURLOPT_SSL_VERIFYPEER, false);
            }
        }

        return $this;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @throws NetworkException
     * @throws RequestException
     *
     * @return array
     */
    protected function sendCurlSimple($curlHandle, RequestInterface $request): array
    {
        $data = \curl_exec($curlHandle);

        $this->parseCurlSimpleError($curlHandle, $request);

        $infos = \curl_getinfo($curlHandle);

        $status = $infos['http_code'];
        $headers = $this->extractHeaders($data, $infos['header_size']);
        $body = $this->extractBody($data, $infos['header_size']);

        \curl_close($curlHandle);

        return ['body' => $body, 'status' => $status, 'headers' => $headers];
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @throws NetworkException
     * @throws RequestException
     */
    protected function parseCurlSimpleError($curlHandle, RequestInterface $request): void
    {
        $errno = \curl_errno($curlHandle);

        switch ($errno) {
            case \CURLE_OK:
                break;
            case \CURLE_COULDNT_RESOLVE_PROXY:
            case \CURLE_COULDNT_RESOLVE_HOST:
            case \CURLE_COULDNT_CONNECT:
            case \CURLE_OPERATION_TIMEOUTED:
            case \CURLE_SSL_CONNECT_ERROR:
                throw new NetworkException($request, \curl_error($curlHandle), $errno);
            default:
                throw new RequestException($request, \curl_error($curlHandle), $errno);
        }
    }

    protected function extractHeaders(string $data, int $headerSize): array
    {
        $rawHeaders = \mb_substr($data, 0, $headerSize);

        $headers = [];
        $headerText = \mb_substr($rawHeaders, 0, \mb_strpos($rawHeaders, "\r\n\r\n"));
        foreach (\explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0) {
                continue;
            }

            [$key, $value] = \explode(':', $line, 2);
            $headers[$key] = $value;
        }

        return $headers;
    }

    protected function extractBody(string $data, int $headerSize): string
    {
        return \mb_substr($data, $headerSize);
    }

    /**
     * @param array $infos
     *
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    protected function convertCurlSimpleResponse(array $infos): Response
    {
        return new Response($infos['status'], $infos['headers'], $infos['body']);
    }
}
