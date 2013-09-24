<?php
class FrontException extends CrossException
{
    function error_handler (exception $e)
    {
        $message = $e->getMessage();
        $code = $e->getCode() ? $e->getCode() : 200;
        $line = $e->getLine();
        // $e->getTraceAsString ();
        $trace = $e->getTrace();
        $class = $trace[0]["class"];
        $notes = $message.' '.$class.' '.$line;
 
        $tpl = CROSSPHP_PATH.'exception/_tpl/fronterror.php';      
        return Response::message( $code, $notes, $tpl );
    }
}