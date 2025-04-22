<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Psr\Http\Factory\UriFromGlobal;

/**
 * Description of UriFromGlobalTest
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class UriFromGlobalTest extends TestCase
{
    protected function tearDown(): void
    {
        // Pulisce l'ambiente tra i test
        $_SERVER = [];
    }

    public function testHttpUriWithDefaultPort()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/foo/bar?x=1';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = 80;

        $uri = UriFromGlobal::get();
        $this->assertEquals('http://example.com/foo/bar?x=1', (string)$uri);
    }

    public function testHttpsUriWithNonDefaultPort()
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8443';
        $_SERVER['REQUEST_URI'] = '/secure';
        $_SERVER['HTTPS'] = 'on';

        $uri = UriFromGlobal::get();
        $this->assertEquals('https://example.com:8443/secure', (string)$uri);
    }

    public function testUriWithIpv6Address()
    {
        $_SERVER['HTTP_HOST'] = '[2001:db8::1]';
        $_SERVER['REQUEST_URI'] = '/ipv6';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;

        $uri = UriFromGlobal::get();
        $this->assertEquals('https://[2001:db8::1]/ipv6', (string)$uri);
    }

    public function testUriWithNoRequestUri()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['QUERY_STRING'] = 'test=value';
        unset($_SERVER['REQUEST_URI']);

        $uri = UriFromGlobal::get();
        $this->assertEquals('http://example.com?test=value', (string)$uri);
    }

    public function testUriWithServerNameFallback()
    {
        $_SERVER['SERVER_NAME'] = 'fallback.com';
        $_SERVER['REQUEST_URI'] = '/from/server_name';

        $uri = UriFromGlobal::get();
        $this->assertEquals('http://fallback.com/from/server_name', (string)$uri);
    }

    public function testMissingHostThrowsException()
    {
        $this->expectException(RuntimeException::class);

        unset($_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'], $_SERVER['SERVER_ADDR']);
        $_SERVER['REQUEST_URI'] = '/no-host';

        UriFromGlobal::get();
    }
}

