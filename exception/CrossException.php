<?php
abstract class CrossException extends Exception
{
    function __construct($message, $code=null)
    {
        parent::__construct($message, $code);
        set_exception_handler(array($this, "error_handler"));
    }
    
    //定义错误处理抽象类
    abstract protected function error_handler (exception $e);
}