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
        599 => 'Network Connect Timeout Error',
        701 => 'Meh',
        702 => 'Emacs',
        703 => 'Explosion',
        704 => 'Goto Fail',
        705 => 'I wrote the code and missed the necessary validation by an oversight (see 795)',
        706 => 'Delete Your Account',
        707 => 'Can\'t quit vi',
        710 => 'PHP',
        711 => 'Convenience Store',
        712 => 'NoSQL',
        718 => 'I am not a teapot',
        719 => 'Haskell',
        720 => 'Unpossible',
        721 => 'Known Unknowns',
        722 => 'Unknown Unknowns',
        723 => 'Tricky',
        724 => 'This line should be unreachable',
        725 => 'It works on my machine',
        726 => 'It\'s a feature, not a bug',
        727 => '32 bits is plenty',
        728 => 'It works in my timezone',
        730 => 'Fucking npm',
        731 => 'Fucking Rubygems',
        732 => 'Fucking Unic&#128169;de',
        733 => 'Fucking Deadlocks',
        734 => 'Fucking Deferreds',
        735 => 'Fucking IE',
        736 => 'Fucking Race Conditions',
        737 => 'FuckThreadsing',
        739 => 'Fucking Windows',
        740 => 'Got the brains trust on the case.',
        750 => 'Didn\'t bother to compile it',
        753 => 'Syntax Error',
        754 => 'Too many semi-colons',
        755 => 'Not enough semi-colons',
        756 => 'Insufficiently polite',
        757 => 'Excessively polite',
        759 => 'Unexpected "T_PAAMAYIM_NEKUDOTAYIM"',
        761 => 'Hungover',
        762 => 'Stoned',
        763 => 'Under-Caffeinated',
        764 => 'Over-Caffeinated',
        765 => 'Railscamp',
        766 => 'Sober',
        767 => 'Drunk',
        768 => 'Accidentally Took Sleeping Pills Instead Of Migraine Pills During Crunch Week',
        771 => 'Cached for too long',
        772 => 'Not cached long enough',
        773 => 'Not cached at all',
        774 => 'Why was this cached?',
        775 => 'Out of cash',
        776 => 'Error on the Exception',
        777 => 'Coincidence',
        778 => 'Off By One Error',
        779 => 'Off By Too Many To Count Error',
        780 => 'Project owner not responding',
        781 => 'Operations',
        782 => 'QA',
        783 => 'It was a customer request, honestly',
        784 => 'Management, obviously',
        785 => 'TPS Cover Sheet not attached',
        786 => 'Try it now',
        787 => 'Further Funding Required',
        788 => 'Designer\'s final designs weren\'t',
        789 => 'Not my department',
        791 => 'The Internet shut down due to copyright restrictions',
        792 => 'Climate change driven catastrophic weather event',
        793 => 'Zombie Apocalypse',
        794 => 'Someone let PG near a REPL',
        795 => '#heartbleed (see 705)',
        796 => 'Some DNS fuckery idno',
        797 => 'This is the last page of the Internet.  Go back',
        798 => 'I checked the db backups cupboard and the cupboard was bare',
        799 => 'End of the world'
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
        string $reason = null
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
        if (!\is_int($code) && !\is_string($code)) {
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
