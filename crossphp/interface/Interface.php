<?php
interface RouterInterface
{
	function getController();
	function getAction();
	function getParams();
}

//Cross缓存接口
interface CacheInterface
{
    function get();
    function set();
}

interface ConfigInterface
{
    function put();
    function get($config, $name=null);
    function set($config, $name=null);
}