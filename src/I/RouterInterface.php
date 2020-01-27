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
     * @return mixed controller
     */
    function getController();

    /**
     * @return mixed action
     */
    function getAction();

    /**
     * @return mixed params
     */
    function getParams();
}
