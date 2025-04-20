<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Rancoud\Http\Client\Client;
use Rancoud\Http\Client\Exception\NetworkException;
use Rancoud\Http\Client\Exception\RequestException;
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Stream;
use Symfony\Component\Process\Process;

/** @internal */
class ClientTest extends TestCase
{
    protected static Process $process;

    protected static string $localPHPServer = 'localhost:9877';

    protected string $goodURL = 'https://example.com';

    protected string $badURL = 'https://wrong.bad.invalid.example.com';

    protected string $localURL = 'http://localhost:9877/';

    public static function setUpBeforeClass(): void
    {
        static::$process = new Process(['php', '-S', static::$localPHPServer, '-t', \realpath(__DIR__ . \DIRECTORY_SEPARATOR . 'fake_server')]);

        static::$process->disableOutput();

        static::$process->start(static function ($type, $data): void {
            \sleep(1);

            if (\mb_stripos($data, 'started') === false) {
                static::$process->stop();
            }
        });

        \sleep(1);
    }

    public static function tearDownAfterClass(): void
    {
        static::$process->stop();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testHead(): void
    {
        $client = new Client();

        $res = $client->sendRequest(new Request('HEAD', $this->localURL . 'get.php'));

        static::assertSame(200, $res->getStatusCode());
        static::assertSame('', (string) $res->getBody());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testGet(): void
    {
        $client = new Client();

        $res = $client->sendRequest(new Request('GET', $this->localURL . 'get.php'));

        static::assertSame(200, $res->getStatusCode());
        static::assertSame('content from get', (string) $res->getBody());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPost(): void
    {
        $client = new Client();

        $res = $client->sendRequest(new Request('POST', $this->localURL . 'post.php'));

        static::assertSame(200, $res->getStatusCode());
        static::assertSame('content from post', (string) $res->getBody());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testHeader(): void
    {
        $client = new Client();

        $res = $client->sendRequest(new Request('GET', $this->localURL . 'headers.php', ['X-yolo' => ['content from header']]));

        static::assertSame(200, $res->getStatusCode());
        static::assertSame('content from header', (string) $res->getBody());
        static::assertSame('hello', $res->getHeaderLine('X-my-custom-header'));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testRequestExceptionBadCAInfos(): void
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('error setting');

        $client = new Client();
        $client->setCAInfosPath('/', '/');
        $request = new Request('GET', $this->goodURL);

        try {
            $client->sendRequest(new Request('GET', $this->goodURL));
        } catch (RequestException $e) {
            $req = $e->getRequest();
            static::assertSame($request->getMethod(), $req->getMethod());
            static::assertSame((string) $request->getUri(), (string) $req->getUri());

            throw $e;
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testNetworkExceptionBadURL(): void
    {
        $this->expectException(NetworkException::class);

        $client = new Client();
        $client->sendRequest(new Request('GET', $this->badURL));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testCaInfos(): void
    {
        $client = new Client();
        $client->setCAInfosPath(__DIR__ . \DIRECTORY_SEPARATOR . 'cacert.pem', __DIR__ . \DIRECTORY_SEPARATOR);

        $res = $client->sendRequest(new Request('GET', $this->goodURL));
        static::assertSame(200, $res->getStatusCode());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDisableEnableSSL(): void
    {
        $request = new Request('GET', $this->goodURL);
        $client = new Client();
        $client->sendRequest($request);

        try {
            $client->sendRequest($request);
        } catch (\Exception $e) {
            static::assertSame(RequestException::class, $e::class);
            static::assertSame('SSL certificate problem: unable to get local issuer certificate', $e->getMessage());
        }

        $client->disableSSLVerification();
        $res = $client->sendRequest($request);
        static::assertSame(200, $res->getStatusCode());

        try {
            $client->enableSSLVerification();
            $client->sendRequest($request);
        } catch (\Exception $e) {
            static::assertSame(RequestException::class, $e::class);
            static::assertSame('SSL certificate problem: unable to get local issuer certificate', $e->getMessage());
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

        $res = $client->sendRequest((new Request('HEAD', $this->localURL . 'get.php'))->withProtocolVersion('1.0'));
        static::assertSame(200, $res->getStatusCode());
        static::assertSame('', (string) $res->getBody());

        $res = $client->sendRequest((new Request('HEAD', $this->goodURL))->withProtocolVersion('2'));
        static::assertSame(200, $res->getStatusCode());
        static::assertSame('', (string) $res->getBody());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testSmallBody(): void
    {
        $body = Stream::create('a=b');
        $request = (new Request('POST', $this->localURL . 'small-body.php'))->withBody($body);
        $client = new Client();

        $res = $client->sendRequest($request);
        static::assertSame(200, $res->getStatusCode());
        static::assertSame('c', (string) $res->getBody());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NetworkException
     * @throws RequestException
     */
    public function testBigBody(): void
    {
        $body = Stream::create(\file_get_contents(__DIR__ . \DIRECTORY_SEPARATOR . 'noise.jpg'));
        $request = (new Request('PUT', $this->localURL . 'big-body.php'))->withBody($body);
        $client = new Client();

        $res = $client->sendRequest($request);

        static::assertSame(200, $res->getStatusCode());
        static::assertSame('ok', (string) $res->getBody());
    }
}
