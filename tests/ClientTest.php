<?php

namespace Tests\Rancoud\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Rancoud\Http\Client\Client;
use Rancoud\Http\Client\Exception\NetworkException;
use Rancoud\Http\Client\Exception\RequestException;
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Stream;

class ClientTest extends TestCase
{
    public function testHead(): void
    {
        $client = new Client();
        $client->disableSSLVerification();

        try {
            $res = $client->sendRequest(new Request("HEAD", "https://lab.rancoud.com/http-tests/get.php"));

            static::assertEquals(200, $res->getStatusCode());
            static::assertEquals('', $res->getBody()->__toString());
        } catch (NetworkException $e) {
        } catch (RequestException $e) {
        } catch (ClientExceptionInterface $e) {
        }
    }

    public function testGet(): void
    {
        $client = new Client();
        $client->disableSSLVerification();

        try {
            $res = $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/get.php"));

            static::assertEquals(200, $res->getStatusCode());
            static::assertEquals('content from get', $res->getBody()->__toString());
        } catch (NetworkException $e) {
        } catch (RequestException $e) {
        } catch (ClientExceptionInterface $e) {
        }
    }

    public function testPost(): void
    {
        $client = new Client();
        $client->disableSSLVerification();

        try {
            $res = $client->sendRequest(new Request("POST", "https://lab.rancoud.com/http-tests/post.php"));

            static::assertEquals(200, $res->getStatusCode());
            static::assertEquals('content from post', $res->getBody()->__toString());
        } catch (NetworkException $e) {
        } catch (RequestException $e) {
        } catch (ClientExceptionInterface $e) {
        }
    }

    public function testHeader(): void
    {
        $client = new Client();
        $client->disableSSLVerification();

        try {
            $res = $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/headers.php", ['X-yolo' => ['you', 'us']]));

            static::assertEquals(200, $res->getStatusCode());
            static::assertEquals('you, us', $res->getBody()->__toString());
            static::assertEquals(['hello'], $res->getHeader('X-my-custom-header'));
        } catch (NetworkException $e) {
        } catch (RequestException $e) {
        } catch (ClientExceptionInterface $e) {
        }
    }

    public function testRequestException(): void
    {
        $this->expectException(RequestException::class);

        $client = new Client();
        $client->setCaInfosPath('/', '/');

        try {
            $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/get.php"));
        } catch (NetworkException $e) {
        } catch (RequestException $e) {
            throw $e;
        } catch (ClientExceptionInterface $e) {
        }
    }

    public function testNetworkException(): void
    {
        $this->expectException(NetworkException::class);

        $client = new Client();

        try {
            $client->sendRequest(new Request("GET", "https://labo.rancoud.com/http-tests/get.php"));
        } catch (NetworkException $e) {
            static::assertStringContainsStringIgnoringCase("Could not resolve", $e->getMessage());
            static::assertStringContainsStringIgnoringCase("labo.rancoud.com", $e->getMessage());
            throw $e;
        } catch (RequestException $e) {
        } catch (ClientExceptionInterface $e) {
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testCaInfos(): void
    {
        $client = new Client();
        $client->setCaInfosPath(__DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem', __DIR__ . DIRECTORY_SEPARATOR);

        $res = $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/get.php"));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('content from get', $res->getBody()->__toString());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDisableEnableSSL(): void
    {
        $request = new Request("GET", "https://lab.rancoud.com/http-tests/get.php");
        $client = new Client();
        try {
            $client->sendRequest($request);
        } catch (\Exception $e) {
            static::assertEquals(RequestException::class, get_class($e));
            static::assertEquals('SSL certificate problem: unable to get local issuer certificate', $e->getMessage());
        }

        $client->disableSSLVerification();
        $res = $client->sendRequest($request);
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('content from get', $res->getBody()->__toString());

        try {
            $client->enableSSLVerification();
            $client->sendRequest($request);
        } catch (\Exception $e) {
            static::assertEquals(RequestException::class, get_class($e));
            static::assertEquals('SSL certificate problem: unable to get local issuer certificate', $e->getMessage());
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testProtocolVersion(): void
    {
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest((new Request("HEAD", "https://lab.rancoud.com/http-tests/get.php"))->withProtocolVersion('1.0'));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('', $res->getBody()->__toString());

        $res = $client->sendRequest((new Request("HEAD", "https://lab.rancoud.com/http-tests/get.php"))->withProtocolVersion('2'));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('', $res->getBody()->__toString());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testSmallBody(): void
    {
        $body = Stream::create('a=a');
        $request = (new Request("POST", "https://lab.rancoud.com/http-tests/small-body.php"))->withBody($body);
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest($request);
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('a', $res->getBody()->__toString());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testBigBody(): void
    {
        $body = Stream::create(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'noise.jpg'));
        $request = (new Request("PUT", "https://lab.rancoud.com/http-tests/big-body.php"))->withBody($body);
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest($request);

        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('a', $res->getBody()->__toString());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     */
    public function testRequestExceptionGetRequest(): void
    {
        $request = new Request("GET", "https://lab.rancoud.com/http-tests/get.php");
        try {
            $client = new Client();
            $client->setCaInfosPath('/', '/');
            $client->sendRequest($request);
        } catch (RequestException $e) {
            $req = $e->getRequest();
            static::assertEquals($request, $req);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RequestException
     */
    public function testNetworkExceptionGetRequest(): void
    {
        $request = new Request("GET", "https://labo.rancoud.com/http-tests/get.php");
        try {
            $client = new Client();
            $client->sendRequest($request);
        } catch (NetworkException $e) {
            $req = $e->getRequest();
            static::assertEquals($request, $req);
        }
    }
}
