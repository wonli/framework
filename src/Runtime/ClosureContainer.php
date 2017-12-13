<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Runtime;


use Closure;

/**
 * @author wonli <wonli@live.com>
 * Class ClosureContainer
 * @package Cross\Core
 */
class ClosureContainer
{
    /**
     * @var array
     */
    protected $actions;

    function __construct()
    {

    }

    /**
     * 注册一个匿名方法
     *
     * @param string $name
     * @param Closure $f
     */
    function add($name, Closure $f)
    {
        $this->actions[$name] = $f;
    }

    /**
     * 执行指定的匿名方法
     *
     * @param string $name
     * @param array $params
     * @return mixed
     */
    function run($name, $params = array())
    {
        if (isset($this->actions[$name])) {
            if (!is_array($params)) {
                $params = array($params);
            }
            return call_user_func_array($this->actions[$name], $params);
        }

        return false;
    }

    /**
     * 执行指定的匿名方法并缓存执行结果
     *
     * @param string $name
     * @param array $params
     * @return mixed
     */
    function runOnce($name, $params = array())
    {
        static $cache = array();
        if (isset($cache[$name])) {
            return $cache[$name];
        } elseif (isset($this->actions[$name])) {
            if (!is_array($params)) {
                $params = array($params);
            }

            $cache[$name] = call_user_func_array($this->actions[$name], $params);
            return $cache[$name];
        }

        return false;
    }

    /**
     * 检查指定的匿名方法是否已经注册
     *
     * @param string $name
     * @param Closure|null $closure
     * @return bool
     */
    function has($name, & $closure = null)
    {
        if (isset($this->actions[$name])) {
            $closure = $this->actions[$name];
            return true;
        }

        return false;
    }
}
