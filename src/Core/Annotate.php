<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Closure;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Annotate
 * @package Cross\Core
 */
class Annotate
{
    /**
     * @var Delegate
     */
    private $delegate;

    /**
     * 注释参数前缀
     *
     * @var string
     */
    private $prefix = 'cp_';

    /**
     * @var Annotate
     */
    private static $instance;

    /**
     * 注册一个wrapper
     *
     * @param Delegate $delegate
     */
    private function __construct(Delegate &$delegate)
    {
        $this->delegate = $delegate;
        stream_register_wrapper('annotate', 'Cross\Lib\StringToPHPStream');
    }

    /**
     * 生成解析注释配置单例对象
     *
     * @param Delegate $delegate
     * @return Annotate
     */
    public static function getInstance(Delegate &$delegate)
    {
        if (!self::$instance) {
            self::$instance = new Annotate($delegate);
        }

        return self::$instance;
    }

    /**
     * 设置前缀
     *
     * @param string $prefix
     * @return $this
     */
    function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 注释配置转换为数组
     *
     * @param string $annotate
     * @return array
     */
    public function parse($annotate = '')
    {
        if (empty($annotate)) {
            return array();
        }

        $flag = preg_match_all("/@{$this->prefix}(.*?)\s+(.*)/", $annotate, $content);
        if (!$flag) {
            return array();
        }

        $configs = array_combine($content[1], $content[2]);
        return $this->parseAnnotate($configs);
    }

    /**
     * 把PHP代码绑定到匿名函数中
     *
     * @param string $params
     * @return Closure
     */
    public function bindToClosure($params)
    {
        return function ($self) use ($params) {
            return include("annotate://{$params}");
        };
    }

    /**
     * php字符串代码通过wrapper转换为php代码
     *
     * @param string $params
     * @return mixed
     */
    public function toCode($params)
    {
        return include("annotate://{$params}");
    }

    /**
     * 配置参数值解析
     * <pre>
     * 如: a, b=file, c 会被解析为
     * array(
     *      'a' => '',
     *      'b' => file,
     *      'c' => '',
     * )
     * </pre>
     *
     * @param $params
     * @return array
     */
    public function toArray($params)
    {
        $result = array();
        $conf = array_map('trim', explode(',', $params));
        foreach ($conf as $c) {
            if (false !== strpos($c, '=')) {
                $c = explode('=', $c);
                $result[trim($c[0])] = isset($c[1]) ? trim($c[1]) : '';
            } else {
                $result[$c] = '';
            }
        }
        unset($conf);

        return $result;
    }

    /**
     * 注释配置解析
     *
     * @param array $annotateConfigs
     * @return array
     */
    private function parseAnnotate(array $annotateConfigs)
    {
        $result = array();
        foreach ($annotateConfigs as $conf => $params) {
            switch ($conf) {
                case 'params':
                    $result['params'] = $this->toArray($params);
                    break;

                case 'cache':
                case 'response':
                case 'basicAuth':
                    $result[$conf] = $this->toCode($params);
                    break;

                case 'after':
                case 'before':
                    $result[$conf] = $this->bindToClosure($params);
                    break;

                default:
                    $params = trim($params);
                    $closureContainer = $this->delegate->getClosureContainer();
                    $hasClosure = $closureContainer->has('parseAnnotate');
                    if ($hasClosure) {
                        $closureContainer->run('parseAnnotate', array($conf, &$params, $this));
                    }

                    $result[$conf] = $params;
            }
        }

        return $result;
    }
}
