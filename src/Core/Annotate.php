<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.5
 */
namespace Cross\Core;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Annotate
 * @package Cross\Core
 */
class Annotate
{
    function __construct($annotate)
    {
        $this->content = $annotate;
    }

    /**
     * 生成解析注释配置单例对象
     *
     * @param $annotate
     * @return Annotate
     */
    public static function getInstance($annotate)
    {
        return new Annotate($annotate);
    }

    /**
     * 注释配置转换为数组
     *
     * @return array|bool
     */
    public function parse()
    {
        $flag = preg_match_all('/@cp_(.*?)\s+(.*?)\n/', $this->content, $content);

        if (!$flag) {
            return true;
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
        foreach ($conf as $func => $params) {
            switch ($func) {
                case 'params':
                    $result['params'] = $this->parseConfigValue($params);
                    break;

                case 'cache':
                    $result['cache'] = $this->parseAnnotateCacheConfig($params);
                    break;
            }
        }

        return $result;
    }

    /**
     * 配置参数转二维数组
     * <pre>
     *  如: true(...)
     *  将被转换为
     *  array(
     *      true,
     *      array(...)
     *  )
     * </pre>
     *
     * @param $params
     * @return array
     */
    private function parseAnnotateCacheConfig($params)
    {
        $result = array();
        $flag = preg_match_all('/(.*?)\((.*?)\)/', $params, $params);

        if ($flag) {
            $result[] = $params[1][0] === 'true' ? true : false;
            $result[] = $this->parseConfigValue($params[2][0]);
        }

        return $result;
    }

    /**
     * 配置参数值解析
     * <pre>
     * 如: 1, type:file, 300 会被解析为
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
