<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.3
 */
namespace Cross\MVC;

use Cross\Core\FrameBase;
use Cross\Core\Helper;
use Cross\Core\Response;
use Cross\Core\Router;
use Cross\Exception\CoreException;
use Cross\Lib\ArrayOperate\Array2XML;

/**
 * @Auth: wonli <wonli@live.com>
 * Class View
 * @package Cross\MVC
 */
class View extends FrameBase
{
    /**
     * 模板数据
     *
     * @var array
     */
    protected $data;

    /**
     * 设置layer中变量的值
     *
     * @var array
     */
    protected $set = array();

    /**
     * 资源配置
     *
     * @var array
     */
    protected $res_list;

    /**
     * 默认模板目录
     *
     * @var string
     */
    protected $tpl_dir;

    /**
     * 默认模板路径
     *
     * @var string
     */
    protected $tpl_base_path;

    /**
     * 默认url
     *
     * @var string
     */
    protected $link_base = null;

    /**
     * 模版扩展文件名
     *
     * @var string
     */
    protected $tpl_file_ext_name = '.tpl.php';

    /**
     * 模版文件夹路径
     *
     * @var string
     */
    protected static $tpl_path;

    /**
     * 控制器解析缓存
     *
     * @var array
     */
    protected static $controller_cache = array();

    /**
     * url配置缓存
     *
     * @var array
     */
    protected static $url_config_cache = array();

    /**
     * 路由别名配置缓存
     *
     * @var array
     */
    protected static $router_alias_cache = array();

    /**
     * 模板的绝对路径
     *
     * @param $tpl_name
     * @param bool $get_content 是否读取模板内容
     * @return string
     */
    function tpl($tpl_name, $get_content = false)
    {
        $file_path = $this->getTplPath() . $tpl_name . $this->tpl_file_ext_name;
        if (true === $get_content) {
            return file_get_contents($file_path, true);
        }

        return $file_path;
    }

    /**
     * 载入模板, 并输出$data变量中的数据
     *
     * @param $tpl_name
     * @param array|mixed $data
     */
    function renderTpl($tpl_name, $data = array())
    {
        include $this->tpl($tpl_name);
    }

    /**
     * 设置layer附加参数
     *
     * @param $name
     * @param null $value
     * @return $this
     */
    final function set($name, $value = null)
    {
        if (is_array($name)) {
            $this->set = array_merge($this->set, $name);
        } else {
            $this->set[$name] = $value;
        }

        return $this;
    }

    /**
     * 生成资源文件路径
     *
     * @param $res_url
     * @param bool $use_static_url
     * @return string
     */
    function res($res_url, $use_static_url = true)
    {
        if ($this->config->get('res_url')) {
            $res_base_url = $this->config->get('res_url');
        } elseif ($use_static_url) {
            $res_base_url = $this->config->get('static', 'url');
        } else {
            $res_base_url = $this->config->get('url', 'full_request');
        }

        return rtrim($res_base_url, '/') . '/' . $res_url;
    }

    /**
     * 获取生成连接的基础路径
     *
     * @return string
     */
    function getLinkBase()
    {
        if (null === $this->link_base) {
            $this->setLinkBase($this->config->get('url', 'full_request'));
        }

        return $this->link_base;
    }

    /**
     * 设置生成的连接基础路径
     *
     * @param $link_base
     */
    function setLinkBase($link_base)
    {
        $this->link_base = $link_base;
    }

    /**
     * 模板路径
     *
     * @return string 要加载的模板路径
     */
    function getTplPath()
    {
        $app_name = $this->config->get('app', 'name');
        if (! isset(self::$tpl_path[$app_name])) {
            self::$tpl_path[$app_name] = $this->getTplBasePath().$this->getTplDir();
        }
        return self::$tpl_path[$app_name];
    }

    /**
     * 设置模板路径
     *
     * @param $tpl_base_path
     */
    function setTplBasePath($tpl_base_path)
    {
        $this->tpl_base_path = rtrim($tpl_base_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取模板默认路径
     *
     * @return string
     */
    function getTplBasePath()
    {
        if (!$this->tpl_base_path) {
            $this->setTplBasePath($this->config->get('app', 'path') . 'templates' . DIRECTORY_SEPARATOR);
        }

        return $this->tpl_base_path;
    }

    /**
     * 安全的输出数组中的值
     *
     * @param array $data
     * @param string|int $key
     * @param string $default_value
     */
    function e($data, $key, $default_value = '')
    {
        if (isset($data[$key])) {
            echo $data[$key];
        } else {
            echo $default_value;
        }
    }

    /**
     * 生成连接
     *
     * @param null $controller 控制器:方法
     * @param null $params
     * @param bool $sec
     * @return string
     */
    function link($controller = null, $params = null, $sec = false)
    {
        $url = $this->getLinkBase();
        $app_name = $this->config->get('app', 'name');
        if (! isset(self::$url_config_cache[$app_name])) {
            $url_config = $this->config->get('url');
            self::$url_config_cache[$app_name] = $url_config;
        } else {
            $url_config = self::$url_config_cache[$app_name];
        }

        if (! isset(self::$controller_cache[$app_name][$controller])) {
            $url_controller = '';
            if ($controller !== null) {
                $url_controller = $this->makeController($app_name, $controller, $url_config);
            }

            self::$controller_cache[$app_name][$controller] = $url_controller;
        } else {
            $url_controller = self::$controller_cache[$app_name][$controller];
        }

        $url_params = '';
        if ($params != null) {
            $url_params = $this->makeParams($params, $url_config, $sec);
        }

        if (! empty($url_config['ext']) && ! empty($url_controller)) {
            switch($url_config['type'])
            {
                case 2:
                    $url .= $url_controller.$url_config['ext'].$url_params;
                    break;
                case 1:
                case 3:
                case 4:
                    $url .= $url_controller.$url_params.$url_config['ext'];
                    break;
            }
        } else {
            $url .= $url_controller.$url_params;
        }

        return $url;
    }

    /**
     * 生成参数加密的连接
     *
     * @param null $_controller
     * @param null $params
     * @return string
     */
    function slink($_controller = null, $params = null)
    {
        return $this->link($_controller, $params, true);
    }

    /**
     * 生成控制器连接
     *
     * @param string $app_name
     * @param string $controller
     * @param array $url_config
     * @throws \Cross\Exception\CoreException
     * @return string
     */
    private function makeController($app_name, $controller, $url_config)
    {
        $_link_url = '/';
        $this->getControllerAlias($app_name, $controller, $_controller, $_action);

        if ($url_config ['rewrite']) {
            $_link_url .= $_controller;
        } else {
            $index_file_name = $url_config ['index'];
            switch ($url_config['type']) {
                case 1:
                case 3:
                    if (strcasecmp($index_file_name, 'index.php') == 0) {
                        $_dot = '?';
                        if ($_controller) {
                            $_dot .= '/';
                        }
                    } else {
                        $_dot = $index_file_name . '?';
                    }
                    break;

                case 2:
                case 4:
                    $_dot = $index_file_name . '/';
                    break;

                default:
                    throw new CoreException('不支持的url type');
            }

            $_link_url .= $_dot . $_controller;
        }

        if (null != $_action) {
            $_link_url .= $url_config['dot'] . $_action;
        }

        return $_link_url;
    }

    /**
     * 生成link参数
     *
     * @param array|string $params
     * @param array $url_config
     * @param bool $sec
     * @return string
     */
    private function makeParams($params, $url_config, $sec = false)
    {
        $_params = '';
        $_dot = $url_config['dot'];

        if ($params) {
            switch ($url_config['type']) {
                case 1:
                    if (is_array($params)) {
                        $_params = implode($_dot, $params);
                    } else {
                        $url_str = array();
                        parse_str($params, $url_str);
                        $_params = implode($_dot, $url_str);
                    }
                    break;

                case 2:
                    $_dot = '?';
                    if (is_array($params)) {
                        $_params = http_build_query($params);
                    } else {
                        $_params = $params;
                    }
                    break;

                case 3:
                case 4:
                    if (!is_array($params)) {
                        $p = array();
                        parse_str($params, $p);
                    } else {
                        $p = $params;
                    }

                    foreach ($p as $p_key => $p_val) {
                        $_params .= $p_key.$_dot.$p_val.$_dot;
                    }
                    $_params = rtrim($_params, $_dot);
                    break;
            }

            if (true === $sec) {
                $_params = $this->urlEncrypt($_params);
            }
        }

        return $_dot . $_params;
    }

    /**
     * 解析控制器别名
     *
     * @param string $app_name
     * @param string $controller
     * @param string $_controller
     * @param string $_action
     */
    private function getControllerAlias($app_name, $controller, & $_controller, & $_action)
    {
        $alias_config = $this->parseControllerAlias($app_name);
        if (isset($alias_config[$controller])) {
            $_controller = $alias_config[$controller];
        } else {
            $_action = null;
            if (false !== strpos($controller, ':')) {
                list($_controller, $_action) = explode(':', $controller);
            } else {
                $_controller = $controller;
            }

            if (isset($alias_config[$_controller])) {
                $controller_action_alias_config = $alias_config[$_controller];
                if (isset($controller_action_alias_config[$_action])) {
                    $_action = $controller_action_alias_config[$_action];
                }
            }
        }
    }

    /**
     * 解析路由别名配置
     *
     * @param string $app_name
     * @return array
     */
    private function parseControllerAlias($app_name)
    {
        if (! isset(self::$router_alias_cache[$app_name])) {
            $router = $this->config->get('router');
            self::$router_alias_cache[$app_name] = array();
            if (! empty($router)) {
                foreach($router as $controller_alias => $real_controller) {
                    if (is_array($real_controller)) {
                        self::$router_alias_cache[$app_name][$controller_alias] = array_flip($real_controller);
                    } else {
                        self::$router_alias_cache[$app_name][$real_controller] = $controller_alias;
                    }
                }
            }
        }

        return self::$router_alias_cache[$app_name];
    }

    /**
     * 渲染模板
     *
     * @param null $data
     * @param null $method
     */
    function display($data = null, $method = null)
    {
        $this->data = $data;
        if ($method === null) {
            $display_type = $this->config->get('sys', 'display');
            if ($display_type && $display_type != 'HTML') {
                $method = strtoupper($display_type);
            } else {
                $method = $this->action;
            }
        }

        if (!$method) {
            $method = Router::$default_action;
        }

        $this->obRender($data, $method);
    }

    /**
     * 输出带layer的view
     *
     * @param $data
     * @param $method
     */
    function obRender($data, $method)
    {
        ob_start();
        $this->$method($data);
        $this->loadLayer(ob_get_clean());
    }

    /**
     * 设置模板dir
     *
     * @param $dir_name
     */
    function setTplDir($dir_name)
    {
        $this->tpl_dir = $dir_name;
    }

    /**
     * 取得模板路径前缀
     *
     * @return string
     */
    function getTplDir()
    {
        if (!$this->tpl_dir) {
            $default_tpl_dir = $this->config->get('sys', 'default_tpl_dir');
            if ($default_tpl_dir) {
                $default_tpl_dir = rtrim($default_tpl_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            } else {
                $default_tpl_dir = 'default';
            }
            $this->setTplDir($default_tpl_dir);
        }

        return $this->tpl_dir;
    }

    /**
     * 运行时加载css/js
     *
     * @param $res_url
     * @param string $location
     * @param bool $convert
     */
    function addRes($res_url, $location = 'header', $convert = true)
    {
        $this->res_list [$location][] = array(
            'url' => $res_url,
            'convert' => $convert
        );
    }

    /**
     * 加载 css|js
     *
     * @param string $location
     * @return string
     */
    function loadRes($location = 'header')
    {
        $result = '';
        if (empty($this->res_list) || empty($this->res_list[$location])) {
            return $result;
        }

        if (isset($this->res_list [$location]) && !empty($this->res_list [$location])) {
            $data = $this->res_list [$location];
        }

        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $r) {
                    $result .= $this->outputResLink($r['url'], $r['convert']);
                }
            } else {
                $result .= $this->outputResLink($data);
            }
        }

        return $result;
    }

    /**
     * 输出js/css连接
     *
     * @param $res_link
     * @param bool $make_link
     * @return null|string
     */
    function outputResLink($res_link, $make_link = true)
    {
        $t = Helper::getExt($res_link);
        switch (strtolower($t)) {
            case 'js' :
                $tpl = '<script type="text/javascript" src="%s"></script>';
                break;

            case 'css' :
                $tpl = '<link rel="stylesheet" type="text/css" href="%s"/>';
                break;

            default :
                $tpl = null;
        }

        if (null !== $tpl) {
            if ($make_link) {
                $res_link = $this->res($res_link);
            }

            return sprintf("{$tpl}\n", $res_link);
        }

        return null;
    }

    /**
     * 输出JSON
     *
     * @param $data
     */
    function JSON($data)
    {
        $this->set(
            array('layer' => 'json')
        );

        Response::getInstance()->setContentType('json');
        echo json_encode($data);
    }

    /**
     * 输出XML
     *
     * @param $data
     * @param string $root_name
     */
    function XML($data, $root_name = 'root')
    {
        $this->set(
            array('layer' => 'xml')
        );

        Response::getInstance()->setContentType('xml');
        $xml = Array2XML::createXML($root_name, $data);

        echo $xml->saveXML();
    }

    /**
     * 加载布局
     *
     * @param string $content
     * @param string $layer_ext
     * @throws CoreException
     */
    function loadLayer($content, $layer_ext = '.layer.php')
    {
        if ($this->set) {
            extract($this->set, EXTR_PREFIX_SAME, 'USER_DEFINED');
        }
        $_real_path = $this->getTplPath();

        //运行时>配置>默认
        if (isset($layer)) {
            $layer_file = $_real_path . $layer . $layer_ext;
        } else {
            $layer_file = $_real_path . 'default' . $layer_ext;
        }

        if (!file_exists($layer_file)) {
            throw new CoreException($layer_file . ' layer Not found!');
        }

        include $layer_file;
    }
}


