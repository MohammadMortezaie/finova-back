<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicStorageUrl
{
    public static function fromPath(Request $request, string $path): string
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        $cleanPath = ltrim($path, '/');

        return "{$baseUrl}/receipt/file/{$cleanPath}";
    }
}
