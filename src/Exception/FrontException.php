<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Exception;


/**
 * 处理逻辑出错时候抛出
 *
 * @author wonli <wonli@live.com>
 * Class FrontException
 * @package Cross\Exception
 */
class FrontException extends CrossException
{
    protected int $httpStatusCode = 400;
}
