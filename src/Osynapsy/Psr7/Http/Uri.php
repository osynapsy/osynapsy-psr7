<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr7\Http;

use Psr\Http\Message\UriInterface;

/**
 * Description of Uri
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Uri implements UriInterface
{
    const VALID_SCHEMES = [
        'http' => 80,
        'https' => 443,
        '' => null
    ];

    protected $path;
    protected $host;
    protected $port;
    protected $fragment;
    protected $query;
    protected $scheme;
    protected $user;
    protected $password;

    public function __construct(string $scheme, string $host, ?int $port = null, string $path = '/', string $query = '',  string $fragment = '', string $user = '', string $password = '')
    {
        $this->setScheme($scheme);
        $this->setHost($host);
        $this->setPort($port);
        $this->setPath($path);
        $this->setQuery($query);
        $this->setFragment($fragment);
        $this->setUser($user);
        $this->setPassword($password);
    }

    protected function setScheme($rawscheme)
    {
        if (!is_string($rawscheme)) {
            throw new InvalidArgumentException('Uri scheme must be a string.');
        }
        $scheme = strtolower($rawscheme);
        if (!key_exists($scheme, static::VALID_SCHEMES)) {
            throw new InvalidArgumentException(sprtinf('Uri scheme must be one of: %s"', implode('", "', array_keys(static::SUPPORTED_SCHEMES))));
        }
        $this->scheme = $scheme;
    }

    protected function setHost($host)
    {
        $this->host = $host;
    }

    protected function setPort($port)
    {
        $this->port = $port;
    }

    protected function setPath($path)
    {
        $this->path = $path;
    }

    protected function setQuery($query)
    {
        $this->query = $this->filterQuery($query);
    }

    protected function filterQuery($query): string
    {
        if (is_object($query) && method_exists($query, '__toString')) {
            $query = (string) $query;
        }
        if (!is_string($query)) {
            throw new InvalidArgumentException('Uri query must be a string.');
        }
        $match = preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {  return rawurlencode($match[0]); },
            $query
        );
        return is_string($match) ? $match : '';
    }

    protected function setFragment($fragment)
    {
        $this->fragment = $fragment;
    }

    protected function setUser($user)
    {
        $this->user = $user;
    }

    protected function setPassword($password)
    {
        $this->password = $password;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUserInfo(): string
    {
        return $this->user . $this->composeUriPart($this->password, ':');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPort()
    {
        return  $this->port && !$this->hasStandardPort() ? $this->port : null;
    }

    protected function hasStandardPort(): bool
    {
        return static::VALID_SCHEMES[$this->scheme] === $this->port;
    }

    public function getAuthority(): string
    {
        $result = $this->composeUriPart($this->getUserInfo(), null, '@');
        $result .= $this->getHost();
        $result .= $this->composeUriPart($this->getPort(), ':');
        return $result;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function withPort($port)
    {
        $this->validatePort($port);
        return $this->cloneAndSetProperty('port', $port);
    }

    protected function validatePort($port)
    {
        if (is_null($port) && ($port < 1 || $port > 65536)) {
            new \InvalidArgumentException("Port must be a integer between 0 and 65536 or null");
        }
    }

    public function withHost($host)
    {
        return $this->cloneAndSetProperty('host', $host);
    }

    public function withUserInfo($user, $password = null)
    {
        $result = clone $this;
        $result->user = $user;
        $result->password = $password;
        return $result;
    }

    public function withScheme($scheme)
    {
        $result = clone $this;
        $result->setScheme($scheme);
        return $result;
    }

    public function withFragment($fragment)
    {
        return $this->cloneAndSetProperty('fragment', $fragment);
    }

    public function withQuery($query)
    {
        return $this->cloneAndSetProperty('query', $query);
    }

    public function withPath($path)
    {
        return $this->cloneAndSetProperty('path', $path);
    }

    protected function cloneAndSetProperty($property, $value)
    {
        $result = clone $this;
        $result->{$property} = $value;
        return $result;
    }

    public function __toString(): string
    {
        $result = $this->composeUriPart($this->getScheme(), null, ':');
        $result .= '//';
        $result .= $this->getAuthority();
        $result .= $this->getPath();
        $result .= $this->composeUriPart($this->getQuery(), '?');
        $result .= $this->composeUriPart($this->getFragment(), '#');
        return  $result;
    }

    protected function composeUriPart($uriPart, $prefix = null, $postfix = null)
    {
        return ($uriPart ?? '') === '' ? '' : ($prefix ?? '') . $uriPart . ($postfix ?? '');
    }

    public static function fromString(string $uri)
    {
        $uriParts = parse_url($uri);
        if ($uriParts === false) {
            throw new \Exception('Malformed url');
        }
        return new Uri(
            $uriParts['scheme'] ?? '',
            $uriParts['host'] ?? 'localhost',
            $uriParts['port'] ?? null,
            $uriParts['path'] ?? '',
            $uriParts['query'] ?? '',
            $uriParts['fragment'] ?? '',
            $uriParts['user'] ?? '',
            $uriParts['pass'] ?? ''
        );
    }
}
