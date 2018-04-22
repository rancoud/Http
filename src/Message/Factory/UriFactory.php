<?php

declare(strict_types=1);

namespace Rancoud\Http\Message\Factory;

use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Rancoud\Http\Message\Uri;

/**
 * Class UriFactory.
 */
class UriFactory implements UriFactoryInterface
{
    /**
     * @param string $uri
     *
     * @return UriInterface
     */
    public function createUri($uri = ''): UriInterface
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        return new Uri($uri);
    }

    /**
     * @param array $server
     *
     * @throws InvalidArgumentException
     *
     * @return UriInterface
     */
    public function createUriFromArray(array $server): UriInterface
    {
        $uri = new Uri('');

        if (array_key_exists('REQUEST_SCHEME', $server) === true) {
            $uri = $uri->withScheme($server['REQUEST_SCHEME']);
        } elseif (array_key_exists('HTTPS', $server) === true) {
            if ($server['HTTPS'] === 'on') {
                $uri = $uri->withScheme('https');
            } else {
                $uri = $uri->withScheme('http');
            }
        }

        if (array_key_exists('HTTP_HOST', $server) === true) {
            $uri = $uri->withHost($server['HTTP_HOST']);
        } elseif (array_key_exists('SERVER_NAME', $server) === true) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (array_key_exists('SERVER_PORT', $server) === true) {
            $uri = $uri->withPort($server['SERVER_PORT']);
        }

        if (array_key_exists('REQUEST_URI', $server) === true) {
            $parts = explode('?', $server['REQUEST_URI']);
            $uri = $uri->withPath($parts[0]);
        }

        if (array_key_exists('QUERY_STRING', $server) === true) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }
}
