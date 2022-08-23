<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Psr7\Uri;
use Osynapsy\Psr7\Factory\UriFromGlobal;

/**
 * Description of ResponseTest
 *
 * @author pietro
 */
class UriTest extends TestCase
{
    public function UriFactory()
    {
        return new Uri('https', 'localhost', 8080, '/', 'q=test', 'main', 'user', 'password');
    }

    public function UriFromStringFactory($uri)
    {
        return Uri::fromString($uri);
    }

    public function testFactory()
    {
        $this->assertNotEmpty($this->uriFactory());
    }

    public function testUriHostDefault()
    {
        $uri = $this->uriFactory();
        $this->assertEquals($uri->getHost(), 'localhost');
    }

    public function testUriPortDefault()
    {
        $uri = $this->uriFactory();
        $this->assertEquals($uri->getPort(), 8080);
    }

    public function testUriUserInfo()
    {
        $uri = $this->uriFactory();
        $this->assertEquals('user:password', $uri->getUserInfo());
    }

    public function testUriAutority()
    {
        $uri = $this->uriFactory();
        $this->assertEquals('user:password@localhost:8080', $uri->getAuthority());
    }

    public function testUriToString()
    {
        $uri = $this->uriFactory();
        $this->assertEquals('https://user:password@localhost:8080/?q=test#main', (string) $uri);
    }

    public function testUriFromStrings()
    {
        $this->UriFromStringTest('http://localhost');
        $this->UriFromStringTest('http://localhost:8080');
        $this->UriFromStringTest('https://localhost:8080');
        $this->UriFromStringTest('https://localhost:8080/');
        $this->UriFromStringTest('https://localhost:8080/test');
        $this->UriFromStringTest('https://user:pass@localhost:8080/test');
        $this->UriFromStringTest('https://user:pass@localhost:8080/test?q=search');
        $this->UriFromStringTest('https://user:pass@localhost:8080/test?q=search#main');
        $this->UriFromStringTest('https://user:pass@localhost/test/main?q=search');
    }

    public function UriFromStringTest($uri)
    {
        $this->assertEquals($uri, (string) $this->UriFromStringFactory($uri));
    }

    public function testUriFromGlobalFactory()
    {
        $_SERVER = [
            "SCRIPT_NAME" => "/index.php",
            "REQUEST_URI" => "/",
            "QUERY_STRING" => "",
            "REQUEST_METHOD" => "GET",
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "GATEWAY_INTERFACE" => "CGI/1.1",
            "REDIRECT_URL" => "/",
            "REMOTE_PORT" => "50118",
            "SCRIPT_FILENAME" => "/var/www/html/erp/webroot/index.php",
            "SERVER_ADMIN" => "root@locahost",
            "CONTEXT_DOCUMENT_ROOT" => "/var/www/html/erp/webroot",
            "CONTEXT_PREFIX" => "",
            "REQUEST_SCHEME" => "https",
            "HTTPS" => "on",
            "DOCUMENT_ROOT" => "/var/www/html",
            "HTTP_HOST" => "localhost",
            "SERVER_PORT" => "443",
            "SERVER_ADDR" => "192.168.1.253",
            "SERVER_NAME" => "localhost",
            "SERVER_SOFTWARE" => "Apache/2.4.52 (codeit) OpenSSL/1.1.1m",
            "SERVER_SIGNATURE" => "",
            "PATH" => "/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin",
            "REQUEST_TIME_FLOAT" => 1660573921.119745,
            "REQUEST_TIME" => 1660573921
         ];
        $uri = UriFromGlobal::get();
        $this->assertEquals(sprintf('https://%s/',$_SERVER['SERVER_NAME']), (string) $uri);
    }
}
