<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.0
 */
namespace Cross\Core;

use Cross\Exception\CoreException;
use Cross\Exception\FrontException;
use Cross\I\RouterInterface;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Router
 * @package Cross\Core
 */
class Router implements RouterInterface
{
    /**
     * url参数
     *
     * @var array
     */
    private $params;

    /**
     * Action名称
     *
     * @var string
     */
    private $action;

    /**
     * 控制器名称
     *
     * @var string
     */
    private $controller;

    /**
     * 默认action
     *
     * @var string
     */
    public static $default_action = 'index';

    /**
     * @var array;
     */
    private $router_params = array();

    /**
     * app配置
     *
     * @var Config
     */
    private static $config;

    /**
     * router实例
     *
     * @var Router
     */
    private static $instance;

    private function __construct(& $_config)
    {
        self::$config = $_config;
    }

    /**
     * 创建一个Router的实例
     *
     * @param Config $_config
     * @return Router
     */
    static function initialization(Config & $_config)
    {
        if (!self::$instance) {
            self::$instance = new Router($_config);
        }

        return self::$instance;
    }

    /**
     * 设置url解析参数
     *
     * @param $params
     * @return $this
     */
    public function set_router_params($params = null)
    {
        if (null === $params) {
            $this->router_params = $this->initRequestParams();
        } else {
            $this->router_params = $params;
        }

        return $this;
    }

    /**
     * 要解析的请求string
     *
     * @return array
     */
    public function get_router_params()
    {
        return $this->router_params;
    }

    /**
     * 初始化参数 按类型返回要解析的url字符串
     *
     * @return array|string
     * @throws \cross\exception\CoreException
     */
    function initRequestParams()
    {
        $url_config = self::$config->get('url');
        switch ($url_config ['type']) {
            case 1:
            case 3:
                $request = Request::getInstance()->getUrlRequest(1, $url_config['rewrite']);
                return self::parseString(htmlspecialchars(urldecode($request), ENT_QUOTES), $url_config, true);

            case 2:
            case 4:
                $path_info = Request::getInstance()->getUrlRequest(2, $url_config['rewrite']);
                $request = self::parseString(htmlspecialchars(urldecode($path_info), ENT_QUOTES), $url_config);
                if (!empty($request)) {
                    return array_merge($request, $_REQUEST);
                }
                return $request;

            default:
                throw new CoreException('不支持的 url type');
        }
    }

    /**
     * 解析请求字符串
     * <pre>
     * [0] 解析的结果
     * [1] 抛出异常
     * [2] 返回空字符串
     * </pre>
     *
     * @param string $_query_string
     * @param array $url_config
     * @param bool $parse_mixed_params
     * @return array
     * @throws FrontException
     */
    static function parseString($_query_string, $url_config, $parse_mixed_params = false)
    {
        if (true === $parse_mixed_params && false !== strpos($_query_string, '&')) {
            $_query_string_array = explode('&', $_query_string);
            $_query_string = array_shift($_query_string_array);
        }

        $_query_string = trim(ltrim($_query_string, '/'), $url_config['dot']);
        $router_params = array();
        if (!$_query_string) {
            return $router_params;
        }

        $_url_ext = $url_config['ext'];
        if ($_query_string != '' && isset($_url_ext[0]) && ($_url_ext_len = strlen(trim($_url_ext))) > 0) {
            if (0 === strcasecmp($_url_ext, substr($_query_string, -$_url_ext_len))) {
                $_query_string = substr($_query_string, 0, -$_url_ext_len);
            } else {
                throw new FrontException('Page not found !');
            }
        }

        if (false !== strpos($_query_string, $url_config['dot'])) {
            $router_params = explode($url_config['dot'], $_query_string);
        } else {
            $router_params = array($_query_string);
        }

        return $router_params;
    }

    /**
     * 默认控制器和方法 init['url']['*']
     *
     * @param $init_default
     * @return array
     * @throws CoreException
     */
    private function getDefaultRouter($init_default)
    {
        if ($init_default) {
            list($_defController, $_defAction) = explode(':', $init_default);
            $_defaultRouter = array();

            if (isset($_defController)) {
                $_defaultRouter['controller'] = $_defController;
            } else {
                throw new CoreException('please define the default controller in the APP_PATH/APP_NAME/init.php file!');
            }

            if (isset($_defAction)) {
                $_defaultRouter['action'] = $_defAction;
            } else {
                throw new CoreException('please define the default action in the APP_PATH/APP_NAME/init.php file!');
            }

            $_defaultRouter['params'] = $_REQUEST;

            return $_defaultRouter;
        } else {
            throw new CoreException('undefined default router!');
        }
    }

    /**
     * 设置router
     *
     * @return $this
     * @throws FrontException
     */
    public function getRouter()
    {
        $_router = $this->get_router_params();
        if (empty($_router)) {
            $_defaultRouter = $this->getDefaultRouter(self::$config->get('url', '*'));

            $this->setController($_defaultRouter['controller']);
            $this->setAction($_defaultRouter['action']);
            $this->setParams($_defaultRouter['params']);
        } else {
            $this->setRouter($_router);
        }

        return $this;
    }

    /**
     * 解析router别名配置
     *
     * @param array $request
     * @throws CoreException
     * @internal param $router
     */
    function setRouter($request)
    {
        $router_config = self::$config->get('router');
        $_controller = array_shift($request);
        self::$config->set('url', array('ori_controller' => $_controller));

        $combine_alias_key = '';
        if (isset($request[0])) {
            $combine_alias_key = $_controller . ':' . $request[0];
        }

        $controller_alias = array();
        if (isset($router_config [$combine_alias_key])) {
            array_shift($request);
            $controller_alias = $router_config [$combine_alias_key];
        } elseif (isset($router_config [$_controller])) {
            $controller_alias = $router_config [$_controller];
        }

        if (! empty($controller_alias)) {
            if (is_array($controller_alias)) {
                list($alias_controller, $alias_addition_params) = $controller_alias;
                if (strpos($alias_controller, ':') !== false) {
                    list($_controller, $_action) = explode(':', $alias_controller);
                } else {
                    $_controller = $alias_controller;
                }

                self::$config->set('url', array(
                    'router_addition_params' => $alias_addition_params,
                ));

                if (!isset($_action)) {
                    if (isset($request[0])) {
                        $_action = array_shift($request);
                        self::$config->set('url', array('ori_action' => $_action));
                    } else {
                        $_action = self::$default_action;
                    }
                }
            } else {
                if (false !== strpos($controller_alias, ':')) {
                    list($_controller, $_action) = explode(':', $controller_alias);
                } else {
                    $_controller = $controller_alias;

                    if (isset($request [0])) {
                        $_action = array_shift($request);
                    } else {
                        $_action = self::$default_action;
                    }
                }
            }
        } else {
            if (isset($request[0])) {
                $_action = array_shift($request);
                self::$config->set('url', array('ori_action' => $_action));
            } else {
                $_action = self::$default_action;
            }
        }

        if (isset($alias_params) && !empty($alias_params)) {
            $_params = array_merge($request, $alias_params);
        } else {
            $_params = $request;
        }

        $this->setController($_controller);
        $this->setAction($_action);
        $this->setParams($_params);
    }

    /**
     * 设置controller
     *
     * @param $controller
     */
    private function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * 设置Action
     *
     * @param $action
     */
    private function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * 设置参数
     *
     * @param $params
     */
    private function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 返回控制器名称
     *
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * 返回action名称
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 返回参数
     *
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }
}

