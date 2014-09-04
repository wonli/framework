<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.2
 */
namespace cross\exception;

use cross\core\Response;
use exception;

/**
 * @Auth: wonli <wonli@live.com>
 * Class FrontException
 * @package cross\exception
 */
class FrontException extends CrossException
{
    function errorHandler(exception $e)
    {
        $cp_error = $this->cpExceptionSource($e);
        $code = $e->getCode() ? $e->getCode() : 200;

        return Response::getInstance()->setResponseStatus($code)->display($cp_error, CP_PATH . 'exception/_tpl/fronterror.php');
    }
}




