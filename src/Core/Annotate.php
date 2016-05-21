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
     * 参数前缀
     *
     * @var string
     */
    private $prefix = 'cp_';

    /**
     * 要解析的字符串
     *
     * @var string
     */
    private $content;

    /**
     * @var Delegate
     */
    private $delegate;

    /**
     * @var Annotate
     */
    private static $instance;

    /**
     * 注册一个wrapper
     *
     * @param Delegate $delegate
     */
    private function __construct(Delegate $delegate)
    {
        $this->delegate = $delegate;
        stream_register_wrapper('annotate', 'Cross\Lib\StringToPHPStream');
    }

    /**
     * 生成解析注释配置单例对象
     *
     * @param string $annotate
     * @param Delegate $delegate
     * @return Annotate
     */
    public static function getInstance($annotate, Delegate $delegate)
    {
        if (!self::$instance) {
            self::$instance = new Annotate($delegate);
        }

        return self::$instance->setContent($annotate);
    }

    /**
     * 设置要解析的字符串
     *
     * @param $annotate
     * @return $this
     */
    function setContent($annotate)
    {
        $this->content = $annotate;
        return $this;
    }

    /**
     * 注释配置转换为数组
     *
     * @return array
     */
    public function parse()
    {
        $flag = preg_match_all("/@{$this->prefix}(.*?)\s+(.*)/", $this->content, $content);
        if (!$flag) {
            return array();
        }

        $configs = array_combine($content[1], $content[2]);
        return $this->parseAnnotate($configs);
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
                    $result['params'] = $this->parseConfigValue($params);
                    break;

                case 'cache':
                case 'response':
                case 'basicAuth':
                    $result[$conf] = $this->parseAnnotateConfig($params);
                    break;

                case 'after':
                case 'before':
                    $result[$conf] = $this->bindToClosure($params);
                    break;

                default:
                    $closureContainer = $this->delegate->getClosureContainer();
                    $hasClosure = $closureContainer->has('parseAnnotate');
                    if ($hasClosure) {
                        $closureContainer->run('parseAnnotate', array($conf, &$params));
                    }

                    $result[$conf] = $params;
            }
        }

        print_r($result);

        return $result;
    }

    /**
     * 把PHP代码绑定到匿名函数中
     *
     * @param string $params
     * @return Closure
     */
    protected function bindToClosure($params)
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
    protected function parseAnnotateConfig($params)
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
    private function parseConfigValue($params)
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

}
