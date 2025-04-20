<?php

declare(strict_types=1);

namespace Rancoud\Http\Message;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    /** @var array */
    protected const array SCHEMES = [
        'http'  => 80,
        'https' => 443,
    ];

    /** @var string */
    protected const string CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /** @var string */
    protected const string CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    protected string $scheme = '';

    protected string $userInfo = '';

    protected string $host = '';

    protected ?int $port = null;

    protected string $path = '';

    protected string $query = '';

    protected string $fragment = '';

    /** @throws \InvalidArgumentException */
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

    public function getScheme(): string
    {
        return $this->scheme;
    }

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

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
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
                    $path = '/' . \mb_ltrim($path, '/');
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

    protected static function isNonStandardPort(string $scheme, int $port): bool
    {
        return !isset(static::SCHEMES[$scheme]) || $port !== static::SCHEMES[$scheme];
    }

    /** @throws \InvalidArgumentException */
    protected function filterScheme(string $scheme): string
    {
        return \mb_strtolower($scheme);
    }

    /** @throws \InvalidArgumentException */
    protected function filterUser(string $user): string
    {
        return $user;
    }

    /** @throws \InvalidArgumentException */
    protected function filterPass(?string $pass): ?string
    {
        return $pass;
    }

    /** @throws \InvalidArgumentException */
    protected function filterHost(string $host): string
    {
        return \mb_strtolower($host);
    }

    /** @throws \InvalidArgumentException */
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

    /** @throws \InvalidArgumentException */
    protected function filterPath(string $path): string
    {
        return \preg_replace_callback(
            static::getPatternForFilteringPath(),
            [__CLASS__, 'rawurlencodeMatchZero'],
            $path
        );
    }

    /** @throws \InvalidArgumentException */
    protected function filterQueryAndFragment(string $str): string
    {
        return \preg_replace_callback(
            static::getPatternForFilteringQueryAndFragment(),
            [__CLASS__, 'rawurlencodeMatchZero'],
            $str
        );
    }

    protected static function rawurlencodeMatchZero(array $match): string
    {
        return \rawurlencode($match[0]);
    }

    protected static function getPatternForFilteringPath(): string
    {
        return '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/';
    }

    protected static function getPatternForFilteringQueryAndFragment(): string
    {
        return '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
    }
}
