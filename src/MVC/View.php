<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\MVC;

use Cross\Exception\CoreException;
use Cross\Lib\Document\HTML;
use Cross\Lib\Array2XML;
use Cross\Core\FrameBase;
use Cross\Core\Config;
use Cross\Core\Router;
use Cross\Core\Helper;
use Exception;

/**
 * @author wonli <wonli@live.com>
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
     * @var array
     */
    private $wrap_stack = [];

    /**
     * 模板数据
     *
     * @var array
     */
    protected $data;

    /**
     * 初始化布局文件中的变量
     * <pre>
     * title 标题
     * keywords 关键词
     * description 页面描述
     *
     * layer 布局模板名称
     * load_layer 是否加载布局模板
     * </pre>
     *
     * @var array
     */
    protected $set = [
        'title' => '',
        'keywords' => '',
        'description' => '',

        'layer' => 'default',
        'load_layer' => true,
    ];

    /**
     * 模版扩展文件名
     *
     * @var string
     */
    protected $tpl_file_suffix = '.tpl.php';

    /**
     * url配置缓存
     *
     * @var array
     */
    protected static $url_config_cache = [];

    /**
     * 渲染模板
     *
     * @param null $data
     * @param string $method
     * @throws CoreException
     */
    function display($data = null, string $method = null): void
    {
        $this->data = &$data;
        $contentType = $this->config->get('sys', 'content_type');
        $this->response->setContentType($contentType ? $contentType : 'html');

        if ($method === null) {
            $method = $this->action ? $this->action : Router::DEFAULT_ACTION;
        }

        ob_start();
        $this->obRenderAction($data, $method);
        $this->response->setContent(ob_get_clean());
    }

    /**
     * 生成url
     *
     * @param string $controller
     * @param null|string|array $params
     * @param bool $encrypt_params
     * @return string
     * @throws CoreException
     */
    function url(string $controller = null, $params = null, bool $encrypt_params = false): string
    {
        static $link_base = null;
        if ($link_base === null) {
            $link_base = $this->getLinkBase();
        }

        if ($controller === null && $params === null) {
            return $link_base;
        } else {
            $uri = $this->makeUri($this->getAppName(), false, $controller, $params, $encrypt_params);
            return $link_base . $uri;
        }
    }

    /**
     * 生成参数加密的超链接
     *
     * @param string $controller
     * @param null|string|array $params
     * @return string
     * @throws CoreException
     * @see View::url()
     */
    function sUrl(string $controller = null, $params = null): string
    {
        return $this->url($controller, $params, true);
    }

    /**
     * 超链接
     *
     * @param string $content
     * @param string $href
     * @param array $element_tags
     * @return mixed|string
     */
    function a(string $content, string $href = '', array $element_tags = [])
    {
        $element_tags['href'] = $href;
        $element_tags['@content'] = $content;
        return $this->wrapTag('a', $element_tags);
    }

    /**
     * 输出img标签
     *
     * @param string $src
     * @param array $element_tags
     * @return mixed|string
     */
    function img(string $src, array $element_tags = [])
    {
        $element_tags['src'] = $src;
        $element_tags += ['border' => 0, 'alt' => 'image'];
        return $this->wrapTag('img', $element_tags);
    }

    /**
     * input标签
     *
     * @param string $type
     * @param array $element_tags
     * @return mixed|string
     */
    function input(string $type, array $element_tags = [])
    {
        $element_tags['type'] = $type;
        return $this->wrapTag('input', $element_tags);
    }

    /**
     * 单选
     *
     * @param array $data
     * @param string $default_value
     * @param array $radio_tags
     * @param array $label_tags
     * @return string
     * @see View::buildRadioOrCheckbox
     */
    function radio(array $data, $default_value = '', array $radio_tags = [], array $label_tags = [])
    {
        $default_value = [$default_value => true];
        return $this->buildRadioOrCheckbox('radio', $data, $default_value, $radio_tags, $label_tags);
    }

    /**
     * 多选
     *
     * @param array $data
     * @param string|array $default_value
     * @param array $checkbox_tags
     * @param array $label_tags
     * @return string
     * @see View::buildRadioOrCheckbox
     */
    function checkbox(array $data, $default_value = '', array $checkbox_tags = [], array $label_tags = [])
    {
        if (is_array($default_value)) {
            $default_value = array_flip($default_value);
        } else {
            $default_value = [$default_value => true];
        }

        return $this->buildRadioOrCheckbox('checkbox', $data, $default_value, $checkbox_tags, $label_tags);
    }

    /**
     * 输出select
     *
     * @param array $options_data 二维数组时, 生成optgroup
     * @param int|string|array $default_value
     * @param array $select_params
     * @param array $user_option_params
     * @return mixed
     */
    function select(array $options_data, $default_value = null, array $select_params = [], array $user_option_params = [])
    {
        $content = '';
        if (is_array($default_value)) {
            $default_value = array_flip($default_value);
        } else {
            $default_value = [$default_value => true];
        }

        foreach ($options_data as $value => $option) {
            $option_params = [];
            if (!empty($user_option_params)) {
                $option_params = $user_option_params;
            }

            if (is_array($option)) {
                $opt_content = '';
                foreach ($option as $opt_value => $opt_option) {
                    unset($option_params['selected']);
                    $option_params['value'] = $opt_value;
                    $option_params['@content'] = $opt_option;
                    if (isset($default_value[$opt_value])) {
                        $option_params['selected'] = true;
                    }

                    $opt_content .= self::htmlTag('option', $option_params);
                }

                $opt_params['label'] = $value;
                $opt_params['@content'] = $opt_content;
                $content .= self::htmlTag('optgroup', $opt_params);
            } else {
                $option_params['value'] = $value;
                $option_params['@content'] = $option;
                if (isset($default_value[$value])) {
                    $option_params['selected'] = true;
                }

                $content .= self::htmlTag('option', $option_params);
            }
        }

        $select_params['@content'] = $content;
        return $this->wrapContent(self::htmlTag('select', $select_params), false);
    }

    /**
     * 输出HTML
     * <pre>
     * 带wrap时, 先处理wrap
     * 单独输出HTML内容时候, $encode表示是否转换HTML实体
     * </pre>
     *
     * @param string $content 内容
     * @param bool $encode 是否转码
     * @return mixed
     */
    function html(string $content, bool $encode = true)
    {
        return $this->wrapContent($content, $encode);
    }

    /**
     * 输出任意HTML标签
     *
     * @param string $element
     * @param array $element_tags
     * @return string
     */
    static function htmlTag(string $element, array $element_tags = [])
    {
        return HTML::$element($element_tags);
    }

    /**
     * HTML标签入栈
     *
     * @param string $element
     * @param string|array $element_tags
     * @param bool $content_rear 自身内容是否放在被包裹内容之后
     * @return $this
     */
    function wrap(string $element, $element_tags = [], bool $content_rear = false): self
    {
        if (!is_array($element_tags)) {
            $element_tags = ['@content' => $element_tags];
        }

        array_unshift($this->wrap_stack, array($element, $element_tags, $content_rear));
        return $this;
    }

    /**
     * 带wrap的块级元素
     *
     * @param string $content 内容
     * @param array $element_tags
     * @param string $element
     * @return string
     */
    function block(string $content, array $element_tags = [], string $element = 'div')
    {
        $element_tags['@content'] = $content;
        return $this->wrapTag($element, $element_tags);
    }

    /**
     * 处理带模板的block元素
     *
     * @param string $tpl_name 模板名称
     * @param array $tpl_data 模板中的数据
     * @param array $element_tags
     * @param string $element
     * @return string
     */
    function section(string $tpl_name, array $tpl_data = [], array $element_tags = [], string $element = 'div')
    {
        return $this->block($this->obRenderTpl($tpl_name, $tpl_data), $element_tags, $element);
    }

    /**
     * 生成表单
     * <pre>
     * 使用$this->on('buildForm', ....), 来干预所有生成的表单内容
     * </pre>
     *
     * @param string $tpl_name 包含表单的模板文件路径
     * @param array $form_tags 生成表单tag的参数
     * @param array $tpl_data 模板数据
     * @return string
     */
    function buildForm(string $tpl_name, array $form_tags = [], array $tpl_data = [])
    {
        $content = $this->delegate->getClosureContainer()->run('buildForm', [$this]);
        $content .= $this->obRenderTpl($tpl_name, $tpl_data);

        $form_tags += ['action' => '', 'method' => 'post'];
        $form_tags['@content'] = $content;

        return $this->wrapTag('form', $form_tags);
    }

    /**
     * 加载指定名称的模板文件
     *
     * @param string $tpl_name
     * @param array $data
     */
    function renderTpl(string $tpl_name, array $data = []): void
    {
        include $this->tpl($tpl_name);
    }

    /**
     * 加载指定绝对路径的文件
     *
     * @param string $file 文件绝对路径
     * @param array $data
     */
    function renderFile(string $file, array $data = []): void
    {
        include $file;
    }

    /**
     * 带缓存的renderTpl
     *
     * @param string $tpl_name
     * @param array $data
     * @param bool $encode
     * @return string
     */
    function obRenderTpl(string $tpl_name, array $data = [], bool $encode = false)
    {
        ob_start();
        $this->renderTpl($tpl_name, $data);
        return $this->wrapContent(ob_get_clean(), $encode);
    }

    /**
     * 带缓存的renderFile
     *
     * @param string $file
     * @param array $data
     * @param bool $encode
     * @return string
     */
    function obRenderFile(string $file, array $data = [], bool $encode = false)
    {
        ob_start();
        $this->renderFile($file, $data);
        return $this->wrapContent(ob_get_clean(), $encode);
    }

    /**
     * 模板的绝对路径
     *
     * @param string $tpl_name
     * @param bool $get_content 是否读取模板内容
     * @param bool $auto_append_suffix 是否自动添加模版后缀
     * @return string
     */
    function tpl(string $tpl_name, bool $get_content = false, bool $auto_append_suffix = true): string
    {
        $file_path = $this->getTplPath() . $tpl_name;
        if ($auto_append_suffix) {
            $file_path .= $this->tpl_file_suffix;
        }

        if (true === $get_content) {
            return file_get_contents($file_path, true);
        }

        return $file_path;
    }

    /**
     * 输出JSON
     *
     * @param array $data
     */
    function JSON(array $data): void
    {
        $this->set['load_layer'] = false;
        $this->delegate->getResponse()->setContentType('json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 输出XML
     *
     * @param array $data
     * @param string $root_name
     * @throws Exception
     */
    function XML(array $data, string $root_name = 'root'): void
    {
        $this->set['load_layer'] = false;
        $this->delegate->getResponse()->setContentType('xml');
        echo Array2XML::createXML($root_name, $data)->saveXML();
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
     * 判断数组中的值是否为empty,否则返回默认值
     *
     * @param array $data
     * @param string|int $key
     * @param string $default_value
     * @return string
     * @see e
     */
    function ee(array $data, $key, $default_value = '')
    {
        if (!empty($data[$key])) {
            return $data[$key];
        }

        return $default_value;
    }

    /**
     * 设置layer附加参数
     *
     * @param mixed $name
     * @param null $value
     * @return $this
     */
    final public function set($name, $value = null): self
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
     * @param string $res_url
     * @param bool $use_static_url
     * @return string
     */
    function res(string $res_url, bool $use_static_url = true): string
    {
        static $res_base_url = null;
        if (!isset($res_base_url[$use_static_url])) {
            if ($use_static_url) {
                $base_url = $this->config->get('static', 'url');
            } else {
                $base_url = $this->config->get('url', 'full_request');
            }

            $res_base_url[$use_static_url] = rtrim($base_url, '/') . '/';
        }

        return $res_base_url[$use_static_url] . $res_url;
    }

    /**
     * 输出资源相对路径
     *
     * @param string $res_url
     * @param string $res_dir
     * @return string
     */
    function relRes(string $res_url, string $res_dir = 'static'): string
    {
        static $res_base_url = null;
        if (null === $res_base_url) {
            $res_base_url = rtrim($this->config->get('url', 'request'), '/') . '/' . $res_dir . '/';
        }

        return $res_base_url . $res_url;
    }

    /**
     * 生成指定app,指定控制器的url
     *
     * @param string $base_link
     * @param string $app_name
     * @param null|string $controller
     * @param null|string|array $params
     * @param null|bool $encrypt_params
     * @return string
     * @throws CoreException
     */
    function appUrl(string $base_link, string $app_name, $controller = null, $params = null, $encrypt_params = null): string
    {
        $base_link = rtrim($base_link, '/') . '/';
        if ($controller === null && $params === null) {
            return $base_link;
        } else {
            $uri = $this->makeUri($app_name, true, $controller, $params, $encrypt_params);
            return $base_link . $uri;
        }
    }

    /**
     * 清除link中使用到的缓存(config->url配置在运行过程中发生变动时先清除缓存)
     */
    function cleanLinkCache(): void
    {
        unset(self::$url_config_cache[$this->getAppName()]);
    }

    /**
     * 设置模板dir
     *
     * @param string $dir_name
     */
    function setTplDir(string $dir_name): void
    {
        $this->tpl_dir = $dir_name;
    }

    /**
     * 获取生成连接的基础路径
     *
     * @return string
     */
    function getLinkBase(): string
    {
        if (null === $this->link_base) {
            $this->setLinkBase($this->config->get('url', 'full_request'));
        }

        return $this->link_base;
    }

    /**
     * 获取当前app名称
     *
     * @return string
     */
    function getAppName(): string
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
     * @param string $link_base
     */
    function setLinkBase(string $link_base): void
    {
        $this->link_base = rtrim($link_base, '/') . '/';
    }

    /**
     * 模板路径
     *
     * @return string 要加载的模板路径
     */
    function getTplPath(): string
    {
        static $tpl_path;
        $app_name = $this->getAppName();
        if (!isset($tpl_path[$app_name])) {
            $tpl_path[$app_name] = $this->getTplBasePath() . $this->getTplDir() . DIRECTORY_SEPARATOR;
        }

        return $tpl_path[$app_name];
    }

    /**
     * 设置模板路径
     *
     * @param string $tpl_base_path
     */
    function setTplBasePath(string $tpl_base_path): void
    {
        $this->tpl_base_path = rtrim($tpl_base_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取模板默认路径
     *
     * @return string
     */
    function getTplBasePath(): string
    {
        if (!$this->tpl_base_path) {
            $this->setTplBasePath($this->config->get('app', 'path') . 'templates' . DIRECTORY_SEPARATOR);
        }

        return $this->tpl_base_path;
    }

    /**
     * 取得模板路径前缀
     *
     * @return string
     */
    function getTplDir(): string
    {
        if (!$this->tpl_dir) {
            $default_tpl_dir = $this->config->get('sys', 'default_tpl_dir');
            if (!$default_tpl_dir) {
                $default_tpl_dir = 'default';
            }
            $this->setTplDir($default_tpl_dir);
        }

        return $this->tpl_dir;
    }

    /**
     * 运行时分组添加css/js
     *
     * @param string $res_url
     * @param string $location
     * @param bool $convert
     */
    function addRes(string $res_url, string $location = 'header', bool $convert = true): void
    {
        $this->res_list[$location][] = array(
            'url' => $res_url,
            'convert' => $convert
        );
    }

    /**
     * 分组加载css|js
     *
     * @param string $location
     * @return string
     */
    function loadRes(string $location = 'header'): string
    {
        $result = '';
        if (empty($this->res_list) || empty($this->res_list[$location])) {
            return $result;
        }

        if (isset($this->res_list[$location]) && !empty($this->res_list[$location])) {
            $data = $this->res_list[$location];
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
     * 生成连接
     *
     * @param string $app_name
     * @param bool $check_app_name
     * @param null|string $controller
     * @param null|array $params
     * @param bool $encrypt_params
     * @return string
     * @throws CoreException
     */
    protected function makeUri(string $app_name, bool $check_app_name, $controller = null, $params = null, bool $encrypt_params = false): string
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
                $config = Config::load(APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR . 'init.php');
            } else {
                $config = $this->config;
            }

            $url_router_config = array(
                'url' => $config->get('url'),
                'router' => $config->get('router')
            );
            self::$url_config_cache[$app_name] = $url_router_config;
        } else {
            $enable_controller_cache = true;
            $url_router_config = self::$url_config_cache[$app_name];
        }

        $url_params = '';
        $has_controller_string = true;
        $url_controller = $this->makeControllerUri($app_name, $enable_controller_cache, $controller, $url_router_config);
        if ($url_controller === '') {
            $has_controller_string = false;
        }

        $url_config = &$url_router_config['url'];
        if (!empty($params)) {
            $url_params = $this->makeParams($params, $url_config, $encrypt_params, $has_controller_string);
        }

        if (!empty($url_config['ext'])) {
            if ($url_config['type'] > 2) {
                if ($has_controller_string) {
                    $uri .= $url_controller . $url_config['ext'];
                }

                $uri .= $url_params;
            } else {
                $uri .= $url_controller . $url_params . $url_config['ext'];
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
     */
    protected function makeControllerUri(string $app_name, bool $use_cache, string $controller, array $url_config): string
    {
        static $path_cache;
        if (isset($path_cache[$app_name][$controller]) && $use_cache) {
            return $path_cache[$app_name][$controller];
        }

        $app_alias_config = $this->parseControllerAlias($app_name, $url_config['router']);
        if (isset($app_alias_config[$controller])) {
            $real_controller = $app_alias_config[$controller];
        } else {
            $real_controller = $controller;
        }

        $action_name = null;
        if (false !== strpos($real_controller, ':')) {
            list($controller_name, $action_name) = explode(':', $real_controller);
        } else {
            $controller_name = $real_controller;
        }

        $url = &$url_config['url'];
        $index = $this->makeIndex($url, true);
        $controller_path = $index . $controller_name;

        if (null !== $action_name) {
            $controller_path .= $url['dot'] . $action_name;
        }

        $path_cache[$app_name][$controller] = $controller_path;
        return $controller_path;
    }

    /**
     * 生成URL中的索引部分
     *
     * @param array $url_config
     * @param bool $have_controller
     * @return string
     */
    protected function makeIndex(array $url_config, bool $have_controller = false): string
    {
        static $cache = array();
        if (isset($cache[$have_controller])) {
            return $cache[$have_controller];
        }

        $index = $url_config['index'];
        $is_default_index = (0 === strcasecmp($index, 'index.php'));

        $index_dot = '/';
        $addition_dot = '';
        if ($url_config['rewrite']) {
            $index = $index_dot = $addition_dot = '';
        }

        $virtual_path = &$url_config['virtual_path'];
        if ($have_controller) {
            $index .= $index_dot . $addition_dot;
            if ($virtual_path) {
                $index .= $virtual_path . $url_config['dot'];
            }
        } else {
            if ($is_default_index) {
                $index = '';
            }

            if ($virtual_path) {
                $index .= $index_dot . $addition_dot . $virtual_path;
                if ($url_config['ext']) {
                    $index .= $url_config['ext'];
                }
            }
        }

        $cache[$have_controller] = $index;
        return $index;
    }

    /**
     * 生成uri参数字符串
     *
     * @param array $params 当url_type的值不为2时, 值必须是标量(bool型需要在外部转换为int型)
     * @param array $url_config
     * @param bool $encrypt_params
     * @param bool $add_prefix_dot 当控制器字符串为空时,参数不添加前缀
     * @return string
     */
    protected function makeParams(array $params, array $url_config, bool $encrypt_params = false, bool $add_prefix_dot = true): string
    {
        $url_dot = &$url_config['dot'];
        $params_dot = &$url_config['params_dot'];
        if ($params_dot) {
            $dot = $params_dot;
        } else {
            $dot = $url_dot;
        }

        $url_params = '';
        if ($params) {
            switch ($url_config['type']) {
                case 1:
                    $url_params = implode($dot, $params);
                    break;

                case 2:
                    foreach ($params as $p_key => $p_val) {
                        $url_params .= $p_key . $dot . $p_val . $dot;
                    }
                    $url_params = rtrim($url_params, $dot);
                    break;

                default:
                    $url_dot = '?';
                    $add_prefix_dot = true;
                    $url_params = http_build_query($params);
                    break;
            }

            if (true === $encrypt_params) {
                $url_params = $this->urlEncrypt($url_params);
            }
        }

        if ($add_prefix_dot) {
            return $url_dot . $url_params;
        }

        return $url_params;
    }

    /**
     * 输出js/css连接
     *
     * @param string $res_link
     * @param bool $make_link
     * @return null|string
     */
    protected function outputResLink(string $res_link, bool $make_link = true): string
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

        return '';
    }

    /**
     * 加载布局
     *
     * @param string $content
     * @param string $layer_ext
     * @throws CoreException
     */
    protected function loadLayer(string $content, string $layer_ext = '.layer.php'): void
    {
        $layer_file = $this->getTplPath() . $this->set['layer'] . $layer_ext;
        if (!is_file($layer_file)) {
            throw new CoreException($layer_file . ' layer Not found!');
        }

        extract($this->set, EXTR_PREFIX_SAME, 'USER_DEFINED');
        include $layer_file;
    }

    /**
     * 输出带layer的view
     *
     * @param mixed $data
     * @param string $method
     * @return string|void
     * @throws CoreException
     */
    private function obRenderAction($data, string $method): void
    {
        ob_start();
        $this->$method($data);
        $content = ob_get_clean();

        if ($this->set['load_layer']) {
            $this->loadLayer($content);
        } else {
            echo $content;
        }
    }

    /**
     * 解析路由别名配置
     *
     * @param string $app_name
     * @param array $router
     * @return array
     */
    private function parseControllerAlias(string $app_name, array $router): array
    {
        static $router_alias_cache;
        if (!isset($router_alias_cache[$app_name])) {
            $router_alias_cache[$app_name] = [];
            if (!empty($router)) {
                foreach ($router as $controller_alias => $alias_config) {
                    $router_alias_cache[$app_name][$alias_config] = $controller_alias;
                }
            }
        }

        return $router_alias_cache[$app_name];
    }

    /**
     * 生成radio或checkbox标签
     *
     * @param string $type 指定类型
     * @param array $data 数据 值和label的关联数组
     * @param array $default_value 默认值
     * @param array $input_tags input附加参数
     * @param array $label_tags label附加参数
     * @return string
     */
    private function buildRadioOrCheckbox(string $type, array $data, array $default_value = [], array $input_tags = [], array $label_tags = [])
    {
        $content = '';
        foreach ($data as $value => $label_text) {
            $build_input_tags = array();
            if (!empty($input_tags)) {
                $build_input_tags = $input_tags;
            }

            $build_input_tags['type'] = $type;
            $build_input_tags['value'] = $value;
            if (isset($default_value[$value])) {
                $build_input_tags['checked'] = true;
            }

            $label_tags['@content'] = self::htmlTag('input', $build_input_tags) . $label_text;
            $content .= self::htmlTag('label', $label_tags);
        }

        return $this->wrapContent($content, false);
    }

    /**
     * 处理wrap
     *
     * @param array $wrap_tags
     * @return mixed
     */
    private function buildWrapTags(array &$wrap_tags)
    {
        $i = 0;
        $wrap_content = '';
        $stack_size = count($wrap_tags) - 1;
        while ($wrap_config = array_shift($wrap_tags)) {
            list($wrap_element, $wrap_element_tags, $after) = $wrap_config;
            if ($wrap_content === '') {
                $wrap_content = HTML::$wrap_element($wrap_element_tags);
            } else {
                if (isset($wrap_element_tags['@content']) && $after) {
                    $wrap_element_tags['@content'] = $wrap_content . $wrap_element_tags['@content'];
                } else if (isset($wrap_element_tags['@content'])) {
                    $wrap_element_tags['@content'] .= $wrap_content;
                } else {
                    $wrap_element_tags['@content'] = $wrap_content;
                }

                if ($i == $stack_size) {
                    return self::htmlTag($wrap_element, $wrap_element_tags);
                } else {
                    $wrap_content = HTML::$wrap_element($wrap_element_tags);
                }
            }
            $i++;
        }

        return $wrap_content;
    }

    /**
     * 处理带wrap的HTML
     *
     * @param string $content
     * @param bool $encode 是否转码
     * @return mixed
     */
    private function wrapContent(string $content, bool $encode = true)
    {
        if (!empty($this->wrap_stack)) {
            $stack_top = &$this->wrap_stack[0];
            if (isset($stack_top[1]['@content']) && $stack_top[2]) {
                $content .= $stack_top[1]['@content'];
            } else if (isset($stack_top[1]['@content'])) {
                $content = $stack_top[1]['@content'] . $content;
            }

            $stack_top[1]['@content'] = $content;
            return $this->buildWrapTags($this->wrap_stack);
        } else if ($encode) {
            return htmlentities($content, ENT_IGNORE);
        } else {
            return $content;
        }
    }

    /**
     * 节点入栈并生成HTML
     *
     * @param string $element
     * @param array $element_tags
     * @return mixed|string
     */
    private function wrapTag(string $element, array $element_tags = [])
    {
        if (!empty($this->wrap_stack)) {
            $this->wrap($element, $element_tags);
            return $this->buildWrapTags($this->wrap_stack);
        }

        return self::htmlTag($element, $element_tags);
    }
}
