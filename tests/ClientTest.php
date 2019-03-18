<?php

namespace Tests\Rancoud\Http;

use PHPUnit\Framework\TestCase;
use Rancoud\Http\Client\Client;
use Rancoud\Http\Client\Exception\NetworkException;
use Rancoud\Http\Client\Exception\RequestException;
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Stream;

class ClientTest extends TestCase
{
    public function testHead()
    {
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest(new Request("HEAD", "https://lab.rancoud.com/http-tests/get.php"));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('', $res->getBody()->__toString());
    }

    public function testGet()
    {
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/get.php"));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('content from get', $res->getBody()->__toString());
    }

    public function testPost()
    {
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest(new Request("POST", "https://lab.rancoud.com/http-tests/post.php"));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('content from post', $res->getBody()->__toString());
    }

    public function testHeader()
    {
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/headers.php", ['X-yolo' => ['you','us']]));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('you, us', $res->getBody()->__toString());
        static::assertEquals(['hello'], $res->getHeader('X-my-custom-header'));
    }

    public function testRequestException()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('SSL certificate problem: unable to get local issuer certificate');

        $client = new Client();
        $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/get.php"));
    }

    public function testNetworkException()
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Could not resolve host: labo.rancoud.com');

        $client = new Client();
        $client->sendRequest(new Request("GET", "https://labo.rancoud.com/http-tests/get.php"));
    }

    public function testCaInfos()
    {
        $client = new Client();
        $client->setCaInfosPath(__DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem', __DIR__ . DIRECTORY_SEPARATOR);

        $res = $client->sendRequest(new Request("GET", "https://lab.rancoud.com/http-tests/get.php"));
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('content from get', $res->getBody()->__toString());
    }

    public function testDisableEnableSSL()
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

    public function testProtocolVersion()
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

    public function testSmallBody()
    {
        $body = Stream::create('a=a');
        $request = (new Request("POST", "https://lab.rancoud.com/http-tests/body.php"))->withBody($body);
        $client = new Client();
        $client->disableSSLVerification();
        $res = $client->sendRequest($request);
        static::assertEquals(200, $res->getStatusCode());
        static::assertEquals('a', $res->getBody()->__toString());
    }
}