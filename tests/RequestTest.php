<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Rancoud\Http\Message\Request;
use Rancoud\Http\Message\Uri;

class RequestTest extends TestCase
{
    public function testRequestUriMayBeString(): void
    {
        $r = new Request('GET', '/');
        static::assertSame('/', (string) $r->getUri());
    }

    public function testRequestUriMayBeUri(): void
    {
        $uri = new Uri('/');
        $r = new Request('GET', $uri);
        static::assertSame($uri, $r->getUri());
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
        static::assertSame('baz', (string) $r->getBody());
    }

    public function testNullBody(): void
    {
        $r = new Request('GET', '/', [], null);
        static::assertSame('', (string) $r->getBody());
    }

    public function testFalseyBody(): void
    {
        $r = new Request('GET', '/', [], '0');
        static::assertSame('0', (string) $r->getBody());
    }

    public function testConstructorDoesNotReadStreamBody(): void
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects(static::never())->method('__toString');

        $r = new Request('GET', '/', [], $body);
        static::assertSame($body, $r->getBody());
    }

    public function testWithUri(): void
    {
        $r1 = new Request('GET', '/');
        $u1 = $r1->getUri();
        $u2 = new Uri('https://www.example.com');
        $r2 = $r1->withUri($u2);
        static::assertNotSame($r1, $r2);
        static::assertSame($u2, $r2->getUri());
        static::assertSame($u1, $r1->getUri());
    }

    public function testSameInstanceWhenSameUri(): void
    {
        $r1 = new Request('GET', 'https://foo.com');
        $r2 = $r1->withUri($r1->getUri());
        static::assertSame($r1, $r2);
    }

    public function testWithRequestTarget(): void
    {
        $r1 = new Request('GET', '/');
        $r2 = $r1->withRequestTarget('*');
        static::assertSame('*', $r2->getRequestTarget());
        static::assertSame('/', $r1->getRequestTarget());
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
        static::assertSame('/', $r1->getRequestTarget());
        $r2 = new Request('GET', '*');
        static::assertSame('*', $r2->getRequestTarget());
        $r3 = new Request('GET', 'https://foo.com/bar baz/');
        static::assertSame('/bar%20baz/', $r3->getRequestTarget());
    }

    public function testBuildsRequestTarget(): void
    {
        $r1 = new Request('GET', 'https://foo.com/baz?bar=bam');
        static::assertSame('/baz?bar=bam', $r1->getRequestTarget());
    }

    public function testBuildsRequestTargetWithFalseyQuery(): void
    {
        $r1 = new Request('GET', 'https://foo.com/baz?0');
        static::assertSame('/baz?0', $r1->getRequestTarget());
    }

    public function testHostIsAddedFirst(): void
    {
        $r = new Request('GET', 'https://foo.com/baz?bar=bam', ['Foo' => 'Bar']);
        static::assertSame([
            'Host' => ['foo.com'],
            'Foo'  => ['Bar'],
        ], $r->getHeaders());
    }

    public function testCanGetHeaderAsCsv(): void
    {
        $r = new Request('GET', 'https://foo.com/baz?bar=bam', [
            'Foo' => ['a', 'b', 'c'],
        ]);
        static::assertSame('a, b, c', $r->getHeaderLine('Foo'));
        static::assertSame('', $r->getHeaderLine('Bar'));
    }

    public function testHostIsNotOverwrittenWhenPreservingHost(): void
    {
        $r = new Request('GET', 'https://foo.com/baz?bar=bam', ['Host' => 'a.com']);
        static::assertSame(['Host' => ['a.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('https://www.foo.com/bar'), true);
        static::assertSame('a.com', $r2->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri(): void
    {
        $r = new Request('GET', 'https://foo.com/baz?bar=bam');
        static::assertSame(['Host' => ['foo.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('https://www.baz.com/bar'));
        static::assertSame('www.baz.com', $r2->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders(): void
    {
        $r = new Request('GET', '', [
            'ZOO' => 'zoobar',
            'zoo' => ['foobar', 'zoobar'],
        ]);
        static::assertSame(['ZOO' => ['zoobar', 'foobar', 'zoobar']], $r->getHeaders());
        static::assertSame('zoobar, foobar, zoobar', $r->getHeaderLine('zoo'));
    }

    public function testSupportNumericHeaders(): void
    {
        $r = new Request('GET', '', [
            'Content-Length' => 200,
        ]);
        static::assertSame(['Content-Length' => ['200']], $r->getHeaders());
        static::assertSame('200', $r->getHeaderLine('Content-Length'));
    }

    public function testAddsPortToHeader(): void
    {
        $r = new Request('GET', 'https://foo.com:8124/bar');
        static::assertSame('foo.com:8124', $r->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort(): void
    {
        $r = new Request('GET', 'https://foo.com:8124/bar');
        $r = $r->withUri(new Uri('https://foo.com:8125/bar'));
        static::assertSame('foo.com:8125', $r->getHeaderLine('host'));
    }

    public function testWithMethod(): void
    {
        $r = new Request('GET', 'https://foo.com:8124/bar');
        $r = $r->withMethod('PATCH');
        static::assertSame('PATCH', $r->getMethod());
    }

    public function testWithUriPreserveHostMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Preserve Host must be a boolean');

        $r = new Request('GET', 'https://foo.com:8124/bar');
        $r->withUri(new Uri(), null);
    }

    public function testWithMethodMustHaveCorrectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method must be a string');

        $r = new Request('GET', 'https://foo.com:8124/bar');
        $r->withMethod(null);
    }

    public function testWithMethodMustBeValidMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method NULL_METHOD is invalid');

        $r = new Request('GET', 'https://foo.com:8124/bar');
        $r->withMethod('NULL_METHOD');
    }

    public function testCanHaveHeaderWithEmptyValue(): void
    {
        $r = new Request('GET', 'https://example.com/');
        $r = $r->withHeader('Foo', '');
        static::assertSame([''], $r->getHeader('Foo'));
    }

    public function testHostHeaderIsAlwaysFirstHeaders(): void
    {
        $r = new Request('GET', 'https://example.com/');
        static::assertSame(['Host' => [0 => 'example.com']], $r->getHeaders());
        $r = $r->withoutHeader('Host');
        static::assertSame([], $r->getHeaders());
        $r = $r->withHeader('Foo', 'Bar');
        static::assertSame(['Foo' => [0 => 'Bar']], $r->getHeaders());
        $r = $r->withUri(new Uri('https://example.com/'));
        static::assertNotSame(['Foo' => [0 => 'Bar'], 'Host' => [0 => 'example.com']], $r->getHeaders());
        static::assertSame(['Host' => [0 => 'example.com'], 'Foo' => [0 => 'Bar']], $r->getHeaders());
    }
}
