<?php

/**
 * @Auth: wonli <wonli@live.com>
 * Class CacheException
 */
class CacheException extends CrossException
{
    function error_handler (exception $e)
    {
        $cp_error = $this->cp_exception_source($e);
        $code = $e->getCode() ? $e->getCode() : 200;
        return Response::getInstance()->set_response_status($code)->display( $cp_error, CP_PATH.'exception/_tpl/fronterror.php' );
    }
}