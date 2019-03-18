<?php

declare(strict_types=1);

namespace Rancoud\Http\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Rancoud\Http\Client\Exception\{NetworkException, RequestException};
use Rancoud\Http\Message\Response;

class Client implements ClientInterface
{
    protected $CAInfosPath = ['info' => null, 'path' => null];
    protected $hasSSLVerification = true;

    public function disableSSLVerification(): void
    {
        $this->hasSSLVerification = false;
    }

    public function enableSSLVerification(): void
    {
        $this->hasSSLVerification = true;
    }

    public function setCaInfosPath(string $infos = null, string $path = null): void
    {
        $this->CAInfosPath = ['info' => $infos, 'path' => $path];
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface if an error happens while processing the request
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $curlHandle = $this->createCurlSimple($request);
        $infos = $this->sendCurlSimple($curlHandle, $request);
        $response = $this->convertCurlSimpleResponse($infos);

        return $response;
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

        \curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($curlHandle, CURLOPT_HEADER, true);

        $this->setProtocolVersion($curlHandle, $request);
        $this->setMethod($curlHandle, $request);
        $this->setUrl($curlHandle, $request);
        $this->setBody($curlHandle, $request);
        $this->setHeaders($curlHandle, $request);

        $this->setSSL($curlHandle, $request);

        return $curlHandle;
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     */
    protected function setProtocolVersion($curlHandle, RequestInterface $request): void
    {
        $version = $request->getProtocolVersion();

        if ($version === '1.0') {
            \curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        } elseif ($version === '1.1') {
            \curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        } elseif ($version === '2.0') {
            \curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        } elseif ($version === '2') {
            \curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2);
        }
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     */
    protected function setMethod($curlHandle, RequestInterface $request): void
    {
        $method = $request->getMethod();
        if ($method === 'HEAD') {
            \curl_setopt($curlHandle, CURLOPT_NOBODY, true);
        } elseif ($method === 'GET') {
            \curl_setopt($curlHandle, CURLOPT_HTTPGET, true);
        } else {
            \curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     */
    protected function setUrl($curlHandle, RequestInterface $request): void
    {
        \curl_setopt($curlHandle, CURLOPT_URL, $request->getUri()->__toString());
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     *
     * @throws \RuntimeException
     */
    protected function setBody($curlHandle, RequestInterface $request): void
    {
        $body = $request->getBody();
        $bodySize = $body->getSize();
        if ($bodySize !== 0) {
            if ($body->isSeekable()) {
                $body->rewind();
            }

            if ($bodySize === null || $bodySize > 1024 * 1024) {
                \curl_setopt($curlHandle, CURLOPT_UPLOAD, true);
                if ($bodySize !== null) {
                    \curl_setopt($curlHandle, CURLOPT_INFILESIZE, $bodySize);
                }
                \curl_setopt($curlHandle, CURLOPT_READFUNCTION, function ($curlRes, $streamRes, $length) use ($body) {
                    return $body->read($length);
                });
            } else {
                \curl_setopt($curlHandle, CURLOPT_POSTFIELDS, (string) $body);
            }
        }
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     */
    protected function setHeaders($curlHandle, RequestInterface $request): void
    {
        $headersCurl = [];

        $headers = $request->getHeaders();
        foreach ($headers as $key => $values) {
            if (!\is_array($values)) {
                $headersCurl[] = \sprintf('%s: %s', $key, $values);
            } else {
                foreach ($values as $value) {
                    $headersCurl[] = \sprintf('%s: %s', $key, $value);
                }
            }
        }

        \curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headersCurl);
    }

    /**
     * @param resource         $curlHandle
     * @param RequestInterface $request
     */
    protected function setSSL($curlHandle, RequestInterface $request): void
    {
        if ($request->getUri()->getScheme() === 'https') {
            if ($this->CAInfosPath['info'] !== null) {
                \curl_setopt($curlHandle, CURLOPT_CAINFO, $this->CAInfosPath['info']);
            }

            if ($this->CAInfosPath['path'] !== null) {
                \curl_setopt($curlHandle, CURLOPT_CAPATH, $this->CAInfosPath['path']);
            }

            if (!$this->hasSSLVerification) {
                \curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
            }
        }
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
            case CURLE_OK:
                break;
            case CURLE_COULDNT_RESOLVE_PROXY:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_COULDNT_CONNECT:
            case CURLE_OPERATION_TIMEOUTED:
            case CURLE_SSL_CONNECT_ERROR:
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

            list($key, $value) = \explode(': ', $line);
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
    protected function convertCurlSimpleResponse(array $infos)
    {
        return new Response($infos['status'], $infos['headers'], $infos['body']);
    }
}
