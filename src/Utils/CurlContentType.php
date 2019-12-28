<?php

namespace SdkBase\Utils;

class CurlContentType
{
    const FORM_URL_ENCODED = "application/x-www-form-urlencoded";
    const JSON = "application/json";
    const HTML = "text/html";
    const XML = "text/xml";
    const TEXT = "text/plain";
    const LIST = [
        self::JSON,
        self::FORM_URL_ENCODED,
        self::HTML,
        self::XML,
        self::TEXT,
    ];

    public static function isValid($contentType): bool
    {
        return in_array($contentType, self::LIST);
    }
}