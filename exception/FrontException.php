<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.3
 */
namespace Cross\Exception;

use Cross\Core\Response;
use Exception;

/**
 * @Auth: wonli <wonli@live.com>
 * Class FrontException
 * @package Cross\Exception
 */
class FrontException extends CrossException
{
    function errorHandler(Exception $e)
    {
        $cp_error = $this->cpExceptionSource($e);
        $code = $e->getCode() ? $e->getCode() : 200;

        return Response::getInstance()->setResponseStatus($code)->display($cp_error, CP_PATH . 'exception/_tpl/front_error.php');
    }
}




