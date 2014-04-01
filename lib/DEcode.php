<?php

/*编码解码函数库基类*/
abstract class DEcode
{

    /*编码函数*/
    abstract function enCode ($data)

    ;

    /*解码函数*/
    abstract function deCode ($data)

    ;
}