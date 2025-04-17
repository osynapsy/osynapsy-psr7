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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

/**
 * Description of Message
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Message implements MessageInterface
{
    const VALID_PROTOCOL_VERSION = ['1.0', '1.1', '2.0', '2'];
    protected $protocol = '1.1';
    protected $headers = [];
    protected $headerNames = [];
    protected $bodyStream;

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    protected function setProtocolVersion($protocolVersion)
    {
        $this->validateProtocolVersion($protocolVersion);
        $this->protocol = $protocolVersion;
    }

    protected function validateProtocolVersion($protocolVersion)
    {
        if (!in_array($protocolVersion, self::VALID_PROTOCOL_VERSION)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid HTTP version. Must be one of: %s',
                    implode(', ', self::VALID_PROTOCOL_VERSION)
                )
            );
        }
    }

    public function withProtocolVersion($protocolVersion)
    {
        $result = clone $this;
        $result->setProtocolVersion($protocolVersion);
        return $result;
    }

    public function withHeader($key, $value)
    {
        $caseInsensitiveKey = $this->caseInsensitiveKey($key);
        $result = clone $this;
        $result->headerNames[$caseInsensitiveKey] = $key;
        $result->headers[$key] = is_array($value) ? $value : [$value];
        return $result;
    }

    public function withAddedHeader($key, $value)
    {
        $caseInsensitiveKey = $this->caseInsensitiveKey($key);
        $values = is_array($value) ? $value : [$value];
        $result = clone $this;
        if (!$this->hasHeader($key)) {
            $result->headerNames[$caseInsensitiveKey] = $key;
            $result->headers[$key] = $values;
        } else {
            $headerKey = $this->headerNames[$caseInsensitiveKey];
            $result->headers[$headerKey] = array_merge($this->headers[$headerKey] ?? [], $values);
        }
        return $result;
    }

    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        $result = clone $this;
        $caseInsensitiveKey = $this->caseInsensitiveKey($name);
        $headerKey = $this->headerNames[$caseInsensitiveKey];
        unset($result->headers[$headerKey]);
        unset($result->headerNames[$caseInsensitiveKey]);
        return $result;
    }

    /**
     * Check if key exists in header repository
     *
     * @param string $key key to search
     * @return bool
     */
    public function hasHeader($key) : bool
    {
        return array_key_exists($this->caseInsensitiveKey($key), $this->headerNames);
    }

    /**
     * Return the header values by key
     *
     * @param string $key
     * @return array
     */
    public function getHeader($key) : array
    {
        if (!$this->hasHeader($key)) {
            return [];
        }
        $caseInsensitiveKey = $this->caseInsensitiveKey($key);
        $headerKey = $this->headerNames[$caseInsensitiveKey];
        return $this->headers[$headerKey];
    }

    /**
     * Return associative array of headers
     *
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Return the header line by key
     *
     * @param string $key
     * @return string
     */
    public function getHeaderLine($key) : string
    {
        if (!$this->hasHeader($key)) {
            return '';
        }
        $caseInsensitiveKey = $this->caseInsensitiveKey($key);
        $headerKey = $this->headerNames[$caseInsensitiveKey];
        return implode(', ', $this->headers[$headerKey]);
    }

    public function getBody()
    {
        if ($this->bodyStream === null) {
            $this->bodyStream = new Stream\StringStream('');
        }
        return $this->bodyStream;
    }

    protected function setHeaders(array $headers)
    {
        foreach($headers as $name => $value) {
            $caseInsensitiveKey = $this->caseInsensitiveKey($name);
            $this->headerNames[$caseInsensitiveKey] = $name;
            $values = is_array($value) ? $value : [$value];
            $this->headers[$name] = $values;
        }
    }

    protected function setBody(StreamInterface $stream)
    {
        $this->bodyStream = $stream;
    }

    public function withBody(StreamInterface $stream)
    {
        if ($stream === $this->bodyStream) {
            return $this;
        }
        $result = clone $this;
        $result->setBody($stream);
        return $result;
    }

    protected function caseInsensitiveKey($key)
    {
        return strtolower($key);
    }
}