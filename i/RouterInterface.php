<?php
/**
 * @Auth wonli <wonli@live.com>
 * Class RouterInterface
 */
namespace cross\i;

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
