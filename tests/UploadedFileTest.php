<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rancoud\Http\Message\Stream;
use Rancoud\Http\Message\UploadedFile;

/** @internal */
class UploadedFileTest extends TestCase
{
    protected array $cleanup;

    protected function setUp(): void
    {
        $this->cleanup = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $file) {
            if (\is_scalar($file) && \file_exists($file)) {
                \unlink($file);
            }
        }
    }

    public static function provideInvalidStreamsDataCases(): iterable
    {
        yield 'null'   => [null];

        yield 'true'   => [true];

        yield 'false'  => [false];

        yield 'int'    => [1];

        yield 'float'  => [1.1];

        yield 'array'  => [['filename']];

        yield 'object' => [(object) ['filename']];
    }

    #[DataProvider('provideInvalidStreamsDataCases')]
    public function testRaisesExceptionOnInvalidStreamOrFile($streamOrFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream or file provided for UploadedFile');

        new UploadedFile($streamOrFile, 0, \UPLOAD_ERR_OK);
    }

    public static function provideInvalidErrorStatusesDataCases(): iterable
    {
        yield 'negative' => [-1];

        yield 'too-big'  => [9];
    }

    #[DataProvider('provideInvalidErrorStatusesDataCases')]
    public function testRaisesExceptionOnInvalidErrorStatus($status): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('status');

        new UploadedFile(\fopen('php://temp', 'wb+'), 0, $status);
    }

    public function testGetStreamWithFile(): void
    {
        $stream = Stream::createFromFile(__DIR__ . \DIRECTORY_SEPARATOR . 'noise.jpg');
        $upload = new UploadedFile(__DIR__ . \DIRECTORY_SEPARATOR . 'noise.jpg', 0, \UPLOAD_ERR_OK);

        static::assertSame($stream->getSize(), $upload->getStream()->getSize());
        static::assertSame($stream->getContents(), $upload->getStream()->getContents());
    }

    public function testGetStreamReturnsOriginalStreamObject(): void
    {
        $stream = Stream::create();
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        static::assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream(): void
    {
        $stream = \fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();

        static::assertSame($stream, $uploadStream);
    }

    public function testSuccessful(): void
    {
        $stream = Stream::create('Foo bar!');
        $upload = new UploadedFile($stream, $stream->getSize(), \UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        static::assertSame($stream->getSize(), $upload->getSize());
        static::assertSame('filename.txt', $upload->getClientFilename());
        static::assertSame('text/plain', $upload->getClientMediaType());
        static::assertNull($upload->getFilename());

        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'successful');
        $upload->moveTo($to);
        static::assertFileExists($to);
        static::assertSame($stream->__toString(), \file_get_contents($to));
    }

    public static function provideInvalidMovePathsDataCases(): iterable
    {
        yield 'empty'  => [''];
    }

    #[DataProvider('provideInvalidMovePathsDataCases')]
    public function testMoveRaisesExceptionForInvalidPath($path): void
    {
        $stream = Stream::create('Foo bar!');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->cleanup[] = $path;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('path');
        $upload->moveTo($path);
    }

    public function testMoveCannotBeCalledMoreThanOnce(): void
    {
        $stream = Stream::create('Foo bar!');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        static::assertFileExists($to);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('moved');
        $upload->moveTo($to);
    }

    public function testCannotRetrieveStreamAfterMove(): void
    {
        $stream = Stream::create('Foo bar!');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        static::assertFileExists($to);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('moved');
        $upload->getStream();
    }

    public static function provideNonOkErrorStatusDataCases(): iterable
    {
        yield 'UPLOAD_ERR_INI_SIZE'   => [\UPLOAD_ERR_INI_SIZE];

        yield 'UPLOAD_ERR_FORM_SIZE'  => [\UPLOAD_ERR_FORM_SIZE];

        yield 'UPLOAD_ERR_PARTIAL'    => [\UPLOAD_ERR_PARTIAL];

        yield 'UPLOAD_ERR_NO_FILE'    => [\UPLOAD_ERR_NO_FILE];

        yield 'UPLOAD_ERR_NO_TMP_DIR' => [\UPLOAD_ERR_NO_TMP_DIR];

        yield 'UPLOAD_ERR_CANT_WRITE' => [\UPLOAD_ERR_CANT_WRITE];

        yield 'UPLOAD_ERR_EXTENSION'  => [\UPLOAD_ERR_EXTENSION];
    }

    #[DataProvider('provideNonOkErrorStatusDataCases')]
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        static::assertSame($status, $uploadedFile->getError());
    }

    #[DataProvider('provideNonOkErrorStatusDataCases')]
    public function testMoveToRaisesExceptionWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->moveTo(__DIR__ . '/' . \uniqid('', true));
    }

    #[DataProvider('provideNonOkErrorStatusDataCases')]
    public function testGetStreamRaisesExceptionWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->getStream();
    }

    public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided(): void
    {
        $this->cleanup[] = $from = \tempnam(\sys_get_temp_dir(), 'copy_from');
        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'copy_to');

        \copy(__FILE__, $from);

        $uploadedFile = new UploadedFile($from, 100, \UPLOAD_ERR_OK, \basename($from), 'text/plain');
        $uploadedFile->moveTo($to);

        static::assertFileEquals(__FILE__, $to);
    }

    public function testConstructor(): void
    {
        $params = [
            'filename' => 'my_filename',
            'size'     => 100,
            'error'    => \UPLOAD_ERR_OK,
        ];

        $uploadedFile = new UploadedFile($params['filename'], $params['size'], $params['error']);
        static::assertSame($params['filename'], $uploadedFile->getFilename());
        static::assertSame($params['size'], $uploadedFile->getSize());
        static::assertSame($params['error'], $uploadedFile->getError());
    }
}
