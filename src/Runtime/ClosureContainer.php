<?php
/**
 * Cross - a micro PHP framework
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
    protected array $actions = [];

    /**
     * @var static|null
     */
    private static ?ClosureContainer $instance = null;

    /**
     * ClosureContainer constructor.
     */
    private function __construct()
    {

    }

    /**
     * @return static
     */
    static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 注册一个匿名方法
     *
     * @param string $name
     * @param Closure $f
     */
    function add(string $name, Closure $f): void
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
    function run(string $name, array $params = [])
    {
        if (isset($this->actions[$name])) {
            if (!is_array($params)) {
                $params = [$params];
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
    function runOnce(string $name, array $params = []): mixed
    {
        static $cache = [];
        if (isset($cache[$name])) {
            return $cache[$name];
        } elseif (isset($this->actions[$name])) {
            if (!is_array($params)) {
                $params = [$params];
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
    function has(string $name, Closure &$closure = null): bool
    {
        if (isset($this->actions[$name])) {
            $closure = $this->actions[$name];
            return true;
        }

        return false;
    }
}
