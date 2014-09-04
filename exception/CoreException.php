<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.2
 */
namespace cross\exception;

use exception;
use cross\core\Response;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreException
 * @package cross\exception
 */
class CoreException extends CrossException
{
    function errorHandler( exception $e )
    {
        $cp_error = $this->cpExceptionSource($e);
        $code = $e->getCode() ? $e->getCode() : 500;

        return Response::getInstance()->setResponseStatus($code)->display($cp_error, CP_PATH . 'exception/_tpl/fronterror.php');
    }
}
