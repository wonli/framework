<?php
/**
 * @Auth wonli <wonli@live.com>
 * Class RouterInterface
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
