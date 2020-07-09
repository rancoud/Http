# Http Package

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rancoud/http)
[![Packagist Version](https://img.shields.io/packagist/v/rancoud/http)](https://packagist.org/packages/rancoud/http)
[![Packagist Downloads](https://img.shields.io/packagist/dt/rancoud/http)](https://packagist.org/packages/rancoud/http)
[![Composer dependencies](https://img.shields.io/badge/dependencies-0-brightgreen)](https://github.com/rancoud/http/blob/master/composer.json)
[![Test workflow](https://img.shields.io/github/workflow/status/rancoud/http/test?label=test&logo=github)](https://github.com/rancoud/http/actions?workflow=test)
[![Codecov](https://img.shields.io/codecov/c/github/rancoud/http?logo=codecov)](https://codecov.io/gh/rancoud/http)
[![composer.lock](https://poser.pugx.org/rancoud/http/composerlock)](https://packagist.org/packages/rancoud/http)

Heavily based on [Nyholm](https://github.com/nyholm) works from the psr7 repository [https://github.com/nyholm/psr7](https://github.com/nyholm/psr7)

Http with PSR 7 15 17 18.  

## Installation
```php
composer require rancoud/http
```

## How to use it?
```php
$factory = new Rancoud\Http\Message\Factory();
$request = $factory->createRequest('GET', 'https://example.com');
$stream = (new Rancoud\Http\Message\Factory())->createStream('foobar');
```

## Client
### Methods
* sendRequest(request: RequestInterface): ResponseInterface
* setCaInfosPath([infos: string = null], [path: string = null]): void
* disableSSLVerification(): void
* enableSSLVerification(): void

## Factory
### Methods
* createRequest(method: string, uri: mixed): RequestInterface  
* createResponse([code: int = 200], [reasonPhrase: string = '']): ResponseInterface  
* createResponseBody([code: int = 200], [body: mixed = null]): ResponseInterface  
* createRedirection(location: string): ResponseInterface  
* createStream([content: string = '']): StreamInterface  
* createStreamFromFile(filename: string, [mode: string = 'r']): StreamInterface  
* createStreamFromResource(resource: mixed): StreamInterface  
* createUploadedFile(stream: StreamInterface, [size: int = null], [error: int = \UPLOAD_ERR_OK], [clientFilename: string = null], [clientMediaType: string = null]): UploadedFileInterface  
* createUri([uri: string = '']): UriInterface  
* createUriFromArray(server: array): UriInterface  
* createServerRequest(method: string, uri: mixed, [serverParams: array = []]): ServerRequestInterface  
* createServerRequestFromArray(server: array): ServerRequestInterface  
* createServerRequestFromArrays(server: array, headers: array, cookie: array, get: array, post: array, files: array): ServerRequestInterface  
* createServerRequestFromGlobals(): ServerRequestInterface  

## Request
### Constructor
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| method | string | HTTP method |
| uri | mixed | Uri |

#### Optionnals
| Parameter | Type | Default value | Description |
| --- | --- | --- | --- |
| headers | array | [] | Request headers |
| body | mixed | [] | Request body |
| version | string | '1.1' | HTTP protocol version |

### Methods
* getBody(): StreamInterface  
* getHeader(name: string): array  
* getHeaderLine(name: string): string  
* getHeaders(): array  
* getMethod(): string  
* getProtocolVersion(): string  
* getRequestTarget(): string  
* getUri(): UriInterface  
* hasHeader(name: string): bool  
* withAddedHeader(name: string, value: mixed): self  
* withBody(body: StreamInterface): self  
* withHeader(name: string, value: mixed): self  
* withMethod(method: string): self  
* withoutHeader(name: string): self  
* withProtocolVersion(version: string): self  
* withRequestTarget(requestTarget: string): self  
* withUri(uri: UriInterface, [preserveHost: bool = false]): self  

### HTTP Methods supported
* ACL  
* BASELINE-CONTROL  
* BCOPY  
* BDELETE  
* BIND  
* BMOVE  
* BPROPFIND  
* BPROPPATCH  
* CHECKIN  
* CHECKOUT  
* CONNECT  
* COPY  
* DELETE  
* GET  
* HEAD  
* LABEL  
* LINK  
* LOCK  
* M-SEARCH  
* MERGE  
* MKACTIVITY  
* MKCALENDAR  
* MKCOL  
* MKREDIRECTREF  
* MKWORKSPACE  
* MOVE  
* NOTIFY  
* OPTIONS  
* ORDERPATCH  
* PATCH  
* POLL  
* POST  
* PRI  
* PROPFIND  
* PROPPATCH  
* PURGE  
* PUT  
* REBIND  
* REPORT  
* SEARCH  
* SUBSCRIBE  
* TRACE  
* UNBIND  
* UNCHECKOUT  
* UNLINK  
* UNLOCK  
* UNSUBSCRIBE  
* UPDATE  
* UPDATEREDIRECTREF  
* VERSION-CONTROL  
* VIEW  
* X-MS-ENUMATTS  

## Response
### Constructor
#### Optionnals
| Parameter | Type | Default value | Description |
| --- | --- | --- | --- |
| status | int | 200 | Status code |
| headers | array | [] | Response headers |
| body | mixed | [] | Response body |
| version | string | '1.1' | HTTP protocol version |
| reason | string | null | String send after status code |

### Methods
* getBody(): StreamInterface  
* getHeader(name: string): array  
* getHeaderLine(name: string): string  
* getHeaders(): array  
* getProtocolVersion(): string  
* getReasonPhrase(): string  
* getStatusCode(): int  
* hasHeader(name: string): bool  
* send([bodyChunkSize: int = 8192]): void  
* withAddedHeader(name: string, value: mixed): self  
* withBody(body: StreamInterface): self  
* withHeader(name: string, value: mixed): self  
* withoutHeader(name: string): self  
* withProtocolVersion(version: string): self  
* withStatus(code: int, [reasonPhrase: string = '']): self  

### Status Code and Reasons Phrases supported
* 100 => Continue  
* 101 => Switching Protocols  
* 102 => Processing  
* 103 => Early Hints  
* 200 => OK  
* 201 => Created  
* 202 => Accepted  
* 203 => Non-Authoritative Information  
* 204 => No Content  
* 205 => Reset Content  
* 206 => Partial Content  
* 207 => Multi-status  
* 208 => Already Reported  
* 210 => Content Different  
* 226 => IM Used  
* 300 => Multiple Choices  
* 301 => Moved Permanently  
* 302 => Found  
* 303 => See Other  
* 304 => Not Modified  
* 305 => Use Proxy  
* 306 => Switch Proxy  
* 307 => Temporary Redirect  
* 308 => Permanent Redirect  
* 310 => Too many Redirects  
* 400 => Bad Request  
* 401 => Unauthorized  
* 402 => Payment Required  
* 403 => Forbidden  
* 404 => Not Found  
* 405 => Method Not Allowed  
* 406 => Not Acceptable  
* 407 => Proxy Authentication Required  
* 408 => Request Timeout  
* 409 => Conflict  
* 410 => Gone  
* 411 => Length Required  
* 412 => Precondition Failed  
* 413 => Payload Too Large  
* 414 => URI Too Long  
* 415 => Unsupported Media Type  
* 416 => Range Not Satisfiable  
* 417 => Expectation Failed  
* 418 => I'm a teapot  
* 421 => Misdirected Request  
* 422 => Unprocessable Entity  
* 423 => Locked  
* 424 => Failed Dependency  
* 425 => Unordered Collection  
* 426 => Upgrade Required  
* 428 => Precondition Required  
* 429 => Too Many Requests  
* 431 => Request Header Fields Too Large  
* 444 => No Response  
* 449 => Retry With  
* 450 => Blocked by Windows Parental Controls  
* 451 => Unavailable For Legal Reasons  
* 456 => Unrecoverable Error  
* 495 => SSL Certificate Error  
* 496 => SSL Certificate Required  
* 497 => HTTP Request Sent to HTTPS Port  
* 499 => Client has closed connection  
* 500 => Internal Server Error  
* 501 => Not Implemented  
* 502 => Bad Gateway  
* 503 => Service Unavailable  
* 504 => Gateway Timeout  
* 505 => HTTP Version Not Supported  
* 506 => Variant Also Negotiates  
* 507 => Insufficient Storage  
* 508 => Loop Detected  
* 509 => Bandwidth Limit Exceeded  
* 510 => Not extended  
* 511 => Network Authentication Required  
* 520 => Unknown Error  
* 521 => Web Server Is Down  
* 522 => Connection Timed Out  
* 523 => Origin Is Unreachable  
* 524 => A Timeout Occurred  
* 525 => SSL Handshake Failed  
* 526 => Invalid SSL Certificate  
* 527 => Railgun Error  
* 599 => Network Connect Timeout Error  
* 701 => Meh  
* 702 => Emacs  
* 703 => Explosion  
* 704 => Goto Fail  
* 705 => I wrote the code and missed the necessary validation by an oversight (see 795)  
* 706 => Delete Your Account  
* 707 => Can't quit vi  
* 710 => PHP  
* 711 => Convenience Store  
* 712 => NoSQL  
* 718 => I am not a teapot  
* 719 => Haskell  
* 720 => Unpossible  
* 721 => Known Unknowns  
* 722 => Unknown Unknowns  
* 723 => Tricky  
* 724 => This line should be unreachable  
* 725 => It works on my machine  
* 726 => It's a feature, not a bug  
* 727 => 32 bits is plenty  
* 728 => It works in my timezone  
* 730 => Fucking npm  
* 731 => Fucking Rubygems  
* 732 => Fucking Unic&#128169;de  
* 733 => Fucking Deadlocks  
* 734 => Fucking Deferreds  
* 735 => Fucking IE  
* 736 => Fucking Race Conditions  
* 737 => FuckThreadsing  
* 739 => Fucking Windows  
* 740 => Got the brains trust on the case.  
* 750 => Didn't bother to compile it  
* 753 => Syntax Error  
* 754 => Too many semi-colons  
* 755 => Not enough semi-colons  
* 756 => Insufficiently polite  
* 757 => Excessively polite  
* 759 => Unexpected "T_PAAMAYIM_NEKUDOTAYIM"  
* 761 => Hungover  
* 762 => Stoned  
* 763 => Under-Caffeinated  
* 764 => Over-Caffeinated  
* 765 => Railscamp  
* 766 => Sober  
* 767 => Drunk  
* 768 => Accidentally Took Sleeping Pills Instead Of Migraine Pills During Crunch Week  
* 771 => Cached for too long  
* 772 => Not cached long enough  
* 773 => Not cached at all  
* 774 => Why was this cached?  
* 775 => Out of cash  
* 776 => Error on the Exception  
* 777 => Coincidence  
* 778 => Off By One Error  
* 779 => Off By Too Many To Count Error  
* 780 => Project owner not responding  
* 781 => Operations  
* 782 => QA  
* 783 => It was a customer request, honestly  
* 784 => Management, obviously  
* 785 => TPS Cover Sheet not attached  
* 786 => Try it now  
* 787 => Further Funding Required  
* 788 => Designer's final designs weren't  
* 789 => Not my department  
* 791 => The Internet shut down due to copyright restrictions  
* 792 => Climate change driven catastrophic weather event  
* 793 => Zombie Apocalypse  
* 794 => Someone let PG near a REPL  
* 795 => #heartbleed (see 705)  
* 796 => Some DNS fuckery idno  
* 797 => This is the last page of the Internet.  Go back  
* 798 => I checked the db backups cupboard and the cupboard was bare  
* 799 => End of the world  

## ServerRequest
### Constructor
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| method | string | HTTP method |
| uri | mixed | Uri |

#### Optionnals
| Parameter | Type | Default value | Description |
| --- | --- | --- | --- |
| headers | array | [] | Request headers |
| body | mixed | [] | Request body |
| version | string | '1.1' | HTTP protocol version |
| serverParams | array | [] | Server parameters |

### Methods
* getAttribute(name: string, [default: mixed = null]): mixed|null  
* getAttributes(): array  
* getBody(): StreamInterface  
* getCookieParams(): array  
* getHeader(name: string): array  
* getHeaderLine(name: string): string  
* getHeaders(): array  
* getMethod(): string  
* getParsedBody(): array|null|object  
* getProtocolVersion(): string  
* getQueryParams(): array  
* getRequestTarget(): string  
* getServerParams(): array  
* getUploadedFiles(): array  
* getUri(): UriInterface  
* hasHeader(name: string): bool  
* withAddedHeader(name: string, value: mixed): self  
* withAttribute(name: string, value: mixed): self  
* withBody(body: StreamInterface): self  
* withCookieParams(cookies: array): self
* withHeader(name: string, value: mixed): self  
* withMethod(method: string): self  
* withoutAttribute(name: string): self  
* withoutHeader(name: string): self  
* withParsedBody(data: array|null|object): self
* withProtocolVersion(version: string): self  
* withQueryParams(query: array): self  
* withRequestTarget(requestTarget: string): self  
* withUploadedFiles(uploadedFiles: array): self
* withUri(uri: UriInterface, [preserveHost: bool = false]): self  

## Stream
### Methods
* __destruct(): void  
* __toString(): string  
* close(): void  
* detach(): null|resource  
* eof(): bool  
* getContents(): string  
* getMetadata([key: string|null = null]): ?array  
* getSize(): ?int  
* isReadable(): bool  
* isSeekable(): bool  
* isWritable(): bool  
* read(length: int): string  
* rewind(): void  
* seek(offset: int, [whence: int = \SEEK_SET]): void  
* tell(): int  
* write(string: string): bool|int  

### Static Methods
* create([content: string = '']): StreamInterface  

## UploadedFile
### Constructor
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| streamOrFile | mixed | Stream or file |
| size | int | Filesize |
| errorStatus | int | Upload errors |

#### Optionnals
| Parameter | Type | Default value | Description |
| --- | --- | --- | --- |
| clientFilename | string\|null | null | Filename |
| clientMediaType | string\|null | null | Media type |

### Methods
* getClientFilename(): ?string  
* getClientMediaType(): ?string  
* getError(): int  
* getSize(): ?int  
* getStream(): StreamInterface  
* moveTo(targetPath: string): void  

### Upload errors supported
* \UPLOAD_ERR_OK  
* \UPLOAD_ERR_INI_SIZE  
* \UPLOAD_ERR_FORM_SIZE  
* \UPLOAD_ERR_PARTIAL  
* \UPLOAD_ERR_NO_FILE  
* \UPLOAD_ERR_NO_TMP_DIR  
* \UPLOAD_ERR_CANT_WRITE  
* \UPLOAD_ERR_EXTENSION  

## Uri
### Constructor
#### Optionnals
| Parameter | Type | Default value | Description |
| --- | --- | --- | --- |
| uri | string | '' | Uri |

### Methods
* __toString(): string  
* getAuthority(): string  
* getFragment(): string  
* getHost(): string  
* getPath(): string  
* getPort(): ?int  
* getQuery(): string  
* getScheme(): string  
* getUserInfo(): string  
* withFragment(fragment: string): self  
* withHost(host: string): self  
* withPath(path: string): self  
* withPort(port: int|null): self  
* withQuery(query: string): self  
* withScheme(scheme: string): self  
* withUserInfo(user: string, [password: string|null = null]): self  

## How to Dev
`composer ci` for php-cs-fixer and phpunit and coverage  
`composer lint` for php-cs-fixer  
`composer test` for phpunit and coverage  