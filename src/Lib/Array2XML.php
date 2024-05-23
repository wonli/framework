<?php
/**
 * Array2XML: A class to convert array in PHP to XML
 * It also takes into account attributes names unlike SimpleXML in PHP
 * It returns the XML in form of DOMDocument class for further manipulation.
 * It throws exception if the tag name or attribute name has illegal chars.
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (10 July 2011)
 * Version: 0.2 (16 August 2011)
 *          - replaced htmlentities() with htmlspecialchars() (Thanks to Liel Dulev)
 *          - fixed a edge case where root node has a false/null/0 value. (Thanks to Liel Dulev)
 * Version: 0.3 (22 August 2011)
 *          - fixed tag sanitize regex which didn't allow tagnames with single character.
 * Version: 0.4 (18 September 2011)
 *          - Added support for CDATA section using @cdata instead of @value.
 * Version: 0.5 (07 December 2011)
 *          - Changed logic to check numeric array indices not starting from 0.
 * Version: 0.6 (04 March 2012)
 *          - Code now doesn't @cdata to be placed in an empty array
 * Version: 0.7 (24 March 2012)
 *          - Reverted to version 0.5
 * Version: 0.8 (02 May 2012)
 *          - Removed htmlspecialchars() before adding to text node or attributes.
 * Usage:
 *       $xml = Array2XML::createXML('root_node_name', $phpArray);
 *       echo $xml->saveXML();
 */

namespace Cross\Lib;

use DOMDocument;
use DOMException;
use DOMNode;
use Exception;

class Array2XML
{
    private static ?DomDocument $xml = null;

    /**
     * Initialize the root XML node [optional]
     *
     * @param string $version
     * @param string $encoding
     * @param bool $formatOutput
     */
    public static function init(string $version = '1.0', string $encoding = 'UTF-8', bool $formatOutput = true): void
    {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->formatOutput = $formatOutput;
    }

    /**
     * Convert an Array to XML
     *
     * @param string $nodeName - name of the root node to be converted
     * @param array $arr - array to be converted
     * @return DomDocument|null
     * @throws Exception
     */
    public static function &createXML(string $nodeName, array $arr = []): ?DOMDocument
    {
        $xml = self::getXMLRoot();
        $xml->appendChild(self::convert($nodeName, $arr));

        self::$xml = null;
        return $xml;
    }

    /**
     * Convert an Array to XML
     *
     * @param string $nodeName
     * @param array $arr - array to be converted
     * @return DOMNode
     * @throws DOMException
     * @throws Exception
     */
    private static function &convert(string $nodeName, array $arr = []): DOMNode
    {
        $xml = self::getXMLRoot();
        $node = $xml->createElement($nodeName);

        if (is_array($arr)) {
            if (isset($arr['@attributes'])) {
                foreach ($arr['@attributes'] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $nodeName);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']);
            }

            if (isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);
                return $node;
            } else if (isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);
                return $node;
            }
        }

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (!self::isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: ' . $nodeName);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    foreach ($value as $k => $v) {
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]);
            }
        }

        if (!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }

        return $node;
    }

    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(): ?DOMDocument
    {
        if (empty(self::$xml)) {
            self::init();
        }

        return self::$xml;
    }

    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v)
    {
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;

        return $v;
    }

    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag): bool
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}

