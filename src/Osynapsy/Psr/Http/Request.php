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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

/**
 * Description of Request
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Request extends Message implements RequestInterface
{
    const VALID_METHODS = [
        'OPTIONS',
        'HEAD',
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'TRACE',
        'CONNECT'
    ];
    protected $method;
    protected $requestTarget = null;
    protected $uri;


    public function __construct(string $method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $this->setMethod($method);
        $this->setUri($uri);
        $this->setHeaders($headers);
        if (is_null($body)) {
            $this->setBody(new Stream\StringStream(''));
        } elseif ($body instanceof StreamInterface) {
            $this->setBody($body);
        } elseif (is_string($body)) {
            $this->setBody(new Stream\StringStream($body));
        } elseif (is_resource($body)) {
            $this->setBody(new Stream\Base($body));
        } else {
            throw new InvalidArgumentException('Body must be a StreamInterface, string, or resource');
        }
        $this->setProtocolVersion($protocolVersion);
        $this->updateHostFromUri();
    }

    protected function setMethod($rawMethod)
    {
        $method = strtoupper($rawMethod);
        $this->validateMethod($method);
        $this->method = $method;
    }

    protected function validateMethod($method)
    {
        if (!in_array($method, self::VALID_METHODS)) {
            throw new InvalidArgumentException(sprintf(
                'Method %s is invalid. Valid methods are : %s',
                $method,
                implode(', ', self::VALID_METHODS)
            ));
        }
    }

    protected function setUri($uri)
    {
        $this->uri = ($uri instanceof UriInterface) ? $uri : new Uri($uri);
    }

    protected function setRequestTarget(string $requestTarget)
    {
        $this->requestTarget = $requestTarget;
    }

    public function getRequestTarget(): string
    {
        if (is_null($this->requestTarget)) {
            $string = '/'.ltrim($this->uri->getPath(), '/');
            $query = $this->uri->getQuery();
            if ($query !== '') {
                $string .= '?'.$query;
            }
            return $string  ?? '';
        }
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget) : \Psr\Http\Message\RequestInterface
    {
        if ($this->requestTarget === $requestTarget) {
            return $this;
        }
        $result = clone $this;
        $result->setRequestTarget($requestTarget);
        return $result;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): \Psr\Http\Message\RequestInterface
    {
        $method = strtoupper($method);
        $this->validateMethod($method);
        if ($this->method === $method) {
            return $this;
        }
        $result = clone $this;
        $result->method = $method;
        return $result;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false) : \Psr\Http\Message\RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }
        $result = clone $this;
        $result->setUri($uri);
        if (!$preserveHost || !$result->hasHeader('Host')) {
            $result->updateHostFromUri();
        }
        return $result;
    }

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();
        if ($host == '') {
            return;
        }
        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':' . $port;
        }
        if ($this->hasHeader('Host')) {
            $header = $this->headerNames[$this->caseInsensitiveKey('host')];
        } else {
            $header = 'Host';
            $this->headerNames[$this->caseInsensitiveKey('host')] = 'Host';
        }
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;
    }
}
