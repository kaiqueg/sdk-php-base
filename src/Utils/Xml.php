<?php

namespace SdkBase\Utils;

use SdkBase\Exceptions\Validation\UnexpectedResultException;
use SimpleXMLElement;

class Xml
{
    private static function arrayWalkThrough(array $data, SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }
            if (is_array($value)) {
                $childNode = $xml->addChild($key);
                self::arrayWalkThrough($value, $childNode);
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    /**
     * @param array $input
     * @return string
     * @throws UnexpectedResultException
     */
    public static function fromArray(array $input): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        self::arrayWalkThrough($input, $xml);
        $xmlString = $xml->asXML();
        if ($xmlString === false) {
            throw new UnexpectedResultException("Couldn't convert array to XML.");
        }
        return $xmlString;
    }

    private static function xmlWalkThrough(SimpleXMLElement $parent): array
    {
        // litle help from https://stackoverflow.com/a/15849257
        $output = [];
        foreach ($parent as $name => $element) {
            ($node = &$output[$name])
            && (1 === count($node)
                ? $node = [$node]
                : 1)
            && $node = &$node[];

            $node = $element->count() ? self::xmlWalkThrough($element) : trim($element);
        }
        return $output;
    }

    public static function toArray(string $input): array
    {
        $xml = new SimpleXMLElement($input);
        $output = self::xmlWalkThrough($xml);
        return [$xml->getName() => $output];
    }
}