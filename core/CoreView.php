<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class CoreView
 */
class CoreView extends FrameBase
{
    /**
     * 模板数据
     *
     * @var array
     */
    protected $data;

    /**
     * layer字面量
     *
     * @var array
     */
    protected $set;

    /**
     * url配置
     *
     * @var array
     */
    protected $urlconfig;

    /**
     * 资源配置
     *
     * @var array
     */
    protected $res_list;

    /**
     * 默认连接
     *
     * @var string
     */
    protected $link_base;

    /**
     * 静态资源路径
     *
     * @var string
     */
    protected $static_url;

    /**
     * 初始化视图
     */
    function __construct( )
    {
        parent::__construct();

        $this->urlconfig  = $this->config->get("url");
        $this->link_base  = $this->config->get("sys", "base_url");
        $this->static_url = $this->config->get("sys", "static_url");

        $this->set("static_url", $this->static_url);
    }

    /**
     * 模板的绝对路径
     *
     * @param $tpl_name
     * @param bool $get_content 是否读取模板内容
     * @param string $file_ext_name 模板文件扩展名
     * @return string
     */
    function tpl($tpl_name, $get_content=false, $file_ext_name='.tpl.php')
    {
        $file_path = $this->getTplPath().$tpl_name.$file_ext_name;

        if(true === $get_content)
        {
            return file_get_contents($file_path, true);
        }

        return $file_path;
    }

    /**
     * 设置layer附加参数
     *
     * @param $name
     * @param null $value
     */
    final function set($name, $value=null)
    {
        if(is_array($name)) {
            $this->set = array_merge($this->set, $name);
        } else {
            $this->set[$name] = $value;
        }
    }

    /**
     * 生成资源文件路径
     *
     * @param $res_url
     * @return string
     */
    function res($res_url)
    {
        if (defined('RES_BASE_URL'))
        {
            $res_base_url = RES_BASE_URL;
        } else {
            $res_base_url = $this->static_url;
        }

        return rtrim($res_base_url, "/").'/'.$res_url;
    }

    /**
     * 模板路径
     *
     * @return string 要加载的模板路径
     */
    function getTplPath()
    {
        return $this->config->get("sys", "app_path").DS.'templates'.DS.$this->getTplPathByType().DS;
    }

    /**
     * 生成连接
     *
     * @param null $controller 控制器:方法
     * @param null $params
     * @param bool $sec
     * @return string
     */
    function link($controller=null, $params=null, $sec = false)
    {
        $_link_url = $this->link_base;

        $_action = '';
        $_controller = '';

        if ($controller)
        {
            $_link_url .= $this->makeController($controller, $_controller, $_action);
        }

        if ($params != null)
        {
            $_link_url .= $this->makeParams($params, $_controller, $_action, $sec);
        }

        if ($controller && $this->urlconfig['type'] == 1 && $this->urlconfig['ext'])
        {
            $_link_url .= $this->urlconfig['ext'];
        }

        return $_link_url;
    }

    /**
     * 生成参数加密的连接
     *
     * @param null $_controller
     * @param null $params
     * @return string
     */
    function slink($_controller=null, $params=null)
    {
        return $this->link($_controller, $params, true);
    }

    /**
     * 生成控制器连接
     *
     * @param $controller
     * @param string $r_controller
     * @param string $r_action
     * @return string
     */
    private function makeController($controller, & $r_controller = '', & $r_action = '')
    {
        $_ext = '';
        $_link_url = '/';
        if (false !== strpos($controller, ":")) {
            list($_controller, $_action) = explode(":", $controller);
        } else {
            $_controller = $controller;
        }

        $r_controller = $_controller;
        if (isset($_action))
        {
            $r_action = $_action;
        }

        if ($this->urlconfig ['rewrite']) {
            $_link_url .= $_controller;
        }
        else
        {
            $index = $this->urlconfig ['index'];
            if ( $this->urlconfig ['type'] == 2 ) {
                $_dot = $index;
                $_ext = $this->urlconfig['ext'];
            } else {
                if ($index == 'index.php') {
                    $_dot = '?';
                } else {
                    $_dot = $index.'?';
                }
            }
            $_link_url .=  $_dot.'/'.$_controller;
        }

        if (isset($_action)) {
            $_link_url .= $this->urlconfig['dot'].$_action;
        }

        return $_link_url.$_ext;
    }

    /**
     * 生成link参数
     *
     * @param $params
     * @param string $_controller
     * @param string $_action
     * @param bool $sec
     * @return string
     */
    private function makeParams($params, $_controller='', $_action='', $sec = false)
    {
        $_params = '';
        $_dot = $this->urlconfig['dot'];

        if ($params)
        {
            if ($this->urlconfig ["type"] == 1)
            {
                if (is_array($params)) {
                    $_params = implode($this->urlconfig['dot'], $params);
                } else {
                    $url_str = array();
                    parse_str($params, $url_str);
                    $_params = implode($this->urlconfig['dot'], $url_str);
                }

            } else {
                $_dot = '?';
                if (is_array($params)) {
                    $_params = http_build_query($params);
                } else {
                    $_params = $params;
                }
            }

            if (true === $sec) {
                $_params = $this->encode_params($_params, "crossphp");
            }
        }

        return $_dot.$_params;
    }

    /**
     * 渲染模板
     *
     * @param null $data
     * @param null $method
     */
    function display(  $data = null, $method = null )
    {
        $this->data = $data;
        $display_type = $this->config->get("sys", "display");

        if ($method === null)
        {
            if($display_type && $display_type != "HTML")
            {
                $method = strtoupper( $display_type );
            }
            else
            {
                $method = $this->action;
            }
        }

        if(! $method)
        {
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
    function obRender( $data, $method )
    {
        ob_start();
        $this->$method( $data );
        $this->loadLayer( ob_get_clean() );
    }

    /**
     * 取得模板路径前缀
     *
     * @return string
     */
    function getTplPathByType()
    {
        /*
        if($this->is_robot()) {
            return 'spider';
        }
        else

        if ($this->is_mobile()) {
            return 'mobile';
        }
        */

        $tpl_path = 'web';
        if( $this->config->get("sys", "default_tpl") )
        {
            $tpl_path = $this->config->get("sys", "default_tpl");
        }

        return $tpl_path;
    }

    /**
     * 判断是否是蜘蛛
     *
     * @return bool
     */
    function is_robot()
    {
        $agent= strtolower($_SERVER['HTTP_USER_AGENT']);
        if (!empty($agent))
        {
            $spiderSite= array( "TencentTraveler", "Baiduspider+", "BaiduGame", "Googlebot",
                "msnbot", "Sosospider+", "Sogou web spider", "ia_archiver", "Yahoo! Slurp",
                "YoudaoBot", "Yahoo Slurp", "MSNBot", "Java (Often spam bot)","BaiDuSpider", "Voila",
                "Yandex bot", "BSpider", "twiceler", "Sogou Spider","Speedy Spider","Google AdSense",
                "Heritrix", "Python-urllib", "Alexa (IA Archiver)", "Ask", "Exabot", "Custo",
                "OutfoxBot/YodaoBot", "yacy", "SurveyBot", "legs", "lwp-trivial", "Nutch", "StackRambler",
                "The web archive (IA Archiver)", "Perl tool", "MJ12bot", "Netcraft", "MSIECrawler",
                "WGet tools", "larbin", "Fish search");

            foreach($spiderSite as $val)
            {
                if (stripos($agent, $val) !== false) {
                    return $val;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * 判断是否是移动设备
     *
     * @return bool
     */
    function is_mobile()
    {
        return stristr($_SERVER['HTTP_VIA'],"wap") ? true : false;
    }

    /**
     * 添加资源 css|js
     * @param $res_url
     * @param string $location
     */
    function addRes($res_url, $location="header")
    {
        $this->res_list [$location][] = $res_url;
    }

    /**
     * 加载 css|js
     * @param string $location
     * @return string
     */
    function loadRes($location="header")
    {
        $result = '';
        if (empty($this->res_list))
        {
            return $result;
        }

        if (isset($this->res_list [$location]) && !empty($this->res_list [$location]))
        {
            $data = $this->res_list [$location];
        }

        if (! empty($data))
        {
            if(is_array($data)) {
                foreach($data as $r) {
                    $result .= $this->outputResLink($r);
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
     * @return string
     */
    function outputResLink($res_link)
    {
        $t = Helper::getExt($res_link);

        switch( strtolower($t) )
        {
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
            return sprintf("{$tpl}\n", $this->res($res_link));
        }
        return null;
    }

    /**
     * 输出JSON
     *
     * @param $data
     * @return mixed
     */
    function JSON($data)
    {
        $this->set(
            array("layer"=>"json")
        );

        Response::getInstance()->set_ContentType( 'json' );
        echo json_encode($data);
    }

    /**
     * 输出XML
     *
     * @param $data
     * @return mixed
     */
    function XML( $data )
    {
        $this->set(
            array("layer"=>"xml")
        );

        Response::getInstance()->set_ContentType( 'xml' );
        $xml = new SimpleXMLElement('<root/>');
        $this->array_to_xml($data, $xml);

        echo $xml->asXML();
    }

    /**
     * 输出html
     */
    function HTML()
    {

    }

    /**
     * 数组转XML 数字会被转换成_num_
     *
     * @param $array_data
     * @param $xml_res
     */
    function array_to_xml($array_data, & $xml_res)
    {
        if(! is_array($array_data))
        {
            $array_data = array($array_data);
        }

        foreach($array_data as $key => $value)
        {
            if(is_array($value))
            {
                if(! is_numeric($key))
                {
                    $subNode = $xml_res->addChild($key);
                    $this->array_to_xml($value, $subNode);
                }
                else{
                    $this->array_to_xml($value, $xml_res);
                }
            }
            else if(is_numeric($key))
            {
                $xml_res->addChild("_{$key}_", $value);
            }
            else
            {
                $xml_res->addChild($key, $value);
            }
        }
    }

    /**
     * 加载布局
     *
     * @param $content
     * @param $layer_ext
     * @throws CoreException
     */
    function loadLayer($content, $layer_ext='.layer.php')
    {
        if($this->set)
        {
            extract($this->set, EXTR_PREFIX_SAME, "USER_DEFINED");
        }
        $_real_path = $this->getTplPath();
        $controller_config = $this->config->get("controller", strtolower($this->controller));

        //运行时>配置>默认
        if( isset($layer) )
        {
            $layer_file = $_real_path.$layer.$layer_ext;
        }
        else if( $controller_config && isset($controller_config["layer"]) )
        {
            $layer_file = $_real_path.$controller_config["layer"].$layer_ext;
        }
        else
        {
            $layer_file = $_real_path.'default'.$layer_ext;
        }

        if(! file_exists($layer_file) )
        {
            throw new CoreException($layer_file.' layer Not found!');
        }

        include $layer_file;
    }
}


