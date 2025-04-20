<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr\Http\Factory;

use Osynapsy\Psr\Http\Uri;

/**
 * UriFromGlobal Class
 *
 * This class provides a static method to construct an instance of the Uri class from global $_SERVER data.
 * It extracts information such as the scheme (http/https), host, port, path, and query string to generate a full URL.
 * The class correctly handles IPv6 addresses, default ports, and query strings.
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class UriFromGlobal
{
    public static function get(): Uri
    {
        [$host, $port] = self::getHostAndPort();
        [$path, $query] = self::getPathAndQueryString();
        if (empty($host)) {
            throw new \RuntimeException("Impossibile determinare l'host dalla variabile \$_SERVER.");
        }
        $scheme = self::getScheme();
        $url = sprintf('%s://%s', $scheme, self::formatHost($host));
        if (!empty($port) && !self::isDefaultPort($scheme, $port)) {
            $url .= ':' . $port;
        }
        if (!empty($path)) {
            $url .= $path[0] === '/' ? $path : '/' . $path;
        }
        if (!empty($query)) {
            $url .= '?' . $query;
        }
        return new Uri($url);
    }

    private static function getScheme(): string
    {
        return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    }

    private static function getHostAndPort(): array
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            return self::extractHostAndPortFromAuthority($_SERVER['HTTP_HOST']);
        }
        $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? null;
        $port = $_SERVER['SERVER_PORT'] ?? null;
        return [$host, $port];
    }

    private static function extractHostAndPortFromAuthority(string $authority): array
    {
        // Aggiunge schema fittizio per usare parse_url
        $parsed = parse_url('http://' . $authority);
        if ($parsed === false) {
            return [null, null];
        }
        return [$parsed['host'] ?? null, $parsed['port'] ?? null];
    }

    private static function getPathAndQueryString(): array
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            return ['', $_SERVER['QUERY_STRING'] ?? ''];
        }
        $parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    private static function isDefaultPort(string $scheme, $port): bool
    {
        return ($scheme === 'http' && (int)$port === 80) || ($scheme === 'https' && (int)$port === 443);
    }

    private static function formatHost(string $host): string
    {
        // Aggiunge le parentesi quadre se l'host è un IPv6
        return (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) ? '[' . $host . ']' : $host;
    }
}

