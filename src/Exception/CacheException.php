<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Exception;

use Cross\Http\Response;
use Exception;

/**
 * @author wonli <wonli@live.com>
 * Class CacheException
 * @package Cross\Exception
 */
class CacheException extends CrossException
{
    function errorHandler(Exception $e)
    {
        $cp_error = $this->cpExceptionSource($e);
        $code = $e->getCode() ? $e->getCode() : 200;

        Response::getInstance()->setResponseStatus($code)
            ->display($cp_error, __DIR__ . '/tpl/front_error.tpl.php');
    }
}
