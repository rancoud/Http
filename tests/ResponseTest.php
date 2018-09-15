<?php

namespace Tests\Rancoud\Psr7;

use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @backupGlobals disabled
 */
class ResponseTest extends TestCase
{
    public function testDefaultConstructor()
    {
        $r = new Response();
        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('1.1', $r->getProtocolVersion());
        $this->assertSame('OK', $r->getReasonPhrase());
        $this->assertSame([], $r->getHeaders());
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('', (string) $r->getBody());
    }

    public function testCanConstructWithStatusCode()
    {
        $r = new Response(404);
        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('Not Found', $r->getReasonPhrase());
    }

    public function testConstructorDoesNotReadStreamBody()
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects($this->never())
            ->method('__toString');

        $r = new Response(200, [], $body);
        $this->assertSame($body, $r->getBody());
    }

    public function testStatusCanBeNumericString()
    {
        $r = new Response('404');
        $r2 = $r->withStatus('201');
        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('Not Found', $r->getReasonPhrase());
        $this->assertSame(201, $r2->getStatusCode());
        $this->assertSame('Created', $r2->getReasonPhrase());
    }

    public function testCanConstructWithHeaders()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame('Bar', $r->getHeaderLine('Foo'));
        $this->assertSame(['Bar'], $r->getHeader('Foo'));
    }

    public function testCanConstructWithHeadersAsArray()
    {
        $r = new Response(200, [
            'Foo' => ['baz', 'bar'],
        ]);
        $this->assertSame(['Foo' => ['baz', 'bar']], $r->getHeaders());
        $this->assertSame('baz, bar', $r->getHeaderLine('Foo'));
        $this->assertSame(['baz', 'bar'], $r->getHeader('Foo'));
    }

    public function testCanConstructWithBody()
    {
        $r = new Response(200, [], 'baz');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('baz', (string) $r->getBody());
    }

    public function testNullBody()
    {
        $r = new Response(200, [], null);
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('', (string) $r->getBody());
    }

    public function testFalseyBody()
    {
        $r = new Response(200, [], '0');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('0', (string) $r->getBody());
    }

    public function testCanConstructWithReason()
    {
        $r = new Response(200, [], null, '1.1', 'bar');
        $this->assertSame('bar', $r->getReasonPhrase());

        $r = new Response(200, [], null, '1.1', '0');
        $this->assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
    }

    public function testCanConstructWithProtocolVersion()
    {
        $r = new Response(200, [], null, '1.0');
        $this->assertSame('1.0', $r->getProtocolVersion());
    }

    public function testRaiseExceptionConstructWithProtocolVersion()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Protocol Version must be 0.9 or 1.0 or 1.1 or 2');

        $r = new Response(200, [], null, '1000');
    }
    
    public function testWithStatusCodeAndNoReason()
    {
        $r = (new Response())->withStatus(201);
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame('Created', $r->getReasonPhrase());
    }

    public function testWithStatusCodeAndReason()
    {
        $r = (new Response())->withStatus(201, 'Foo');
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame('Foo', $r->getReasonPhrase());

        $r = (new Response())->withStatus(201, '0');
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
    }

    public function testWithProtocolVersion()
    {
        $r = (new Response())->withProtocolVersion('1.0');
        $this->assertSame('1.0', $r->getProtocolVersion());
    }

    public function testSameInstanceWhenSameProtocol()
    {
        $r = new Response();
        $this->assertSame($r, $r->withProtocolVersion('1.1'));
    }

    public function testWithBody()
    {
        $b = (new Factory())->createStream('0');
        $r = (new Response())->withBody($b);
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('0', (string) $r->getBody());
    }

    public function testSameInstanceWhenSameBody()
    {
        $r = new Response();
        $b = $r->getBody();
        $this->assertSame($r, $r->withBody($b));
    }

    public function testWithHeader()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('baZ', 'Bam');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $r2->getHeaders());
        $this->assertSame('Bam', $r2->getHeaderLine('baz'));
        $this->assertSame(['Bam'], $r2->getHeader('baz'));
    }

    public function testWithHeaderAsArray()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('baZ', ['Bam', 'Bar']);
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $r2->getHeaders());
        $this->assertSame('Bam, Bar', $r2->getHeaderLine('baz'));
        $this->assertSame(['Bam', 'Bar'], $r2->getHeader('baz'));
    }

    public function testWithHeaderReplacesDifferentCase()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('foO', 'Bam');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['foO' => ['Bam']], $r2->getHeaders());
        $this->assertSame('Bam', $r2->getHeaderLine('foo'));
        $this->assertSame(['Bam'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeader()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('foO', 'Baz');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar', 'Baz']], $r2->getHeaders());
        $this->assertSame('Bar, Baz', $r2->getHeaderLine('foo'));
        $this->assertSame(['Bar', 'Baz'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeaderAsArray()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('foO', ['Baz', 'Bam']);
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $r2->getHeaders());
        $this->assertSame('Bar, Baz, Bam', $r2->getHeaderLine('foo'));
        $this->assertSame(['Bar', 'Baz', 'Bam'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeaderThatDoesNotExist()
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('nEw', 'Baz');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $r2->getHeaders());
        $this->assertSame('Baz', $r2->getHeaderLine('new'));
        $this->assertSame(['Baz'], $r2->getHeader('new'));
    }

    public function testWithoutHeaderThatExists()
    {
        $r = new Response(200, ['Foo' => 'Bar', 'Baz' => 'Bam']);
        $r2 = $r->withoutHeader('foO');
        $this->assertTrue($r->hasHeader('foo'));
        $this->assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $r->getHeaders());
        $this->assertFalse($r2->hasHeader('foo'));
        $this->assertSame(['Baz' => ['Bam']], $r2->getHeaders());
    }

    public function testWithoutHeaderThatDoesNotExist()
    {
        $r = new Response(200, ['Baz' => 'Bam']);
        $r2 = $r->withoutHeader('foO');
        $this->assertSame($r, $r2);
        $this->assertFalse($r2->hasHeader('foo'));
        $this->assertSame(['Baz' => ['Bam']], $r2->getHeaders());
    }

    public function testSameInstanceWhenRemovingMissingHeader()
    {
        $r = new Response();
        $this->assertSame($r, $r->withoutHeader('foo'));
    }

    public function trimmedHeaderValues()
    {
        return [
            [new Response(200, ['OWS' => " \t \tFoo\t \t "])],
            [(new Response())->withHeader('OWS', " \t \tFoo\t \t ")],
            [(new Response())->withAddedHeader('OWS', " \t \tFoo\t \t ")],
        ];
    }

    public function testHasHeaderMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be a string');

        $r = new Response();
        $this->assertSame($r, $r->hasHeader(null));
    }

    public function testGetHeaderMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be a string');

        $r = new Response();
        $this->assertSame($r, $r->getHeader(null));
    }

    public function testGetHeaderLineMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be a string');

        $r = new Response();
        $this->assertSame($r, $r->getHeaderLine(null));
    }

    public function testWithHeaderNameMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be non-empty string');

        $r = new Response();
        $this->assertSame($r, $r->withHeader('', ''));
    }

    public function testWithHeaderValueArrayMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be a string or an array of strings, empty array given.');

        $r = new Response();
        $this->assertSame($r, $r->withHeader('aze', []));
    }

    public function testWithHeaderValueStringMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be RFC 7230 compatible strings.');

        $r = new Response();
        $this->assertSame($r, $r->withHeader('aze', [null]));
    }

    public function testWithAddedHeaderNameMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be non-empty string');

        $r = new Response();
        $this->assertSame($r, $r->withAddedHeader('', ''));
    }

    public function testWithAddedHeaderNameMustHaveCorrectTypeRFC()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be RFC 7230 compatible string.');

        $r = new Response();
        $this->assertSame($r, $r->withAddedHeader("a\t", ''));
    }

    public function testWithAddedHeaderValueMustHaveCorrectTypeRFC()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header value must be RFC 7230 compatible string.');

        $r = new Response();
        $this->assertSame($r, $r->withAddedHeader("a", null));
    }
    
    public function testWithAddedHeaderValueArrayMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be a string or an array of strings, empty array given.');

        $r = new Response();
        $this->assertSame($r, $r->withAddedHeader('aze', []));
    }

    public function testWithAddedHeaderValueStringMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be RFC 7230 compatible strings.');

        $r = new Response();
        $this->assertSame($r, $r->withAddedHeader('aze', [null]));
    }

    public function testWithoutHeaderNameMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be non-empty string');

        $r = new Response();
        $this->assertSame($r, $r->withoutHeader(''));
    }

    /**
     * @dataProvider trimmedHeaderValues
     */
    public function testHeaderValuesAreTrimmed($r)
    {
        $this->assertSame(['OWS' => ['Foo']], $r->getHeaders());
        $this->assertSame('Foo', $r->getHeaderLine('OWS'));
        $this->assertSame(['Foo'], $r->getHeader('OWS'));
    }

    public function testWithStatusCodeMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status code has to be an integer');

        $r = new Response();
        $r->withStatus([]);
    }

    public function testWithStatusReasonPhraseMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status code has to be an integer between 100 and 599');

        $r = new Response();
        $r->withStatus(9, []);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSend()
    {
        $r = new Response(
            200,
            [
                'Content-Type' => 'text/plain;charset=UTF-8',
                'customName' => 'mykey',
                'empty' => ''
            ],
            $body = uniqid(true),
            $version = '2'
        );
        $infos = $this->captureSend($r);
        $this->assertSame('Content-Type: text/plain;charset=UTF-8', $infos['headers'][0]);
        $this->assertSame('customName: mykey', $infos['headers'][1]);
        $this->assertSame('empty:', $infos['headers'][2]);
        $this->assertSame($body, $infos['body']);
    }
    
    private function captureSend(Response $response)
    {
        ob_start();
        $response->send();
        $output = ob_get_clean();
        return ['headers' => xdebug_get_headers(), 'body' => $output];
    }
}
