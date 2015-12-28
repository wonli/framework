<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\MVC;

use Cross\Exception\CoreException;
use Cross\Core\CrossArray;
use Cross\Core\FrameBase;
use Cross\Lib\Array2XML;
use Cross\Core\Helper;
use Cross\Core\Loader;
use Cross\Core\Router;

/**
 * @Auth: wonli <wonli@live.com>
 * Class View
 * @package Cross\MVC
 */
class View extends FrameBase
{
    /**
     * 默认模板目录
     *
     * @var string
     */
    private $tpl_dir;

    /**
     * 资源配置
     *
     * @var array
     */
    private $res_list;

    /**
     * 默认模板路径
     *
     * @var string
     */
    private $tpl_base_path;

    /**
     * 默认url
     *
     * @var string
     */
    private $link_base = null;

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
     * 模版扩展文件名
     *
     * @var string
     */
    protected $tpl_file_ext_name = '.tpl.php';

    /**
     * url配置缓存
     *
     * @var array
     */
    protected static $url_config_cache = array();

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
        static $res_base_url = null;
        if (!isset($res_base_url[$use_static_url])) {
            if ($this->config->get('res_url')) {
                $base_url = $this->config->get('res_url');
            } elseif ($use_static_url) {
                $base_url = $this->config->get('static', 'url');
            } else {
                $base_url = $this->config->get('url', 'full_request');
            }

            $res_base_url[$use_static_url] = rtrim($base_url, '/') . '/';
        }

        return $res_base_url[$use_static_url] . $res_url;
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
     * 获取当前app名称
     *
     * @return array|string
     */
    function getAppName()
    {
        static $app_name = null;
        if ($app_name === null) {
            $app_name = $this->config->get('app', 'name');
        }

        return $app_name;
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
        static $tpl_path;
        $app_name = $this->getAppName();
        if (!isset($tpl_path[$app_name])) {
            $tpl_path[$app_name] = $this->getTplBasePath() . $this->getTplDir();
        }

        return $tpl_path[$app_name];
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
     * @return string
     */
    function e(array $data, $key, $default_value = '')
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return $default_value;
    }

    /**
     * 生成url
     *
     * @param null|string $controller
     * @param null|string|array $params
     * @param bool $sec
     * @return string
     */
    function url($controller = null, $params = null, $sec = false)
    {
        static $link_base = null;
        if ($link_base === null) {
            $link_base = $this->getLinkBase();
        }

        $uri = $this->makeUri($this->getAppName(), false, $controller, $params, $sec);
        return $link_base . '/' . $uri;
    }

    /**
     * @see View::url() 生成加密连接
     * @param null|string $controller
     * @param null|string|array $params
     * @return string
     */
    function sUrl($controller = null, $params = null)
    {
        return $this->url($controller, $params, true);
    }

    /**
     * @see View::url() 生成非加密连接
     * @param null|string $controller 控制器:方法
     * @param null|string|array $params
     * @return string
     */
    function link($controller = null, $params = null)
    {
        return $this->url($controller, $params);
    }

    /**
     * @see View::sUrl()
     * @param null|string $controller
     * @param null|string|array $params
     * @return string
     */
    function slink($controller = null, $params = null)
    {
        return $this->url($controller, $params, true);
    }

    /**
     * 生成指定app,指定控制器的url
     *
     * @param string $base_link
     * @param string $app_name
     * @param null|string $controller
     * @param null|string|array $params
     * @param null|bool $sec
     * @return string
     */
    function appUrl($base_link, $app_name, $controller = null, $params = null, $sec = null)
    {
        $uri = $this->makeUri($app_name, true, $controller, $params, $sec);
        return rtrim($base_link, '/') . '/' . $uri;
    }

    /**
     * 生成连接
     *
     * @param string $app_name
     * @param bool $check_app_name
     * @param null|string $controller
     * @param null|array $params
     * @param null|bool $sec
     * @return string
     * @throws CoreException
     */
    private function makeUri($app_name, $check_app_name, $controller = null, $params = null, $sec = null)
    {
        $uri = '';
        $enable_controller_cache = false;
        //在运行过程中,如果url的配置有变化,需要调用cleanLinkCache()来刷新缓存
        if (!isset(self::$url_config_cache[$app_name])) {
            $this_app_name = $app_name;
            if ($check_app_name) {
                $this_app_name = $this->getAppName();
            }

            if ($check_app_name && $app_name != $this_app_name) {
                $config = CrossArray::init(Loader::read(APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR . 'init.php'));
                $url_config = $config->get('url');
            } else {
                $url_config = $this->config->get('url');
            }

            self::$url_config_cache[$app_name] = $url_config;
        } else {
            $enable_controller_cache = true;
            $url_config = self::$url_config_cache[$app_name];
        }

        $url_params = '';
        $url_controller = $this->makeControllerUri($app_name, $enable_controller_cache, $controller, $url_config);
        if (!empty($params)) {
            $url_params = $this->makeParams($params, $url_config, $sec);
        }

        if (!empty($url_config['ext']) && !empty($url_controller)) {
            switch ($url_config['type']) {
                case 2:
                    $uri .= $url_controller . $url_config['ext'] . $url_params;
                    break;
                case 1:
                case 3:
                case 4:
                case 5:
                    $uri .= $url_controller . $url_params . $url_config['ext'];
                    break;
            }
        } else {
            $uri .= $url_controller . $url_params;
        }

        return $uri;
    }

    /**
     * 生成控制器连接
     *
     * @param string $app_name
     * @param bool $use_cache 是否使用缓存
     * @param string $controller
     * @param array $url_config
     * @return string
     * @throws CoreException
     */
    private function makeControllerUri($app_name, $use_cache, $controller, array $url_config)
    {
        static $controller_uri_cache;
        if (isset($controller_uri_cache[$app_name][$controller]) && $use_cache) {
            return $controller_uri_cache[$app_name][$controller];
        }

        $_action = null;
        $real_controller = $controller;
        $app_alias_config = $this->parseControllerAlias($app_name);
        if (isset($app_alias_config[$controller])) {
            $real_controller = $app_alias_config[$controller];
        }

        if (false !== strpos($real_controller, ':')) {
            list($_controller, $_action) = explode(':', $real_controller);
        } else {
            $_controller = $real_controller;
        }

        $controller_uri = '';
        if ($url_config ['rewrite']) {
            $controller_uri .= $_controller;
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
                case 5:
                    $_dot = $index_file_name . '/';
                    break;

                default:
                    throw new CoreException('不支持的url type');
            }

            $controller_uri .= $_dot . $_controller;
        }

        if (null !== $_action) {
            $controller_uri .= $url_config['dot'] . $_action;
        }

        $controller_uri_cache[$app_name][$controller] = $controller_uri;
        return $controller_uri;
    }

    /**
     * 生成link参数
     *
     * @param array $params
     * @param array $url_config
     * @param bool $sec
     * @return string
     */
    private function makeParams(array $params, array $url_config, $sec = false)
    {
        $_params = '';
        $_dot = $url_config['dot'];

        if ($params) {
            switch ($url_config['type']) {
                case 1:
                case 5:
                    $_params = implode($_dot, $params);
                    break;

                case 2:
                    $_dot = '?';
                    $_params = http_build_query($params);
                    break;

                case 3:
                case 4:
                    foreach ($params as $p_key => $p_val) {
                        $_params .= $p_key . $_dot . $p_val . $_dot;
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
     * 解析路由别名配置
     *
     * @param string $app_name
     * @return array
     * @throws CoreException
     */
    private function parseControllerAlias($app_name)
    {
        static $router_alias_cache;
        if (!isset($router_alias_cache[$app_name])) {
            $router = $this->config->get('router');
            $router_alias_cache[$app_name] = array();
            if (!empty($router)) {
                foreach ($router as $controller_alias => $alias_config) {
                    $router_alias_cache[$app_name][$alias_config] = $controller_alias;
                }
            }
        }

        return $router_alias_cache[$app_name];
    }

    /**
     * 清除link中使用到的缓存(config->url配置在运行过程中发生变动时先清除缓存)
     */
    function cleanLinkCache()
    {
        unset(self::$url_config_cache[$this->getAppName()]);
    }

    /**
     * 渲染模板
     *
     * @param null $data
     * @param null $method
     */
    function display($data = null, $method = null)
    {
        $load_layer = true;
        $this->data = $data;
        if ($method === null) {
            $display_type = $this->config->get('sys', 'display');
            if ($display_type && strcasecmp($display_type, 'html') !== 0) {
                $load_layer = false;
                $method = trim($display_type);
            } else if ($this->action) {
                $method = $this->action;
            } else {
                $method = Router::$default_action;
            }
        }

        $this->obRender($data, $method, $load_layer);
    }

    /**
     * 输出带layer的view
     *
     * @param array $data
     * @param string $method
     * @param bool $load_layer
     * @return string|void
     * @throws CoreException
     */
    function obRender($data, $method, $load_layer)
    {
        ob_start();
        $this->$method($data);
        $method_content = ob_get_clean();
        parent::getDelegate()->getClosureContainer()->run('obRender', $method_content);

        if ($load_layer) {
            $this->loadLayer($method_content);
        } else {
            echo $method_content;
        }
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
        $this->delegate->getResponse()->setContentType('json');
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
        $this->delegate->getResponse()->setContentType('xml');
        echo Array2XML::createXML($root_name, $data)->saveXML();
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

        if (!is_file($layer_file)) {
            throw new CoreException($layer_file . ' layer Not found!');
        }

        include $layer_file;
    }
}
