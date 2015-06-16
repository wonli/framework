<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.3.0
 */
namespace Cross\Core;

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
     * @var Annotate
     */
    private static $instance;

    /**
     * 注册一个wrapper
     */
    private function __construct()
    {
        stream_register_wrapper('annotate', 'Cross\Lib\Other\StringToPHPStream');
    }

    /**
     * 生成解析注释配置单例对象
     *
     * @param $annotate
     * @return Annotate
     */
    public static function getInstance($annotate)
    {
        if (!self::$instance) {
            self::$instance = new Annotate();
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
        $flag = preg_match_all(sprintf('/@%s(.*?)\s+(.*?)\n/', $this->prefix), $this->content, $content);

        if (!$flag) {
            return array();
        }

        $conf = array_combine($content[1], $content[2]);
        $res = $this->parseAnnotate($conf);

        return $res;
    }

    /**
     * 注释配置解析
     *
     * @param $conf
     * @return array
     */
    private function parseAnnotate($conf)
    {
        $result = array();
        foreach ($conf as $conf_name => $params) {
            switch ($conf_name) {
                case 'params':
                    $result['params'] = $this->parseConfigValue($params);
                    break;

                default:
                    $result[$conf_name] = $this->parseAnnotateConfig($params);
            }
        }

        return $result;
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
     * 如: 1, type=file, 300 会被解析为
     * array(
     *      1,
     *      'type'  => file,
     *      300
     * )
     * </pre>
     *
     * @param $params
     * @return array
     */
    private function parseConfigValue($params)
    {
        $result = array();
        $conf = array_filter(preg_split('/[\s,]+/', $params));
        foreach ($conf as $c) {
            if (false !== strpos($c, '=')) {
                $c = explode('=', $c);
                $result[$c[0]] = isset($c[1]) ? $c[1] : '';
            } else {
                $result [] = $c;
            }
        }
        unset($conf);

        return $result;
    }

}
