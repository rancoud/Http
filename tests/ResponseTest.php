<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Response;

/**
 * @backupGlobals disabled
 */
class ResponseTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $r = new Response();
        static::assertSame(200, $r->getStatusCode());
        static::assertSame('1.1', $r->getProtocolVersion());
        static::assertSame('OK', $r->getReasonPhrase());
        static::assertSame([], $r->getHeaders());
        static::assertSame('', (string) $r->getBody());
    }

    public function testCanConstructWithStatusCode(): void
    {
        $r = new Response(404);
        static::assertSame(404, $r->getStatusCode());
        static::assertSame('Not Found', $r->getReasonPhrase());
    }

    public function testConstructorDoesNotReadStreamBody(): void
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects(static::never())->method('__toString');

        $r = new Response(200, [], $body);
        static::assertSame($body, $r->getBody());
    }

    public function testConstructStatusCantBeNumericString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status code has to be an integer');

        new Response('-404.4');
    }

    public function testCanConstructWithHeaders(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame('Bar', $r->getHeaderLine('Foo'));
        static::assertSame(['Bar'], $r->getHeader('Foo'));
    }

    public function testCanConstructWithHeadersAsArray(): void
    {
        $r = new Response(200, [
            'Foo' => ['baz', 'bar'],
        ]);
        static::assertSame(['Foo' => ['baz', 'bar']], $r->getHeaders());
        static::assertSame('baz, bar', $r->getHeaderLine('Foo'));
        static::assertSame(['baz', 'bar'], $r->getHeader('Foo'));
    }

    public function testCanConstructWithBody(): void
    {
        $r = new Response(200, [], 'baz');
        static::assertSame('baz', (string) $r->getBody());
    }

    public function testNullBody(): void
    {
        $r = new Response(200, [], null);
        static::assertSame('', (string) $r->getBody());
    }

    public function testFalseyBody(): void
    {
        $r = new Response(200, [], '0');
        static::assertSame('0', (string) $r->getBody());
    }

    public function testCanConstructWithReason(): void
    {
        $r = new Response(200, [], null, '1.1', 'bar');
        static::assertSame('bar', $r->getReasonPhrase());

        $r = new Response(200, [], null, '1.1', '0');
        static::assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
    }

    public function testCanConstructWithProtocolVersion(): void
    {
        $r = new Response(200, [], null, '0.9');
        static::assertSame('0.9', $r->getProtocolVersion());

        $r = new Response(200, [], null, '1.0');
        static::assertSame('1.0', $r->getProtocolVersion());

        $r = new Response(200, [], null, '1.1');
        static::assertSame('1.1', $r->getProtocolVersion());

        $r = new Response(200, [], null, '2.0');
        static::assertSame('2.0', $r->getProtocolVersion());

        $r = new Response(200, [], null, '2');
        static::assertSame('2', $r->getProtocolVersion());

        $r = new Response(200, [], null, '3');
        static::assertSame('3', $r->getProtocolVersion());
    }

    public function testRaiseExceptionConstructWithProtocolVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Protocol Version must be 0.9 or 1.0 or 1.1 or 2 or 2.0 or 3');

        new Response(200, [], null, '1000');
    }

    public function testRaiseWithInvalidStatusCode(): void
    {
        $r = new Response();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status code has to be an integer between 100 and 799');
        $r->withStatus(1);
    }

    public function testWithStatusCodeAndNoReason(): void
    {
        $r = (new Response())->withStatus(201);
        static::assertSame(201, $r->getStatusCode());
        static::assertSame('Created', $r->getReasonPhrase());
    }

    public function testWithStatusCodeAndReason(): void
    {
        $r = (new Response())->withStatus(201, 'Foo');
        static::assertSame(201, $r->getStatusCode());
        static::assertSame('Foo', $r->getReasonPhrase());

        $r = (new Response())->withStatus(201, '0');
        static::assertSame(201, $r->getStatusCode());
        static::assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
    }

    public function testWithProtocolVersion(): void
    {
        $r = (new Response())->withProtocolVersion('1.0');
        static::assertSame('1.0', $r->getProtocolVersion());
    }

    public function testSameInstanceWhenSameProtocol(): void
    {
        $r = new Response();
        static::assertSame($r, $r->withProtocolVersion('1.1'));
    }

    public function testWithBody(): void
    {
        $b = (new Factory())->createStream('0');
        $r = (new Response())->withBody($b);
        static::assertSame('0', (string) $r->getBody());
    }

    public function testSameInstanceWhenSameBody(): void
    {
        $r = new Response();
        $b = $r->getBody();
        static::assertSame($r, $r->withBody($b));
    }

    public function testWithHeader(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('baZ', 'Bam');
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $r2->getHeaders());
        static::assertSame('Bam', $r2->getHeaderLine('baz'));
        static::assertSame(['Bam'], $r2->getHeader('baz'));
    }

    public function testWithHeaderAsArray(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('baZ', ['Bam', 'Bar']);
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $r2->getHeaders());
        static::assertSame('Bam, Bar', $r2->getHeaderLine('baz'));
        static::assertSame(['Bam', 'Bar'], $r2->getHeader('baz'));
    }

    public function testWithHeaderReplacesDifferentCase(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('foO', 'Bam');
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame(['foO' => ['Bam']], $r2->getHeaders());
        static::assertSame('Bam', $r2->getHeaderLine('foo'));
        static::assertSame(['Bam'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeader(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('foO', 'Baz');
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame(['Foo' => ['Bar', 'Baz']], $r2->getHeaders());
        static::assertSame('Bar, Baz', $r2->getHeaderLine('foo'));
        static::assertSame(['Bar', 'Baz'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeaderAsArray(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('foO', ['Baz', 'Bam']);
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $r2->getHeaders());
        static::assertSame('Bar, Baz, Bam', $r2->getHeaderLine('foo'));
        static::assertSame(['Bar', 'Baz', 'Bam'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeaderThatDoesNotExist(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('nEw', 'Baz');
        static::assertSame(['Foo' => ['Bar']], $r->getHeaders());
        static::assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $r2->getHeaders());
        static::assertSame('Baz', $r2->getHeaderLine('new'));
        static::assertSame(['Baz'], $r2->getHeader('new'));
    }

    public function testWithoutHeaderThatExists(): void
    {
        $r = new Response(200, ['Foo' => 'Bar', 'Baz' => 'Bam']);
        $r2 = $r->withoutHeader('foO');
        static::assertTrue($r->hasHeader('foo'));
        static::assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $r->getHeaders());
        static::assertFalse($r2->hasHeader('foo'));
        static::assertSame(['Baz' => ['Bam']], $r2->getHeaders());
    }

    public function testWithoutHeaderThatDoesNotExist(): void
    {
        $r = new Response(200, ['Baz' => 'Bam']);
        $r2 = $r->withoutHeader('foO');
        static::assertSame($r, $r2);
        static::assertFalse($r2->hasHeader('foo'));
        static::assertSame(['Baz' => ['Bam']], $r2->getHeaders());
    }

    public function testSameInstanceWhenRemovingMissingHeader(): void
    {
        $r = new Response();
        static::assertSame($r, $r->withoutHeader('foo'));
    }

    public function trimmedHeaderValues(): array
    {
        return [
            [new Response(200, ['OWS' => " \t \tFoo\t \t "])],
            [(new Response())->withHeader('OWS', " \t \tFoo\t \t ")],
            [(new Response())->withAddedHeader('OWS', " \t \tFoo\t \t ")],
        ];
    }

    public function testWithHeaderNameMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be non-empty string');

        $r = new Response();
        static::assertSame($r, $r->withHeader('', ''));
    }

    public function testWithHeaderValueArrayMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be a string or an array of strings, empty array given.');

        $r = new Response();
        static::assertSame($r, $r->withHeader('aze', []));
    }

    public function testWithHeaderValueStringMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be RFC 7230 compatible strings.');

        $r = new Response();
        static::assertSame($r, $r->withHeader('aze', [null]));
    }

    public function testWithAddedHeaderNameMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be non-empty string');

        $r = new Response();
        static::assertSame($r, $r->withAddedHeader('', ''));
    }

    public function testWithAddedHeaderNameMustHaveCorrectTypeRFC(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be RFC 7230 compatible string.');

        $r = new Response();
        static::assertSame($r, $r->withAddedHeader("a\t", ''));
    }

    public function testWithAddedHeaderValueMustHaveCorrectTypeRFC(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header value must be RFC 7230 compatible string.');

        $r = new Response();
        static::assertSame($r, $r->withAddedHeader('a', null));
    }

    public function testWithAddedHeaderValueArrayMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be a string or an array of strings, empty array given.');

        $r = new Response();
        static::assertSame($r, $r->withAddedHeader('aze', []));
    }

    public function testWithAddedHeaderValueStringMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be RFC 7230 compatible strings.');

        $r = new Response();
        static::assertSame($r, $r->withAddedHeader('aze', [null]));
    }

    public function testWithoutHeaderNameMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be non-empty string');

        $r = new Response();
        static::assertSame($r, $r->withoutHeader(''));
    }

    /**
     * @dataProvider trimmedHeaderValues
     *
     * @param Response $r
     */
    public function testHeaderValuesAreTrimmed(Response $r): void
    {
        static::assertSame(['OWS' => ['Foo']], $r->getHeaders());
        static::assertSame('Foo', $r->getHeaderLine('OWS'));
        static::assertSame(['Foo'], $r->getHeader('OWS'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSend(): void
    {
        $r = new Response(
            200,
            [
                'Content-Type' => 'text/plain;charset=UTF-8',
                'customName'   => 'mykey',
                'empty'        => ''
            ],
            $body = \uniqid('', true),
            '2'
        );
        $infos = $this->captureSend($r);
        static::assertSame('Content-Type: text/plain;charset=UTF-8', $infos['headers'][0]);
        static::assertSame('customName: mykey', $infos['headers'][1]);
        static::assertSame('empty:', $infos['headers'][2]);
        static::assertSame($body, $infos['body']);
    }

    private function captureSend(Response $response): array
    {
        \ob_start();
        $response->send();
        $output = \ob_get_clean();

        return ['headers' => xdebug_get_headers(), 'body' => $output];
    }
}
