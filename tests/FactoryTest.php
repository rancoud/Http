<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Uri;

/** @internal */
class FactoryTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $r = (new Factory())->createRequest('GET', '/');
        static::assertSame('/', (string) $r->getUri());
    }

    public function testCreateResponse(): void
    {
        $r = (new Factory())->createResponse();
        static::assertSame(200, $r->getStatusCode());
        static::assertSame('1.1', $r->getProtocolVersion());
        static::assertSame('OK', $r->getReasonPhrase());
        static::assertSame([], $r->getHeaders());
        static::assertInstanceOf(StreamInterface::class, $r->getBody());
        static::assertSame('', (string) $r->getBody());
    }

    public function testCreateResponseBody(): void
    {
        $r = (new Factory())->createResponseBody(201, 'yolo');
        static::assertSame(201, $r->getStatusCode());
        static::assertSame('1.1', $r->getProtocolVersion());
        static::assertSame('Created', $r->getReasonPhrase());
        static::assertSame([], $r->getHeaders());
        static::assertSame('yolo', (string) $r->getBody());
    }

    public function testCreateRedirection(): void
    {
        $r = (new Factory())->createRedirection('/blog/');
        static::assertSame(301, $r->getStatusCode());
        static::assertSame('1.1', $r->getProtocolVersion());
        static::assertSame('Moved Permanently', $r->getReasonPhrase());
        static::assertSame(['Location' => ['/blog/']], $r->getHeaders());
        static::assertSame('', (string) $r->getBody());
    }

    public function testCreateServerRequest(): void
    {
        $r = (new Factory())->createServerRequest('POST', '/');
        static::assertSame('/', (string) $r->getUri());
    }

    public function testCreateServerRequestFromArray(): void
    {
        $r = (new Factory())->createServerRequestFromArray(\array_merge($_SERVER, ['REQUEST_METHOD' => 'DELETE']));
        static::assertSame('DELETE', $r->getMethod());
    }

    public function testCreateServerRequestFromArrayRaiseExceptionMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine HTTP method');

        (new Factory())->createServerRequestFromArray($_SERVER);
    }

    #[RunInSeparateProcess]
    public function testCreateServerRequestFromGlobalsWithRequestMethod(): void
    {
        $_SERVER = \array_merge($_SERVER, ['REQUEST_METHOD' => 'POST']);
        $r = (new Factory())->createServerRequestFromGlobals();
        static::assertSame('POST', $r->getMethod());
    }

    #[RunInSeparateProcess]
    public function testCreateServerRequestFromGlobalsWithoutRequestMethod(): void
    {
        $r = (new Factory())->createServerRequestFromGlobals();
        static::assertSame('GET', $r->getMethod());
    }

    #[RunInSeparateProcess]
    public function testCreateServerRequestFromGlobalsFakeNginx(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-gb,en;q=0.5';
        $r = (new Factory())->createServerRequestFromGlobals();
        static::assertSame('GET', $r->getMethod());
    }

    public function testCreateUri(): void
    {
        $r = (new Factory())->createUri('/rty/');
        static::assertSame('/rty/', $r->getPath());

        $r = (new Factory())->createUri((string) new Uri('/aze/'));
        static::assertSame('/aze/', $r->getPath());
    }

    public function testCreateUriFromServer(): void
    {
        $server = [
            'PHP_SELF'             => '/blog/article.php',
            'GATEWAY_INTERFACE'    => 'CGI/1.1',
            'SERVER_ADDR'          => 'Server IP: 217.112.82.20',
            'SERVER_NAME'          => 'www.blakesimpson.co.uk',
            'SERVER_SOFTWARE'      => 'Apache/2.2.15 (Win32) JRun/4.0 PHP/5.2.13',
            'SERVER_PROTOCOL'      => 'HTTP/1.0',
            'REQUEST_METHOD'       => 'POST',
            'REQUEST_TIME'         => 'Request start time: 1280149029',
            'QUERY_STRING'         => 'id=10&user=foo',
            'DOCUMENT_ROOT'        => '/path/to/your/server/root/',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
            'HTTP_ACCEPT_LANGUAGE' => 'en-gb,en;q=0.5',
            'HTTP_CONNECTION'      => 'keep-alive',
            'HTTP_HOST'            => 'www.blakesimpson.co.uk',
            'HTTP_REFERER'         => 'https://previous.url.com',
            'HTTP_USER_AGENT'      => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)',
            'HTTPS'                => '1',
            'REMOTE_ADDR'          => '193.60.168.69',
            'REMOTE_HOST'          => 'Client server\'s host name',
            'REMOTE_PORT'          => '5390',
            'SCRIPT_FILENAME'      => '/path/to/this/script.php',
            'SERVER_ADMIN'         => 'webmaster@blakesimpson.co.uk',
            'SERVER_PORT'          => '80',
            'SERVER_SIGNATURE'     => 'Version signature: 5.123',
            'SCRIPT_NAME'          => '/blog/article.php',
            'REQUEST_URI'          => '/blog/article.php?id=10&user=foo',
            'REQUEST_SCHEME'       => 'http'
        ];

        $r = (new Factory())->createUriFromArray($server);
        static::assertSame('http', $r->getScheme());
    }

    public function testCreateStreamFromFile(): void
    {
        $s = (new Factory())->createStreamFromFile(__FILE__);
        static::assertSame(__FILE__, $s->getMetadata()['uri']);
    }

    public function testCreateStreamFromResource(): void
    {
        $s = (new Factory())->createStreamFromResource(\fopen(__FILE__, 'r'));
        static::assertSame(__FILE__, $s->getMetadata()['uri']);
    }

    public function testCreateStreamFromFileRaiseExceptionFileNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The file azert doesn\'t exist.');

        (new Factory())->createStreamFromFile('azert');
    }

    public function testCreateStreamFromFileRaiseExceptionModeInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The mode yolo is invalid.');

        (new Factory())->createStreamFromFile(__FILE__, 'yolo');
    }

    public function testCreateUploadedFile(): void
    {
        $u = (new Factory())->createUploadedFile((new Factory())->createStream('writing to tempfile'));
        static::assertSame('writing to tempfile', (string) $u->getStream());
    }
}
