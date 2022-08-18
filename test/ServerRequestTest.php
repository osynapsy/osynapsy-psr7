<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Http\Psr7\Message\ServerRequest;

/**
 * Description of ServerRequestTest
 *
 * @author pietro
 */
class ServerRequestTest extends TestCase
{
    public function serverRequestFactory()
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
        $serverRequest = ServerRequest::fromGlobals();
        return $serverRequest;
    }

    public function testFactory()
    {
        $this->assertNotEmpty($this->serverRequestFactory());
    }

    public function testHostFactory()
    {
        $this->assertEquals('localhost', $this->serverRequestFactory()->getUri()->getHost());
    }
}
