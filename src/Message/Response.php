<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Rancoud\Http\Message\Factory\StreamFactory;

/**
 * Class Response.
 */
class Response implements ResponseInterface
{
    use Message;

    /** @var array */
    public static $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirects',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'No Response',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        451 => 'Unavailable For Legal Reasons',
        456 => 'Unrecoverable Error',
        495 => 'SSL Certificate Error',
        496 => 'SSL Certificate Required',
        497 => 'HTTP Request Sent to HTTPS Port',
        499 => 'Client has closed connection',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not extended',
        511 => 'Network Authentication Required',
        520 => 'Unknown Error',
        521 => 'Web Server Is Down',
        522 => 'Connection Timed Out',
        523 => 'Origin Is Unreachable',
        524 => 'A Timeout Occurred',
        525 => 'SSL Handshake Failed',
        526 => 'Invalid SSL Certificate',
        527 => 'Railgun Error',
        599 => 'Network Connect Timeout Error'
    ];

    /** @var string */
    protected $reasonPhrase = '';

    /** @var int */
    protected $statusCode = 200;

    /**
     * Response constructor.
     *
     * @param int    $status
     * @param array  $headers
     * @param null   $body
     * @param string $version
     * @param null   $reason
     *
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        $reason = null
    ) {
        $this->statusCode = (int) $status;

        if ($body !== '' && $body !== null) {
            $this->stream = (new StreamFactory())->createStream($body);
        }

        $this->setHeaders($headers);
        if ($reason === null && array_key_exists($this->statusCode, self::$phrases)) {
            $this->reasonPhrase = self::$phrases[$status];
        } else {
            $this->reasonPhrase = (string) $reason;
        }

        $this->protocol = $this->validateProtocolVersion($version);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param        $code
     * @param string $reasonPhrase
     *
     * @throws InvalidArgumentException
     *
     * @return Response
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        if (!is_int($code) && !is_string($code)) {
            throw new InvalidArgumentException('Status code has to be an integer');
        }

        $code = (int) $code;
        if (!array_key_exists($code, self::$phrases)) {
            throw new InvalidArgumentException('Status code has to be an integer between 100 and 599');
        }

        $new = clone $this;
        $new->statusCode = (int) $code;
        if ($reasonPhrase === '' && array_key_exists($new->statusCode, self::$phrases)) {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     */
    public function send()
    {
        $httpLine = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );

        header($httpLine, true, $this->getStatusCode());

        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $stream = $this->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            echo $stream->read(1024 * 8);
        }
    }
}
