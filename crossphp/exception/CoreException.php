<?php
class CoreException extends CrossException
{
    function error_handler (exception $e)
    {
        $cp_error = $this->cp_exception_source($e);
        $code = $e->getCode() ? $e->getCode() : 200;
        $tpl = CP_PATH.'exception/_tpl/fronterror.php';         
        return Response::getInstance()->display( $code, $cp_error, $tpl );
    }
}
