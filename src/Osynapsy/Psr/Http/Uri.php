<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr\Http;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

/**
 * Implementation of PSR-7 UriInterface
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Uri implements UriInterface
{
    /** @var string Uri scheme */
    private $scheme = '';

    /** @var string Uri user info */
    private $userInfo = '';

    /** @var string Uri host */
    private $host = '';

    /** @var int|null Uri port */
    private $port;

    /** @var string Uri path */
    private $path = '';

    /** @var string Uri query string */
    private $query = '';

    /** @var string Uri fragment */
    private $fragment = '';

    /**
     * @param string $uri Uri string
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new InvalidArgumentException("Unable to parse URI: $uri");
            }

            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
            $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
            $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
            $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
        }
    }

    /**
     * Filter and validate port number
     *
     * @param int $port
     * @return int|null
     */
    private function filterPort($port)
    {
        if ($port === null) {
            return null;
        }

        $port = (int) $port;
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port: $port. Must be between 1 and 65535");
        }

        return $port;
    }

    /**
     * Filter path
     *
     * @param string $path
     * @return string
     */
    private function filterPath($path)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }

    /**
     * Filter query string or fragment
     *
     * @param string $str
     * @return string
     */
    private function filterQueryAndFragment($str)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $str
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

    public function withScheme($scheme): UriInterface
    {
        $scheme = strtolower($scheme);
        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        $info = $user;
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

    public function withHost($host): UriInterface
    {
        $host = strtolower($host);
        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    public function withPort($port): UriInterface
    {
        $port = $this->filterPort($port);
        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    public function withPath($path): UriInterface
    {
        $path = $this->filterPath($path);
        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery($query): UriInterface
    {
        $query = $this->filterQueryAndFragment($query);
        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment($fragment): UriInterface
    {
        $fragment = $this->filterQueryAndFragment($fragment);
        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        if ($this->path !== '') {
            if ($authority !== '' && $this->path[0] !== '/') {
                $uri .= '/' . $this->path;
            } else {
                $uri .= $this->path;
            }
        }

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}
