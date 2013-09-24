<?php

/*编码解码函数库基类*/
abstract class DEcode
{

    /*编码函数*/
    abstract function EnCode ($data)
    
    ;

    /*解码函数*/
    abstract function DeCode ($data)
    
    ;
}