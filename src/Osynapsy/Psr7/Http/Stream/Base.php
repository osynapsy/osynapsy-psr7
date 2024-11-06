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

/**
 * Description of Base
 *
 * @author pietro
 */
class Base implements StreamInterface
{
    protected $stream;
    protected $metadata;

    public function __construct($stream)
    {
        $this->stream = $stream;
        $this->metadata = stream_get_meta_data($this->stream);
    }

    public function close() : void
    {
        fclose($this->stream);
    }

    public function detach()
    {
        $this->stream = null;
    }

    public function eof() : bool
    {
        return feof($this->stream);
    }

    public function end()
    {
        fseek($this->stream, 0, SEEK_END);
    }

    public function getContents() : string
    {
        return stream_get_contents($this->stream);
    }

    public function getMetadata($key = null)
    {
        return is_null($key) ? $this->metadata : $this->metadata[$key];
    }

    public function getSize() : ?int
    {
        return fstat($this->stream)['size'];
    }

    public function isReadable() : bool
    {
        return in_array($this->getMetadata('mode'), ['r', 'r+', 'w+', 'w+b']);
    }

    public function isWritable() : bool
    {
        return in_array($this->getMetadata('mode'), ['w', 'r+', 'w+', 'w+b']);
    }

    public function isSeekable(): bool
    {
        return $this->getMetadata('seekable') ? true : false;
    }

    public function postpend($text, $keysearch)
    {
        $keyposition = $this->search($keysearch);
        if ($keyposition === false) {
            return false;
        }
        $keyposition += strlen($keysearch);
        $this->rewind();
        $newcontent = substr_replace($this->getContents(), $text, $keyposition, 0);
        $this->rewind();
        $this->write($newcontent);
    }

    public function prepend($text, $keysearch)
    {
        $keyposition = $this->search($keysearch);
        if ($keyposition === false) {
            return false;
        }
        $this->rewind();
        $newcontent = substr_replace($this->getContents(), $text, $keyposition, 0);
        $this->rewind();
        $this->write($newcontent);
    }

    public function read(int $requestLength) : string
    {
        return fread($this->stream, $requestLength);
    }

    public function rewind() : void
    {
        $this->seek(0);
    }

    public function search($keysearch)
    {
        $currentPosition = $this->tell();
        $this->rewind();
        $result = strpos($this->getContents(), $keysearch);
        $this->seek($currentPosition);
        return $result;
    }

    public function seek(int $position, int $whence = \SEEK_SET) : void
    {
        fseek($this->stream, $position, $whence);
    }

    public function tell() : int
    {
        return ftell($this->stream);
    }

    public function write(string $text) : int
    {
        return fwrite($this->stream, $text);
    }

    public function __toString()
    {
        $this->rewind();
        return $this->getContents();
    }
}
