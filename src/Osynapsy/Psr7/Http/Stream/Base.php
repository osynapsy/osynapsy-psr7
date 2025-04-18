<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr7\Http\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * Base stream implementation
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Base implements StreamInterface
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var array|null
     */
    private $metadata;

    /**
     * @var bool
     */
    private $readable;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @var bool
     */
    private $seekable;

    /**
     * @var int|null
     */
    private $size;

    /**
     * @param resource $stream
     * @throws InvalidArgumentException
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        $this->stream = $stream;
        $this->metadata = stream_get_meta_data($stream);
        $this->readable = false !== strpos($this->metadata['mode'], 'r') || false !== strpos($this->metadata['mode'], '+');
        $this->writable = false !== strpos($this->metadata['mode'], 'w') || 
                         false !== strpos($this->metadata['mode'], 'a') || 
                         false !== strpos($this->metadata['mode'], '+');
        $this->seekable = $this->metadata['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!$this->isReadable() || !$this->isSeekable()) {
            return '';
        }

        try {
            $position = $this->tell();
            $this->rewind();
            $contents = $this->getContents();
            $this->seek($position);
            return $contents;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->stream;
        $this->stream = null;
        $this->metadata = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
        $this->size = null;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!is_resource($this->stream)) {
            return null;
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        $position = ftell($this->stream);
        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return !is_resource($this->stream) || feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): int
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }

        $this->size = null;
        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        if ($length < 0) {
            throw new RuntimeException('Length cannot be negative');
        }

        if ($length === 0) {
            return '';
        }

        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (!is_resource($this->stream)) {
            return $key ? null : [];
        }

        if ($key === null) {
            return $this->metadata;
        }

        return isset($this->metadata[$key]) ? $this->metadata[$key] : null;
    }

    /**
     * Move pointer to the end of stream
     */
    public function end(): void
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, 0, SEEK_END) === -1) {
            throw new RuntimeException('Unable to seek to the end of the stream');
        }
    }

    /**
     * Get content from current position to the end
     * 
     * @return string
     */
    public function getContent(): string
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('Stream is not a resource');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }
}