<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr7\Http\Factory;

use Osynapsy\Psr7\Http\Uri;

/**
 * Description of UriFromGlobalFactory
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class UriFromGlobal
{
    public static function get()
    {
        list($host, $port) = self::getHostAndPort();
        list($path, $query) = self::getPathAndQueryString();
        return new Uri(self::getScheme(), $host, $port, $path, $query);
    }

    private static function getScheme()
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    }

    private static function getHostAndPort()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return self::extractHostAndPortFromAuthority($_SERVER['HTTP_HOST']);
        }
        $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? null;
        $port = $_SERVER['SERVER_PORT'] ?? null;
        return [$host, $port];
    }

    private static function extractHostAndPortFromAuthority(string $authority): array
    {
        $uri = 'http://' . $authority;
        $parts = parse_url($uri);
        if (false === $parts) {
            return [null, null];
        }
        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;
        return [$host, $port];
    }

    private static function getPathAndQueryString()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return ['', $_SERVER['QUERY_STRING'] ?? ''];
        }
        $requestUriParts = explode('?', $_SERVER['REQUEST_URI'], 2);
        return [$requestUriParts[0], $requestUriParts[1] ?? ''];
    }
}
