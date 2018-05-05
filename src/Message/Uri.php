<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Class Uri.
 */
class Uri implements UriInterface
{
    /** @var array */
    protected static $schemes = [
        'http'  => 80,
        'https' => 443,
    ];

    protected static $charUnreserved = 'a-zA-Z0-9_\-\.~';

    /** @var string */
    protected static $charSubDelims = '!\$&\'\(\)\*\+,;=';

    /** @var string Uri scheme. */
    protected $scheme = '';

    /** @var string Uri user info. */
    protected $userInfo = '';

    /** @var string Uri host. */
    protected $host = '';

    /** @var int|null Uri port. */
    protected $port;

    /** @var string Uri path. */
    protected $path = '';

    /** @var string Uri query string. */
    protected $query = '';

    /** @var string Uri fragment. */
    protected $fragment = '';

    /**
     * Uri constructor.
     *
     * @param string $uri
     *
     * @throws InvalidArgumentException
     */
    public function __construct($uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new InvalidArgumentException("Unable to parse URI: $uri");
            }

            $this->applyParts($parts);
        }
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @return string
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @param $scheme
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withScheme($scheme): self
    {
        $scheme = $this->filterScheme($scheme);

        if ($scheme === $this->scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);

        return $new;
    }

    /**
     * @param      $user
     * @param null $password
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withUserInfo($user, $password = null): self
    {
        if (!is_string($user)) {
            throw new InvalidArgumentException('User must be a string');
        }

        if (!$this->isStringOrNull($password)) {
            throw new InvalidArgumentException('Password must be a string or NULL');
        }

        $info = $user;
        if ($password !== null && mb_strlen($password) > 0) {
            $info .= ':' . $password;
        }

        if ($info === $this->userInfo) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * @param $host
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withHost($host): self
    {
        $host = $this->filterHost($host);

        if ($host === $this->host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * @param $port
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withPort($port): self
    {
        $port = $this->filterPort($port);

        if ($port === $this->port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * @param $path
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withPath($path): self
    {
        $path = $this->filterPath($path);

        if ($path === $this->path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @param $query
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withQuery($query): self
    {
        $query = $this->filterQueryAndFragment($query);

        if ($query === $this->query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * @param $fragment
     *
     * @throws InvalidArgumentException
     *
     * @return Uri
     */
    public function withFragment($fragment): self
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($fragment === $this->fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    /**
     * @param array $parts
     *
     * @throws InvalidArgumentException
     */
    protected function applyParts(array $parts): void
    {
        $this->scheme = '';
        $this->userInfo = '';
        $this->host = '';
        $this->port = null;
        $this->path = '';
        $this->query = '';
        $this->fragment = '';

        if (array_key_exists('scheme', $parts)) {
            $this->scheme = $this->filterScheme($parts['scheme']);
        }

        if (array_key_exists('user', $parts)) {
            $this->userInfo = $parts['user'];
        }

        if (array_key_exists('host', $parts)) {
            $this->host = $this->filterHost($parts['host']);
        }

        if (array_key_exists('port', $parts)) {
            $this->port = $this->filterPort($parts['port']);
        }

        if (array_key_exists('path', $parts)) {
            $this->path = $this->filterPath($parts['path']);
        }

        if (array_key_exists('query', $parts)) {
            $this->query = $this->filterQueryAndFragment($parts['query']);
        }

        if (array_key_exists('fragment', $parts)) {
            $this->fragment = $this->filterQueryAndFragment($parts['fragment']);
        }

        if (array_key_exists('pass', $parts)) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     *
     * @return string
     */
    protected static function createUriString(
        string $scheme,
        string $authority,
        string $path,
        string $query,
        string $fragment
    ): string {
        $uri = '';
        if ($scheme !== '') {
            $uri .= $scheme . ':';
        }

        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        if ($path !== '') {
            if ($path[0] !== '/') {
                if ($authority !== '') {
                    $path = '/' . $path;
                }
            } elseif (isset($path[1]) && $path[1] === '/') {
                if ($authority === '') {
                    $path = '/' . ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ($query !== '') {
            $uri .= '?' . $query;
        }

        if ($fragment !== '') {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * @param string $scheme
     * @param int    $port
     *
     * @return bool
     */
    protected static function isNonStandardPort(string $scheme, int $port): bool
    {
        if (!array_key_exists($scheme, self::$schemes)) {
            return true;
        }

        if ($port !== self::$schemes[$scheme]) {
            return true;
        }

        return false;
    }

    /**
     * @param $scheme
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function filterScheme($scheme): string
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('Scheme must be a string');
        }

        return mb_strtolower($scheme);
    }

    /**
     * @param $host
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function filterHost($host): string
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException('Host must be a string');
        }

        return mb_strtolower($host);
    }

    /**
     * @param $port
     *
     * @throws InvalidArgumentException
     *
     * @return int|null
     */
    protected function filterPort($port): ?int
    {
        if ($port === null) {
            return null;
        }

        $port = (int) $port;
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf('Invalid port: %d. Must be between 1 and 65535', $port));
        }

        if (!self::isNonStandardPort($this->scheme, $port)) {
            return null;
        }

        return $port;
    }

    /**
     * @param $path
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function filterPath($path): string
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }

        return preg_replace_callback(
            $this->getPatternForFilteringPath(),
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }

    /**
     * @param $str
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function filterQueryAndFragment($str): string
    {
        if (!is_string($str)) {
            throw new InvalidArgumentException('Query and fragment must be a string');
        }

        return preg_replace_callback(
            $this->getPatternForFilteringQueryAndFragment(),
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
    }

    /**
     * @param array $match
     *
     * @return string
     */
    protected function rawurlencodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }

    /**
     * @param $param
     *
     * @return bool
     */
    protected function isStringOrNull($param): bool
    {
        return in_array(gettype($param), ['string', 'NULL'], true);
    }

    /**
     * @return string
     */
    protected function getPatternForFilteringPath(): string
    {
        return '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/';
    }

    /**
     * @return string
     */
    protected function getPatternForFilteringQueryAndFragment(): string
    {
        return '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
    }
}
