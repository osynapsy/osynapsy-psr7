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

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Description of UploadedFile
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class UploadedFile implements UploadedFileInterface
{

    private const ERRORS = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    /**
     * @var string|null
     */
    private $clientFilename;

    /**
     * @var string|null
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var string|null
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var int|null
     */
    private $size;

    /**
     * @var StreamInterface|null
     */
    private $stream;

    public function __construct($streamOrFile, ?int $size, int $errorStatus, string $clientFilename = null, string $clientMediaType = null)
    {
        $this->setError($errorStatus);
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

     /**
     * @throws InvalidArgumentException
     */
    private function setError(int $error): void
    {
        if (in_array($error, UploadedFile::ERRORS, true) === false) {
            throw new InvalidArgumentException('Invalid error status for UploadedFile');
        }
        $this->error = $error;
    }

    private function isStringNotEmpty($param): bool
    {
        return is_string($param) && false === empty($param);
    }

    /**
     * Return true if there is no upload error
     */
    private function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function isMoved(): bool
    {
        return $this->moved;
    }

      /**
     * Depending on the value set file or stream variable
     *
     * @param StreamInterface|string|resource $streamOrFile
     *
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile($streamOrFile): void
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (is_resource($streamOrFile)) {
            $this->stream = new Stream\Base($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    public function getClientMediaType() :? string
    {
        return $this->clientMediaType;
    }

    public function getClientFilename() :? string
    {
        return $this->clientFilename;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    public function moveTo(string $targetPath) : void
    {
        $this->validateActive();
        if ($this->isStringNotEmpty($targetPath) === false) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }
        $this->moved = $this->file ? $this->moveFile($targetPath) : $this->saveStream($targetPath);
        if ($this->moved === false) {
            throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath));
        }
    }

    protected function moveFile(string $targetPath) : void
    {
        PHP_SAPI === 'cli' ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
    }

    protected function saveStream($targetPath)
    {
        $streamMaster = $this->getStream();
        $streamMaster->rewind();
        $streamTarget = Base(fopen($targetPath, 'w'));
        while (!$streamMaster->eof()) {
            $streamTarget->write($streamMaster->read(1024));
        }
        $streamTarget->close();
        return true;
    }
}
