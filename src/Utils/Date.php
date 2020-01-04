<?php

namespace SdkBase\Utils;

class Date
{
    const DEFAULT_DATE_FORMAT = "Y-m-d H:i:s";

    public static function fromMilliseconds(int $ms, string $dateFormat = self::DEFAULT_DATE_FORMAT): ?string
    {
        return $ms ? date($dateFormat, $ms / 1000) : null;
    }
}