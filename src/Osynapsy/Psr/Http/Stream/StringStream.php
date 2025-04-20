<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr\Http\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * String-based stream implementation
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class StringStream implements StreamInterface
{
    /**
     * @var string
     */
    private $contents;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var bool
     */
    private $writable = true;

    /**
     * @param string $contents
     */
    public function __construct(string $contents = '')
    {
        $this->contents = $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try {
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        // Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return strlen($this->contents);
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return $this->position >= strlen($this->contents);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($whence !== SEEK_SET && $whence !== SEEK_CUR && $whence !== SEEK_END) {
            throw new InvalidArgumentException('Invalid whence value');
        }

        $size = strlen($this->contents);

        if ($whence === SEEK_SET) {
            $this->position = $offset;
        } elseif ($whence === SEEK_CUR) {
            $this->position += $offset;
        } elseif ($whence === SEEK_END) {
            $this->position = $size + $offset;
        }

        if ($this->position < 0) {
            $this->position = 0;
        }

        if ($this->position > $size) {
            $this->position = $size;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
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
        if (!$this->writable) {
            throw new RuntimeException('Stream is not writeable');
        }

        $size = strlen($string);
        $this->contents = substr_replace($this->contents, $string, $this->position, $size);
        $this->position += $size;

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        if ($length < 0) {
            throw new RuntimeException('Length cannot be negative');
        }

        if ($this->position >= strlen($this->contents)) {
            return '';
        }

        $result = substr($this->contents, $this->position, $length);
        $this->position += strlen($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if ($this->position >= strlen($this->contents)) {
            return '';
        }

        $contents = substr($this->contents, $this->position);
        $this->position = strlen($this->contents);

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        $metadata = [
            'timed_out' => false,
            'blocked' => false,
            'eof' => $this->eof(),
            'unread_bytes' => strlen($this->contents) - $this->position,
            'stream_type' => 'string',
            'wrapper_type' => 'string',
            'wrapper_data' => null,
            'mode' => 'rb+',
            'seekable' => true,
            'uri' => 'string://memory',
        ];

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * Move pointer to the end of stream
     */
    public function end(): void
    {
        $this->position = strlen($this->contents);
    }

    /**
     * Get content from current position to the end
     *
     * @return string
     */
    public function getContent(): string
    {
        if ($this->position >= strlen($this->contents)) {
            return '';
        }
        return substr($this->contents, $this->position);
    }
}