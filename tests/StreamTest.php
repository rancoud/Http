<?php

namespace Tests\Rancoud\Http;

use Rancoud\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testConstructorInitializesProperties()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertInternalType('array', $stream->getMetadata());
        $this->assertEquals(4, $stream->getSize());
        $this->assertFalse($stream->eof());
        $stream->close();
    }

    public function testStreamClosesHandleOnDestruct()
    {
        $handle = fopen('php://temp', 'r');
        $stream = Stream::create($handle);
        unset($stream);
        $this->assertFalse(is_resource($handle));
    }

    public function testConvertsToString()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        $this->assertEquals('data', (string) $stream);
        $this->assertEquals('data', (string) $stream);
        $stream->close();
    }

    public function testGetsContents()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        $this->assertEquals('', $stream->getContents());
        $stream->seek(0);
        $this->assertEquals('data', $stream->getContents());
        $this->assertEquals('', $stream->getContents());
    }

    public function testChecksEof()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        $this->assertFalse($stream->eof());
        $stream->read(4);
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testGetSize()
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = Stream::create($handle);
        $this->assertEquals($size, $stream->getSize());
        // Load from cache
        $this->assertEquals($size, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent()
    {
        $h = fopen('php://temp', 'w+');
        $this->assertEquals(3, fwrite($h, 'foo'));
        $stream = Stream::create($h);
        $this->assertEquals(3, $stream->getSize());
        $this->assertEquals(4, $stream->write('test'));
        $this->assertEquals(7, $stream->getSize());
        $this->assertEquals(7, $stream->getSize());
        $stream->close();
    }

    public function testProvidesStreamPosition()
    {
        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $this->assertEquals(0, $stream->tell());
        $stream->write('foo');
        $this->assertEquals(3, $stream->tell());
        $stream->seek(1);
        $this->assertEquals(1, $stream->tell());
        $this->assertSame(ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testCanDetachStream()
    {
        $r = fopen('php://temp', 'w+');
        $stream = Stream::create($r);
        $stream->write('foo');
        $this->assertTrue($stream->isReadable());
        $this->assertSame($r, $stream->detach());
        $stream->detach();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());

        $throws = function (callable $fn) use ($stream) {
            try {
                $fn($stream);
                $this->fail();
            } catch (\Exception $e) {
            }
        };

        $throws(function ($stream) {
            $stream->read(10);
        });
        $throws(function ($stream) {
            $stream->write('bar');
        });
        $throws(function ($stream) {
            $stream->seek(10);
        });
        $throws(function ($stream) {
            $stream->tell();
        });
        $throws(function ($stream) {
            $stream->eof();
        });
        $throws(function ($stream) {
            $stream->getSize();
        });
        $throws(function ($stream) {
            $stream->getContents();
        });
        $this->assertSame('', (string) $stream);
        $stream->close();
    }

    public function testCloseClearProperties()
    {
        $handle = fopen('php://temp', 'r+');
        $stream = Stream::create($handle);
        $stream->close();

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->getSize());
        $this->assertEmpty($stream->getMetadata());
        $this->assertNull($stream->getMetadata("key"));
    }

    //---

    public function testSeekRaiseExceptionOffset()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be a int');

        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->seek('string');
    }

    public function testSeekRaiseExceptionWhence()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Whence must be a int');

        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->seek(1, 'string');
    }

    public function testSeekRaiseExceptionUnableToSeek()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek to stream position 1 with whence 90909090');

        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->seek(1, 90909090);
    }

    public function testWriteRaiseExceptionData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be a string');

        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->write(0);
    }

    public function testReadRaiseExceptionLength()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Length must be a int');

        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->read('string');
    }

    public function testGetMetadataRaiseExceptionKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be a string or NULL');

        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->getMetadata(45);
    }

    public function testGetMetadataKeyNonExist()
    {
        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $this->assertNull($stream->getMetadata('KeyNonExist'));
    }

    public function testCreateRaiseException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument to Stream::create() must be a string, resource or StreamInterface.');

        Stream::create(1);
    }
}
