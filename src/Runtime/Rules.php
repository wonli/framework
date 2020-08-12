<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Runtime;

use Cross\Exception\CoreException;
use Closure;

/**
 * 验证规则
 *
 * Class Rules
 * @package Cross\Runtime
 */
class Rules
{
    protected $rules = [];
    protected static $instance;

    /**
     * Rules constructor.
     */
    protected function __construct()
    {
        $this->rules = [];
    }

    /**
     * 匹配
     *
     * @param string $name
     * @param mixed $content
     * @return mixed
     * @throws CoreException
     */
    static function match(string $name, $content)
    {
        $handler = self::instance()->has($name);
        if (null === $handler) {
            throw new CoreException('未定义的Rule');
        }

        if ($handler instanceof Closure) {
            return $handler($content);
        }

        $isMatch = preg_match(preg_quote($handler), $content);
        if (!$isMatch) {
            throw new CoreException('验证Rule失败');
        }

        return $content;
    }

    /**
     * rule是否存在
     *
     * @param string $name
     * @return mixed
     */
    static function has(string $name)
    {
        return self::instance()->rules[$name] ?? null;
    }

    /**
     * 获取所有规则
     *
     * @return array
     */
    static function getRules(): array
    {
        return self::instance()->rules;
    }

    /**
     * 添加规则
     *
     * @param string $name
     * @param mixed $rule
     * @return string
     */
    static function addRule(string $name, $rule)
    {
        self::instance()->rules[$name] = $rule;
        return $rule;
    }

    /**
     * @return Rules
     */
    protected static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}