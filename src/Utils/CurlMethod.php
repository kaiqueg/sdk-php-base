<?php

namespace SdkBase\Utils;

class CurlMethod
{
    const POST = "POST";
    const GET = "GET";
    const PUT = "PUT";
    const DELETE = "DELETE";
    const LIST = [
        self::POST,
        self::GET,
        self::PUT,
        self::DELETE,
    ];

    public static function isValid(string &$method): bool
    {
        $method = strtoupper($method);
        return in_array($method, self::LIST);
    }
}