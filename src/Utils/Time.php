<?php

namespace SdkBase\Utils;

class Time
{
    private static function getUnityAsString(int $unity): string
    {
        return ($unity < 10 ? "0" : "") . $unity;
    }
    public static function fromMilliseconds(int $ms): ?string
    {
        if($ms <= 0) {
            return null;
        }
        $seconds = floor($ms/1000);
        $minutes = floor(($seconds / 60) % 60);
        $hours = floor($seconds / 3600);
        $time = [
            self::getUnityAsString($hours),
            self::getUnityAsString($minutes),
            self::getUnityAsString($seconds % 60),
        ];
        return implode(":", $time);
    }
}