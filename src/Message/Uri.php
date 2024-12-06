<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * Class Uri.
 */
class Uri implements UriInterface
{
    /** @var array */
    protected const SCHEMES = [
        'http'  => 80,
        'https' => 443,
    ];

    /** @var string */
    protected const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /** @var string */
    protected const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    protected string $scheme = '';

    protected string $userInfo = '';

    protected string $host = '';

    protected ?int $port = null;

    protected string $path = '';

    protected string $query = '';

    protected string $fragment = '';

    /**
     * @param string $uri
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = \parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException(\sprintf('Unable to parse URI: %s', $uri));
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
     * @param string $scheme
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withScheme(string $scheme): self
    {
        $scheme = $this->filterScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);

        return $new;
    }

    /**
     * @param string      $user
     * @param string|null $password
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withUserInfo(string $user, ?string $password = null): self
    {
        $info = $this->filterUser($user);
        $password = $this->filterPass($password);

        if ($password !== null && $password !== '') {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * @param string $host
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withHost(string $host): self
    {
        $host = $this->filterHost($host);

        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * @param int|null $port
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withPort(?int $port): self
    {
        $port = $this->filterPort($port);

        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * @param string $path
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withPath(string $path): self
    {
        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @param string $query
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withQuery(string $query): self
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * @param string $fragment
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function withFragment(string $fragment): self
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
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
        return static::createUriString(
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
     * @throws \InvalidArgumentException
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

        if (isset($parts['scheme'])) {
            $this->scheme = $this->filterScheme($parts['scheme']);
        }

        if (isset($parts['user'])) {
            $this->userInfo = $this->filterUser($parts['user']);
        }

        if (isset($parts['host'])) {
            $this->host = $this->filterHost($parts['host']);
        }

        if (isset($parts['port'])) {
            $this->port = $this->filterPort($parts['port']);
        }

        if (isset($parts['path'])) {
            $this->path = $this->filterPath($parts['path']);
        }

        if (isset($parts['query'])) {
            $this->query = $this->filterQueryAndFragment($parts['query']);
        }

        if (isset($parts['fragment'])) {
            $this->fragment = $this->filterQueryAndFragment($parts['fragment']);
        }

        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $this->filterPass($parts['pass']);
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

        $charAtPosZero = \mb_substr($path, 0, 1);
        $charAtPosOne = \mb_substr($path, 1, 1);

        if ($path !== '') {
            if ($charAtPosZero !== '/') {
                if ($authority !== '') {
                    $path = '/' . $path;
                }
            } elseif (isset($charAtPosOne) && $charAtPosOne === '/') {
                if ($authority === '') {
                    $path = '/' . static::ltrim($path, '/');
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
     * Because of PHP 8.4.
     *
     * @param        $string
     * @param string $characters
     *
     * @return string
     */
    protected static function ltrim($string, string $characters = " \n\r\t\v\0"): string
    {
        if (\PHP_MAJOR_VERSION >= 8 && \PHP_MINOR_VERSION >= 4) {
            return \mb_ltrim((string) $string, $characters);
        }

        return \ltrim((string) $string, $characters);
    }

    /**
     * @param string $scheme
     * @param int    $port
     *
     * @return bool
     */
    protected static function isNonStandardPort(string $scheme, int $port): bool
    {
        return !isset(static::SCHEMES[$scheme]) || $port !== static::SCHEMES[$scheme];
    }

    /**
     * @param string $scheme
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function filterScheme(string $scheme): string
    {
        return \mb_strtolower($scheme);
    }

    /**
     * @param string $user
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function filterUser(string $user): string
    {
        return $user;
    }

    /**
     * @param ?string $pass
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function filterPass(?string $pass): ?string
    {
        return $pass;
    }

    /**
     * @param string $host
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function filterHost(string $host): string
    {
        return \mb_strtolower($host);
    }

    /**
     * @param int|null $port
     *
     * @throws \InvalidArgumentException
     *
     * @return int|null
     */
    protected function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException(\sprintf('Invalid port: %d. Must be between 1 and 65535', $port));
        }

        if (!static::isNonStandardPort($this->scheme, $port)) {
            return null;
        }

        return $port;
    }

    /**
     * @param string $path
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function filterPath(string $path): string
    {
        return \preg_replace_callback(
            static::getPatternForFilteringPath(),
            [__CLASS__, 'rawurlencodeMatchZero'],
            $path
        );
    }

    /**
     * @param string $str
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function filterQueryAndFragment(string $str): string
    {
        return \preg_replace_callback(
            static::getPatternForFilteringQueryAndFragment(),
            [__CLASS__, 'rawurlencodeMatchZero'],
            $str
        );
    }

    /**
     * @param array $match
     *
     * @return string
     */
    protected static function rawurlencodeMatchZero(array $match): string
    {
        return \rawurlencode($match[0]);
    }

    /**
     * @return string
     */
    protected static function getPatternForFilteringPath(): string
    {
        return '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/';
    }

    /**
     * @return string
     */
    protected static function getPatternForFilteringQueryAndFragment(): string
    {
        return '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
    }
}
