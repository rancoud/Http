<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Rancoud\Http\Message\Stream;

class StreamTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $handle = \fopen('php://temp', 'r+');
        \fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertTrue($stream->isReadable());
        static::assertTrue($stream->isWritable());
        static::assertTrue($stream->isSeekable());
        static::assertSame('php://temp', $stream->getMetadata('uri'));
        static::assertIsArray($stream->getMetadata());
        static::assertSame(4, $stream->getSize());
        static::assertFalse($stream->eof());
        $stream->close();
    }

    public function testStreamClosesHandleOnDestruct(): void
    {
        $handle = \fopen('php://temp', 'r');
        $stream = Stream::create($handle);
        unset($stream);
        static::assertIsNotResource(\is_resource($handle));
    }

    public function testConvertsToString(): void
    {
        $handle = \fopen('php://temp', 'w+');
        \fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertSame('data', (string) $stream);
        static::assertSame('data', (string) $stream);
        $stream->close();
    }

    public function testGetsContents(): void
    {
        $handle = \fopen('php://temp', 'w+');
        \fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertSame('', $stream->getContents());
        $stream->seek(0);
        static::assertSame('data', $stream->getContents());
        static::assertSame('', $stream->getContents());
    }

    public function testGetsContentsRaiseException(): void
    {
        $handle = \fopen(\tempnam(\sys_get_temp_dir(), 'rancoud/http'), 'r+');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to read stream contents');

        $stream = Stream::create($handle);

        \fclose($handle);

        $stream->getContents();
    }

    public function testChecksEof(): void
    {
        $handle = \fopen('php://temp', 'w+');
        \fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertFalse($stream->eof());
        $stream->read(4);
        static::assertTrue($stream->eof());
        $stream->close();
    }

    public function testGetSize(): void
    {
        $size = \filesize(__FILE__);
        $handle = \fopen(__FILE__, 'r');
        $stream = Stream::create($handle);
        static::assertSame($size, $stream->getSize());
        // Load from cache
        static::assertSame($size, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent(): void
    {
        $h = \fopen('php://temp', 'w+');
        static::assertSame(3, \fwrite($h, 'foo'));
        $stream = Stream::create($h);
        static::assertSame(3, $stream->getSize());
        static::assertSame(4, $stream->write('test'));
        static::assertSame(7, $stream->getSize());
        static::assertSame(7, $stream->getSize());
        $stream->close();
    }

    public function testProvidesStreamPosition(): void
    {
        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        static::assertSame(0, $stream->tell());
        $stream->write('foo');
        static::assertSame(3, $stream->tell());
        $stream->seek(1);
        static::assertSame(1, $stream->tell());
        static::assertSame(\ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testCanDetachStream(): void
    {
        $this->expectException(\Throwable::class);

        $r = \fopen('php://temp', 'w+');
        $stream = Stream::create($r);
        $stream->write('foo');
        static::assertTrue($stream->isReadable());
        static::assertSame($r, $stream->detach());
        $stream->detach();

        static::assertFalse($stream->isReadable());
        static::assertFalse($stream->isWritable());
        static::assertFalse($stream->isSeekable());

        $throws = static function (callable $fn) use ($stream) {
            try {
                $fn($stream);
                static::fail();
            } catch (\Exception $e) {
            }
        };

        $throws(static function ($stream) {
            $stream->read(10);
        });
        $throws(static function ($stream) {
            $stream->write('bar');
        });
        $throws(static function ($stream) {
            $stream->seek(10);
        });
        $throws(static function ($stream) {
            $stream->tell();
        });
        $throws(static function ($stream) {
            $stream->eof();
        });
        $throws(static function ($stream) {
            $stream->getSize();
        });
        $throws(static function ($stream) {
            $stream->getContents();
        });

        $stream->close();
        $content = (string) $stream;

        static::assertNull($content);
    }

    public function testCloseClearProperties(): void
    {
        $handle = \fopen('php://temp', 'r+');
        $stream = Stream::create($handle);
        $stream->close();

        static::assertFalse($stream->isSeekable());
        static::assertFalse($stream->isReadable());
        static::assertFalse($stream->isWritable());
        static::assertNull($stream->getSize());
        static::assertEmpty($stream->getMetadata());
        static::assertNull($stream->getMetadata('key'));
    }

    public function testSeekRaiseExceptionOffset(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be a int');

        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->seek('string');
    }

    public function testSeekRaiseExceptionWhence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Whence must be a int');

        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->seek(1, 'string');
    }

    public function testSeekRaiseExceptionUnableToSeek(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek to stream position 1 with whence 90909090');

        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->seek(1, 90909090);
    }

    public function testReadRaiseExceptionLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Length must be a int');

        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->read('string');
    }

    public function testGetMetadataRaiseExceptionKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be a string or NULL');

        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        $stream->getMetadata(45);
    }

    public function testGetMetadataKeyNonExist(): void
    {
        $handle = \fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        static::assertNull($stream->getMetadata('KeyNonExist'));
    }

    public function testCreateRaiseException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument to Stream::create() must be a string, resource or StreamInterface.');

        Stream::create(1);
    }
}
