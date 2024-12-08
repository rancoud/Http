<?php

namespace tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rancoud\Http\Message\Stream;
use Rancoud\Http\Message\UploadedFile;

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

    public static function invalidStreams(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    /**
     * @dataProvider invalidStreams
     *
     * @param $streamOrFile
     */
    #[DataProvider('invalidStreams')]
    public function testRaisesExceptionOnInvalidStreamOrFile($streamOrFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream or file provided for UploadedFile');

        new UploadedFile($streamOrFile, 0, \UPLOAD_ERR_OK);
    }

    public static function invalidErrorStatuses(): array
    {
        return [
            'negative' => [-1],
            'too-big'  => [9],
        ];
    }

    /**
     * @dataProvider invalidErrorStatuses
     *
     * @param $status
     */
    #[DataProvider('invalidErrorStatuses')]
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

    public static function invalidMovePaths(): array
    {
        return [
            'empty'  => [''],
        ];
    }

    /**
     * @dataProvider invalidMovePaths
     *
     * @param $path
     */
    #[DataProvider('invalidMovePaths')]
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

    public static function nonOkErrorStatus(): array
    {
        return [
            'UPLOAD_ERR_INI_SIZE'   => [\UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE'  => [\UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL'    => [\UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE'    => [\UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [\UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [\UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION'  => [\UPLOAD_ERR_EXTENSION],
        ];
    }

    /**
     * @dataProvider nonOkErrorStatus
     *
     * @param $status
     */
    #[DataProvider('nonOkErrorStatus')]
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        static::assertSame($status, $uploadedFile->getError());
    }

    /**
     * @dataProvider nonOkErrorStatus
     *
     * @param $status
     */
    #[DataProvider('nonOkErrorStatus')]
    public function testMoveToRaisesExceptionWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->moveTo(__DIR__ . '/' . \uniqid('', true));
    }

    /**
     * @dataProvider nonOkErrorStatus
     *
     * @param $status
     */
    #[DataProvider('nonOkErrorStatus')]
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
