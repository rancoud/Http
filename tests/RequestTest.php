<?php

namespace Tests\Rancoud\Http;

use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class RequestTest extends TestCase
{
    public function testRequestUriMayBeString(): void
    {
        $r = new Request('GET', '/');
        $this->assertEquals('/', (string) $r->getUri());
    }

    public function testRequestUriMayBeUri(): void
    {
        $uri = new Uri('/');
        $r = new Request('GET', $uri);
        $this->assertSame($uri, $r->getUri());
    }

    public function testValidateRequestUri(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to parse URI: ///');

        new Request('GET', '///');
    }

    public function testCanConstructWithBody(): void
    {
        $r = new Request('GET', '/', [], 'baz');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertEquals('baz', (string) $r->getBody());
    }

    public function testNullBody(): void
    {
        $r = new Request('GET', '/', [], null);
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('', (string) $r->getBody());
    }

    public function testFalseyBody(): void
    {
        $r = new Request('GET', '/', [], '0');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('0', (string) $r->getBody());
    }

    public function testConstructorDoesNotReadStreamBody(): void
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects($this->never())
            ->method('__toString');

        $r = new Request('GET', '/', [], $body);
        $this->assertSame($body, $r->getBody());
    }

    public function testWithUri(): void
    {
        $r1 = new Request('GET', '/');
        $u1 = $r1->getUri();
        $u2 = new Uri('http://www.example.com');
        $r2 = $r1->withUri($u2);
        $this->assertNotSame($r1, $r2);
        $this->assertSame($u2, $r2->getUri());
        $this->assertSame($u1, $r1->getUri());
    }

    public function testSameInstanceWhenSameUri(): void
    {
        $r1 = new Request('GET', 'http://foo.com');
        $r2 = $r1->withUri($r1->getUri());
        $this->assertSame($r1, $r2);
    }

    public function testWithRequestTarget(): void
    {
        $r1 = new Request('GET', '/');
        $r2 = $r1->withRequestTarget('*');
        $this->assertEquals('*', $r2->getRequestTarget());
        $this->assertEquals('/', $r1->getRequestTarget());
    }

    public function testRequestTargetDoesNotAllowSpaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request target provided; cannot contain whitespace');

        $r1 = new Request('GET', '/');
        $r1->withRequestTarget('/foo bar');
    }

    public function testRequestTargetDefaultsToSlash(): void
    {
        $r1 = new Request('GET', '');
        $this->assertEquals('/', $r1->getRequestTarget());
        $r2 = new Request('GET', '*');
        $this->assertEquals('*', $r2->getRequestTarget());
        $r3 = new Request('GET', 'http://foo.com/bar baz/');
        $this->assertEquals('/bar%20baz/', $r3->getRequestTarget());
    }

    public function testBuildsRequestTarget(): void
    {
        $r1 = new Request('GET', 'http://foo.com/baz?bar=bam');
        $this->assertEquals('/baz?bar=bam', $r1->getRequestTarget());
    }

    public function testBuildsRequestTargetWithFalseyQuery(): void
    {
        $r1 = new Request('GET', 'http://foo.com/baz?0');
        $this->assertEquals('/baz?0', $r1->getRequestTarget());
    }

    public function testHostIsAddedFirst(): void
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', ['Foo' => 'Bar']);
        $this->assertEquals([
            'Host' => ['foo.com'],
            'Foo' => ['Bar'],
        ], $r->getHeaders());
    }

    public function testCanGetHeaderAsCsv(): void
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', [
            'Foo' => ['a', 'b', 'c'],
        ]);
        $this->assertEquals('a, b, c', $r->getHeaderLine('Foo'));
        $this->assertEquals('', $r->getHeaderLine('Bar'));
    }

    public function testHostIsNotOverwrittenWhenPreservingHost(): void
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', ['Host' => 'a.com']);
        $this->assertEquals(['Host' => ['a.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('http://www.foo.com/bar'), true);
        $this->assertEquals('a.com', $r2->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri(): void
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam');
        $this->assertEquals(['Host' => ['foo.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('http://www.baz.com/bar'));
        $this->assertEquals('www.baz.com', $r2->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders(): void
    {
        $r = new Request('GET', '', [
            'ZOO' => 'zoobar',
            'zoo' => ['foobar', 'zoobar'],
        ]);
        $this->assertEquals(['ZOO' => ['zoobar', 'foobar', 'zoobar']], $r->getHeaders());
        $this->assertEquals('zoobar, foobar, zoobar', $r->getHeaderLine('zoo'));
    }

    public function testSupportNumericHeaders(): void
    {
        $r = new Request('GET', '', [
            'Content-Length' => 200,
        ]);
        $this->assertSame(['Content-Length' => ['200']], $r->getHeaders());
        $this->assertSame('200', $r->getHeaderLine('Content-Length'));
    }

    public function testAddsPortToHeader(): void
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        $this->assertEquals('foo.com:8124', $r->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort(): void
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r = $r->withUri(new Uri('http://foo.com:8125/bar'));
        $this->assertEquals('foo.com:8125', $r->getHeaderLine('host'));
    }

    public function testWithMethod(): void
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r = $r->withMethod('PATCH');
        $this->assertEquals('PATCH', $r->getMethod());
    }

    public function testWithUriPreserveHostMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Preserve Host must be a boolean');

        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r->withUri(new Uri(), null);
    }

    public function testWithMethodMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method must be a string');

        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r->withMethod(null);
    }

    public function testWithMethodMustBeValidMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method NULL_METHOD is invalid');

        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r->withMethod('NULL_METHOD');
    }

    public function testCanHaveHeaderWithEmptyValue(): void
    {
        $r = new Request('GET', 'https://example.com/');
        $r = $r->withHeader('Foo', '');
        $this->assertEquals([''], $r->getHeader('Foo'));
    }
}
