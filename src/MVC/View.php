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
    private $tplDir;

    /**
     * 资源配置
     *
     * @var array
     */
    private $resList;

    /**
     * 默认模板路径
     *
     * @var string
     */
    private $tplBasePath;

    /**
     * 默认url
     *
     * @var string
     */
    private $linkBase = null;

    /**
     * @var array
     */
    private $wrapStack = [];

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
    protected $tplFileSuffix = '.tpl.php';

    /**
     * url配置缓存
     *
     * @var array
     */
    protected static $urlConfigCache = [];

    /**
     * 渲染模板
     *
     * @param null $data
     * @param string|null $method
     * @throws CoreException
     */
    function display($data = null, string $method = null): void
    {
        $this->data = &$data;
        $contentType = $this->config->get('sys', 'content_type');
        $this->delegate->getResponse()->setContentType($contentType ?: 'html');

        if ($method === null) {
            $method = $this->action ?: Router::DEFAULT_ACTION;
        }

        ob_start();
        $this->obRenderAction($data, $method);
        $this->delegate->getResponse()->setContent(ob_get_clean());
    }

    /**
     * 生成url
     *
     * @param string|null $controller
     * @param null|string|array $params
     * @param bool $encryptParams
     * @return string
     * @throws CoreException
     */
    function url(string $controller = null, $params = null, bool $encryptParams = false): string
    {
        static $linkBase = null;
        if ($linkBase === null) {
            $linkBase = $this->getLinkBase();
        }

        if ($controller === null && $params === null) {
            return $linkBase;
        } else {
            $uri = $this->makeUri($this->getAppName(), false, $controller, $params, $encryptParams);
            return $linkBase . $uri;
        }
    }

    /**
     * 生成参数加密的超链接
     *
     * @param string|null $controller
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
     * @param array $elementTags
     * @return mixed|string
     */
    function a(string $content, string $href = '', array $elementTags = [])
    {
        $elementTags['href'] = $href;
        $elementTags['@content'] = $content;
        return $this->wrapTag('a', $elementTags);
    }

    /**
     * 输出img标签
     *
     * @param string $src
     * @param array $elementTags
     * @return mixed|string
     */
    function img(string $src, array $elementTags = [])
    {
        $elementTags['src'] = $src;
        $elementTags += ['border' => 0, 'alt' => 'image'];
        return $this->wrapTag('img', $elementTags);
    }

    /**
     * input标签
     *
     * @param string $type
     * @param array $elementTags
     * @return mixed|string
     */
    function input(string $type, array $elementTags = [])
    {
        $elementTags['type'] = $type;
        return $this->wrapTag('input', $elementTags);
    }

    /**
     * 单选
     *
     * @param array $data
     * @param string $defaultValue
     * @param array $radioTags
     * @param array $labelTags
     * @return string
     * @see View::buildRadioOrCheckbox
     */
    function radio(array $data, $defaultValue = '', array $radioTags = [], array $labelTags = [])
    {
        $defaultValue = [$defaultValue => true];
        return $this->buildRadioOrCheckbox('radio', $data, $defaultValue, $radioTags, $labelTags);
    }

    /**
     * 多选
     *
     * @param array $data
     * @param string|array $defaultValue
     * @param array $checkboxTags
     * @param array $labelTags
     * @return string
     * @see View::buildRadioOrCheckbox
     */
    function checkbox(array $data, $defaultValue = '', array $checkboxTags = [], array $labelTags = [])
    {
        if (is_array($defaultValue)) {
            $defaultValue = array_flip($defaultValue);
        } else {
            $defaultValue = [$defaultValue => true];
        }

        return $this->buildRadioOrCheckbox('checkbox', $data, $defaultValue, $checkboxTags, $labelTags);
    }

    /**
     * 输出select
     *
     * @param array $optionsData 二维数组时, 生成optgroup
     * @param mixed $defaultValue
     * @param array $selectParams
     * @param array $userOptionParams
     * @return mixed
     */
    function select(array $optionsData, $defaultValue = null, array $selectParams = [], array $userOptionParams = [])
    {
        $content = '';
        if (is_array($defaultValue)) {
            $defaultValue = array_flip($defaultValue);
        } else {
            $defaultValue = [$defaultValue => true];
        }

        foreach ($optionsData as $value => $option) {
            $optionParams = [];
            if (!empty($userOptionParams)) {
                $optionParams = $userOptionParams;
            }

            if (is_array($option)) {
                $optContent = '';
                foreach ($option as $optValue => $optOption) {
                    unset($optionParams['selected']);
                    $optionParams['value'] = $optValue;
                    $optionParams['@content'] = $optOption;
                    if (isset($defaultValue[$optValue])) {
                        $optionParams['selected'] = true;
                    }

                    $optContent .= self::htmlTag('option', $optionParams);
                }

                $optParams['label'] = $value;
                $optParams['@content'] = $optContent;
                $content .= self::htmlTag('optgroup', $optParams);
            } else {
                $optionParams['value'] = $value;
                $optionParams['@content'] = $option;
                if (isset($defaultValue[$value])) {
                    $optionParams['selected'] = true;
                }

                $content .= self::htmlTag('option', $optionParams);
            }
        }

        $selectParams['@content'] = $content;
        return $this->wrapContent(self::htmlTag('select', $selectParams), false);
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
     * @param array $elementTags
     * @return string
     */
    static function htmlTag(string $element, array $elementTags = [])
    {
        return HTML::$element($elementTags);
    }

    /**
     * HTML标签入栈
     *
     * @param string $element
     * @param string|array $elementTags
     * @param bool $contentRear 自身内容是否放在被包裹内容之后
     * @return $this
     */
    function wrap(string $element, $elementTags = [], bool $contentRear = false): self
    {
        if (!is_array($elementTags)) {
            $elementTags = ['@content' => $elementTags];
        }

        array_unshift($this->wrapStack, array($element, $elementTags, $contentRear));
        return $this;
    }

    /**
     * 带wrap的块级元素
     *
     * @param string $content 内容
     * @param array $elementTags
     * @param string $element
     * @return string
     */
    function block(string $content, array $elementTags = [], string $element = 'div')
    {
        $elementTags['@content'] = $content;
        return $this->wrapTag($element, $elementTags);
    }

    /**
     * 处理带模板的block元素
     *
     * @param string $tplName 模板名称
     * @param array $tplData 模板中的数据
     * @param array $elementTags
     * @param string $element
     * @return string
     */
    function section(string $tplName, array $tplData = [], array $elementTags = [], string $element = 'div')
    {
        return $this->block($this->obRenderTpl($tplName, $tplData), $elementTags, $element);
    }

    /**
     * 生成表单
     * <pre>
     * 使用$this->on('buildForm', ....), 来干预所有生成的表单内容
     * </pre>
     *
     * @param string $tplName 包含表单的模板文件路径
     * @param array $formTags 生成表单tag的参数
     * @param array $tplData 模板数据
     * @return string
     */
    function buildForm(string $tplName, array $formTags = [], array $tplData = [])
    {
        $content = $this->delegate->getClosureContainer()->run('buildForm', [$this]);
        $content .= $this->obRenderTpl($tplName, $tplData);

        $formTags += ['action' => '', 'method' => 'post'];
        $formTags['@content'] = $content;

        return $this->wrapTag('form', $formTags);
    }

    /**
     * 加载指定名称的模板文件
     *
     * @param string $tplName
     * @param array $data
     */
    function renderTpl(string $tplName, array $data = []): void
    {
        include $this->tpl($tplName);
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
     * @param string $tplName
     * @param array $data
     * @param bool $encode
     * @return string
     */
    function obRenderTpl(string $tplName, array $data = [], bool $encode = false)
    {
        ob_start();
        $this->renderTpl($tplName, $data);
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
     * @param string $tplName
     * @param bool $getContent 是否读取模板内容
     * @param bool $autoAppendSuffix 是否自动添加模版后缀
     * @return string
     */
    function tpl(string $tplName, bool $getContent = false, bool $autoAppendSuffix = true): string
    {
        $filePath = $this->getTplPath() . $tplName;
        if ($autoAppendSuffix) {
            $filePath .= $this->tplFileSuffix;
        }

        if (true === $getContent) {
            return file_get_contents($filePath, true);
        }

        return $filePath;
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
     * @param string $rootName
     * @throws Exception
     */
    function XML(array $data, string $rootName = 'root'): void
    {
        $this->set['load_layer'] = false;
        $this->delegate->getResponse()->setContentType('xml');
        echo Array2XML::createXML($rootName, $data)->saveXML();
    }

    /**
     * 安全的输出数组中的值
     *
     * @param array $data
     * @param string|int $key
     * @param string $defaultValue
     * @return string
     */
    function e(array $data, $key, $defaultValue = '')
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return $defaultValue;
    }

    /**
     * 判断数组中的值是否为empty,否则返回默认值
     *
     * @param array $data
     * @param string|int $key
     * @param string $defaultValue
     * @return string
     * @see e
     */
    function ee(array $data, $key, $defaultValue = '')
    {
        if (!empty($data[$key])) {
            return $data[$key];
        }

        return $defaultValue;
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
     * @param string $resUrl
     * @param bool $useStaticUrl
     * @return string
     */
    function res(string $resUrl, bool $useStaticUrl = true): string
    {
        static $resBaseUrl = null;
        if (!isset($resBaseUrl[$useStaticUrl])) {
            if ($useStaticUrl) {
                $baseUrl = $this->getConfig()->get('static', 'url');
            } else {
                $baseUrl = $this->getConfig()->get('url', 'full_request');
            }

            $resBaseUrl[$useStaticUrl] = rtrim($baseUrl, '/') . '/';
        }

        return $resBaseUrl[$useStaticUrl] . $resUrl;
    }

    /**
     * 输出资源相对路径
     *
     * @param string $resUrl 资源路径
     * @param string $resDir 资源文件夹名称
     * @return string
     */
    function relRes(string $resUrl, string $resDir = 'static'): string
    {
        static $resBaseUrl = null;
        if (null === $resBaseUrl) {
            $resBaseUrl = rtrim($this->getConfig()->get('url', 'request'), '/') . '/' . $resDir . '/';
        }

        return $resBaseUrl . $resUrl;
    }

    /**
     * 生成指定app,指定控制器的url
     *
     * @param string $baseLink
     * @param string $appName
     * @param null|string $controller
     * @param null|string|array $params
     * @param null|bool $encryptParams
     * @return string
     * @throws CoreException
     */
    function appUrl(string $baseLink, string $appName, $controller = null, $params = null, bool $encryptParams = false): string
    {
        $baseLink = rtrim($baseLink, '/') . '/';
        if ($controller === null && $params === null) {
            return $baseLink;
        } else {
            $uri = $this->makeUri($appName, true, $controller, $params, $encryptParams);
            return $baseLink . $uri;
        }
    }

    /**
     * 清除link中使用到的缓存(config->url配置在运行过程中发生变动时先清除缓存)
     */
    function cleanLinkCache(): void
    {
        unset(self::$urlConfigCache[$this->getAppName()]);
    }

    /**
     * 设置模板dir
     *
     * @param string $dirName
     */
    function setTplDir(string $dirName): void
    {
        $this->tplDir = $dirName;
    }

    /**
     * 获取生成连接的基础路径
     *
     * @return string
     */
    function getLinkBase(): string
    {
        if (null === $this->linkBase) {
            $this->setLinkBase($this->getConfig()->get('url', 'full_request'));
        }

        return $this->linkBase;
    }

    /**
     * 获取当前app名称
     *
     * @return string
     */
    function getAppName(): string
    {
        static $appName = null;
        if ($appName === null) {
            $appName = $this->getConfig()->get('app', 'name');
        }

        return $appName;
    }

    /**
     * 设置生成的连接基础路径
     *
     * @param string $linkBase
     */
    function setLinkBase(string $linkBase): void
    {
        $this->linkBase = rtrim($linkBase, '/') . '/';
    }

    /**
     * 模板路径
     *
     * @return string 要加载的模板路径
     */
    function getTplPath(): string
    {
        static $tplPath;
        $appName = $this->getAppName();
        if (!isset($tplPath[$appName])) {
            $tplPath[$appName] = $this->getTplBasePath() . $this->getTplDir() . DIRECTORY_SEPARATOR;
        }

        return $tplPath[$appName];
    }

    /**
     * 设置模板路径
     *
     * @param string $tplBasePath
     */
    function setTplBasePath(string $tplBasePath): void
    {
        $this->tplBasePath = rtrim($tplBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取模板默认路径
     *
     * @return string
     */
    function getTplBasePath(): string
    {
        if (!$this->tplBasePath) {
            $this->setTplBasePath($this->getConfig()->get('app', 'path') . 'templates' . DIRECTORY_SEPARATOR);
        }

        return $this->tplBasePath;
    }

    /**
     * 取得模板路径前缀
     *
     * @return string
     */
    function getTplDir(): string
    {
        if (!$this->tplDir) {
            $defaultTplDir = $this->getConfig()->get('sys', 'default_tpl_dir');
            if (!$defaultTplDir) {
                $defaultTplDir = 'default';
            }
            $this->setTplDir($defaultTplDir);
        }

        return $this->tplDir;
    }

    /**
     * 运行时分组添加css/js
     *
     * @param string $resUrl
     * @param string $location
     * @param bool $convert
     */
    function addRes(string $resUrl, string $location = 'header', bool $convert = true): void
    {
        $this->resList[$location][] = [
            'url' => $resUrl,
            'convert' => $convert
        ];
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
        if (empty($this->resList) || empty($this->resList[$location])) {
            return $result;
        }

        if (isset($this->resList[$location]) && !empty($this->resList[$location])) {
            $data = $this->resList[$location];
        }

        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $r) {
                    $result .= $this->outputResLink($r['url'], $r['convert']) . "\n";
                }
            } else {
                $result .= $this->outputResLink($data) . "\n";
            }
        }

        return $result;
    }

    /**
     * 生成连接
     *
     * @param string $appName
     * @param bool $checkAppName
     * @param null|string $controller
     * @param null|array $params
     * @param bool $encryptParams
     * @return string
     * @throws CoreException
     */
    protected function makeUri(string $appName, bool $checkAppName, $controller = null, $params = null, bool $encryptParams = false): string
    {
        $uri = '';
        $enableControllerCache = false;
        //在运行过程中,如果url的配置有变化,需要调用cleanLinkCache()来刷新缓存
        if (!isset(self::$urlConfigCache[$appName])) {
            $thisAppName = $appName;
            if ($checkAppName) {
                $thisAppName = $this->getAppName();
            }

            if ($checkAppName && $appName != $thisAppName) {
                $appPath = PROJECT_REAL_PATH . str_replace('/', DIRECTORY_SEPARATOR, $appName);
                $config = Config::load($appPath . DIRECTORY_SEPARATOR . 'init.php');
            } else {
                $config = $this->config;
            }

            $urlRouterConfig = array(
                'url' => $config->get('url'),
                'router' => $config->get('router')
            );
            self::$urlConfigCache[$appName] = $urlRouterConfig;
        } else {
            $enableControllerCache = true;
            $urlRouterConfig = self::$urlConfigCache[$appName];
        }

        $urlParams = '';
        $hasControllerString = true;
        $urlController = $this->makeControllerUri($appName, $enableControllerCache, $controller, $urlRouterConfig);
        if ($urlController === '') {
            $hasControllerString = false;
        }

        $urlConfig = &$urlRouterConfig['url'];
        if (!empty($params)) {
            $urlParams = $this->makeParams($params, $urlConfig, $encryptParams, $hasControllerString);
        }

        if (!empty($urlConfig['ext'])) {
            if ($urlConfig['type'] > 2) {
                if ($hasControllerString) {
                    $uri .= $urlController . $urlConfig['ext'];
                }

                $uri .= $urlParams;
            } else {
                $uri .= $urlController . $urlParams . $urlConfig['ext'];
            }
        } else {
            $uri .= $urlController . $urlParams;
        }

        return $uri;
    }

    /**
     * 生成控制器连接
     *
     * @param string $appName
     * @param bool $useCache 是否使用缓存
     * @param string $controller
     * @param array $urlConfig
     * @return string
     */
    protected function makeControllerUri(string $appName, bool $useCache, string $controller, array $urlConfig): string
    {
        static $pathCache;
        if (isset($pathCache[$appName][$controller]) && $useCache) {
            return $pathCache[$appName][$controller];
        }

        $appAliasConfig = $this->parseControllerAlias($appName, $urlConfig['router']);
        if (isset($appAliasConfig[$controller])) {
            $realController = $appAliasConfig[$controller];
        } else {
            $realController = $controller;
        }

        $actionName = null;
        if (false !== strpos($realController, ':')) {
            list($controllerName, $actionName) = explode(':', $realController);
        } else {
            $controllerName = $realController;
        }

        $url = &$urlConfig['url'];
        $index = $this->makeIndex($url, true);
        $controllerPath = $index . $controllerName;

        if (null !== $actionName) {
            $controllerPath .= $url['dot'] . $actionName;
        }

        $pathCache[$appName][$controller] = $controllerPath;
        return $controllerPath;
    }

    /**
     * 生成URL中的索引部分
     *
     * @param array $urlConfig
     * @param bool $haveController
     * @return string
     */
    protected function makeIndex(array $urlConfig, bool $haveController = false): string
    {
        static $cache = [];
        if (isset($cache[$haveController])) {
            return $cache[$haveController];
        }

        $index = $urlConfig['index'];
        $isDefaultIndex = (0 === strcasecmp($index, 'index.php'));

        $indexDot = '/';
        $additionDot = '';
        if ($urlConfig['rewrite']) {
            $index = $indexDot = $additionDot = '';
        }

        $virtualPath = &$urlConfig['virtual_path'];
        if ($haveController) {
            $index .= $indexDot . $additionDot;
            if ($virtualPath) {
                $index .= $virtualPath . $urlConfig['dot'];
            }
        } else {
            if ($isDefaultIndex) {
                $index = '';
            }

            if ($virtualPath) {
                $index .= $indexDot . $additionDot . $virtualPath;
                if ($urlConfig['ext']) {
                    $index .= $urlConfig['ext'];
                }
            }
        }

        $cache[$haveController] = $index;
        return $index;
    }

    /**
     * 生成uri参数字符串
     *
     * @param array $params 当url_type的值不为2时, 值必须是标量(bool型需要在外部转换为int型)
     * @param array $urlConfig
     * @param bool $encryptParams
     * @param bool $addPrefixDot 当控制器字符串为空时,参数不添加前缀
     * @return string
     */
    protected function makeParams(array $params, array $urlConfig, bool $encryptParams = false, bool $addPrefixDot = true): string
    {
        $urlDot = &$urlConfig['dot'];
        $paramsDot = &$urlConfig['params_dot'];
        if ($paramsDot) {
            $dot = $paramsDot;
        } else {
            $dot = $urlDot;
        }

        $urlParams = '';
        if ($params) {
            switch ($urlConfig['type']) {
                case 1:
                    $urlParams = implode($dot, $params);
                    break;

                case 2:
                    foreach ($params as $pKey => $pVal) {
                        $urlParams .= $pKey . $dot . $pVal . $dot;
                    }
                    $urlParams = rtrim($urlParams, $dot);
                    break;

                default:
                    $urlDot = '?';
                    $addPrefixDot = true;
                    $urlParams = http_build_query($params);
                    break;
            }

            if (true === $encryptParams) {
                $urlParams = $this->urlEncrypt($urlParams);
            }
        }

        if ($addPrefixDot) {
            return $urlDot . $urlParams;
        }

        return $urlParams;
    }

    /**
     * 输出js/css连接
     *
     * @param string $resLink
     * @param bool $makeLink
     * @return null|string
     */
    protected function outputResLink(string $resLink, bool $makeLink = true): string
    {
        $resType = strtolower(Helper::getExt($resLink));
        if ($makeLink) {
            $resLink = $this->res($resLink);
        }

        if ($resType == 'js') {
            return self::htmlTag('script', ['type' => 'text/javascript', 'src' => $resLink]);
        } elseif ($resType == 'css') {
            return self::htmlTag('link', ['rel' => 'stylesheet', 'href' => $resLink]);
        }

        return '';
    }

    /**
     * 加载布局
     *
     * @param string $content
     * @param string $layerExt
     * @throws CoreException
     */
    protected function loadLayer(string $content, string $layerExt = '.layer.php'): void
    {
        $layerFile = $this->getTplPath() . $this->set['layer'] . $layerExt;
        if (!is_file($layerFile)) {
            throw new CoreException($layerFile . ' layer Not found!');
        }

        extract($this->set, EXTR_PREFIX_SAME, 'USER_DEFINED');
        include $layerFile;
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
     * @param string $appName
     * @param array $router
     * @return array
     */
    private function parseControllerAlias(string $appName, array $router): array
    {
        static $routerAliasCache;
        if (!isset($routerAliasCache[$appName])) {
            $routerAliasCache[$appName] = [];
            if (!empty($router)) {
                foreach ($router as $controllerAlias => $aliasConfig) {
                    $routerAliasCache[$appName][$aliasConfig] = $controllerAlias;
                }
            }
        }

        return $routerAliasCache[$appName];
    }

    /**
     * 生成radio或checkbox标签
     *
     * @param string $type 指定类型
     * @param array $data 数据 值和label的关联数组
     * @param array $defaultValue 默认值
     * @param array $inputTags input附加参数
     * @param array $labelTags label附加参数
     * @return string
     */
    private function buildRadioOrCheckbox(string $type, array $data, array $defaultValue = [], array $inputTags = [], array $labelTags = [])
    {
        $content = '';
        foreach ($data as $value => $labelText) {
            $buildInputTags = [];
            if (!empty($inputTags)) {
                $buildInputTags = $inputTags;
            }

            $buildInputTags['type'] = $type;
            $buildInputTags['value'] = $value;
            if (isset($defaultValue[$value])) {
                $buildInputTags['checked'] = true;
            }

            $labelTags['@content'] = self::htmlTag('input', $buildInputTags) . $labelText;
            $content .= self::htmlTag('label', $labelTags);
        }

        return $this->wrapContent($content, false);
    }

    /**
     * 处理wrap
     *
     * @param array $wrapTags
     * @return mixed
     */
    private function buildWrapTags(array &$wrapTags)
    {
        $i = 0;
        $content = '';
        $stackSize = count($wrapTags) - 1;
        while ($wrapConfig = array_shift($wrapTags)) {
            list($element, $tags, $after) = $wrapConfig;
            if ($content === '') {
                $content = HTML::$element($tags);
            } else {
                if (isset($tags['@content']) && $after) {
                    $tags['@content'] = $content . $tags['@content'];
                } else if (isset($tags['@content'])) {
                    $tags['@content'] .= $content;
                } else {
                    $tags['@content'] = $content;
                }

                if ($i == $stackSize) {
                    return self::htmlTag($element, $tags);
                } else {
                    $content = HTML::$element($tags);
                }
            }
            $i++;
        }

        return $content;
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
        if (!empty($this->wrapStack)) {
            $stackTop = &$this->wrapStack[0];
            if (isset($stackTop[1]['@content']) && $stackTop[2]) {
                $content .= $stackTop[1]['@content'];
            } else if (isset($stackTop[1]['@content'])) {
                $content = $stackTop[1]['@content'] . $content;
            }

            $stackTop[1]['@content'] = $content;
            return $this->buildWrapTags($this->wrapStack);
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
     * @param array $elementTags
     * @return mixed|string
     */
    private function wrapTag(string $element, array $elementTags = [])
    {
        if (!empty($this->wrapStack)) {
            $this->wrap($element, $elementTags);
            return $this->buildWrapTags($this->wrapStack);
        }

        return self::htmlTag($element, $elementTags);
    }
}
