<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\I;

/**
 * Interface RouterInterface
 *
 * @package Cross\I
 */
interface RouterInterface
{
    /**
     * @return string
     */
    function getController(): string;

    /**
     * @return string
     */
    function getAction(): string;

    /**
     * @return array
     */
    function getParams(): array;
}
