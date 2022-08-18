<?php
namespace Osynapsy\Psr\Http;

use Osynapsy\Psr\Http\Factory\UriFromGlobal;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Description of ServerRequest
 *
 * @author pietro
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    protected $attributes = [];
    protected $parsedBody;
    protected $uploadedFiles = [];
    protected $queryParameters = [];
    protected $cookieParametes = [];
    protected $serverParams = [];

    public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1', array $serverParams = [])
    {
        $this->serverParams = $serverParams;
        parent::__construct($method, $uri, $headers, $body, $version);
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value)
    {
        if ($this->getAttribute($name) === $value) {
            return $this;
        }
        $result = clone $this;
        $result->attributes[$name] = $value;
        return $result;
    }

    public function withoutAttribute($name)
    {
        if (array_key_exists($name, $this->attributes) === false) {
            return $this;
        }
        $result = clone $this;
        unset($result->attributes[$name]);
        return $result;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        if ($this->parsedBody === $data) {
            return $this;
        }
        $result = clone $this;
        $result->parsedBody = $data;
        return $result;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        if ($this->uploadedFiles === $uploadedFiles) {
            return $this;
        }
        $result = clone $this;
        $result->uploadedFiles = $uploadedFiles;
        return $result;
    }

    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    public function withQueryParams(array $query)
    {

        if ($this->queryParameters === $query) {
            return $this;
        }
        $result = clone $this;
        $result->queryParameters = $query;
        return $result;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParametes;
    }

    public function withCookieParams(array $cookies)
    {
        if ($this->cookieParametes === $cookies) {
            return $this;
        }
        $result = clone $this;
        $result->cookieParametes = $cookies;
        return $result;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public static function fromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = self::getAllHeaders();
        $uri = UriFromGlobal::get();
        $body = new Stream\Base(fopen('php://input', 'r+'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        return (new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER))
               ->withCookieParams($_COOKIE)
               ->withQueryParams($_GET)
               ->withParsedBody($_POST)
               ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    public static function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
                continue;
            }
            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeNestedFileSpec($value);
                continue;
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }
        return $normalized;
    }

    private static function createUploadedFileFromSpec(array $value)
    {
        return new UploadedFile($value['tmp_name'], (int) $value['size'], (int) $value['error'], $value['name'], $value['type']);
    }

    private static function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }
        return $normalizedFiles;
    }

    private static function getAllHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
           if (substr($name, 0, 5) !== 'HTTP_') {
               continue;
           }
           $headerName = ucwords(strtolower(str_replace('_', '-', 'HTTP_')), '-');
           $headers[$headerName] = $value;
        }
        return $headers;
    }
}
