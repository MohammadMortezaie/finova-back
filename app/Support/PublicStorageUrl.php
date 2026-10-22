<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicStorageUrl
{
    public static function fromPath(Request $request, string $path): string
    {
        $baseUrl = self::baseUrl($request);
        $cleanPath = ltrim($path, '/');

        return "{$baseUrl}/receipt/file/{$cleanPath}";
    }

    public static function absolute(Request $request, ?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || ! self::isLoopback($host)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);

        return self::baseUrl($request).$path.($query ? '?'.$query : '');
    }

    private static function baseUrl(Request $request): string
    {
        if (! self::isLoopback($request->getHost())) {
            return rtrim($request->getSchemeAndHttpHost(), '/');
        }

        $configured = rtrim((string) config('app.url'), '/');
        $configuredHost = parse_url($configured, PHP_URL_HOST);
        if (is_string($configuredHost) && $configuredHost !== '' && ! self::isLoopback($configuredHost)) {
            return $configured;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/');
    }

    private static function isLoopback(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        return in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true);
    }
}
