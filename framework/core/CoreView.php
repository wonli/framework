<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version $Id: CoreView.php 106 2013-08-09 08:26:21Z ideaa $
 */
class CoreView extends FrameBase
{

    /**
     * @var 模板数据
     */
    protected $data;

    /**
     * @var layer字面量
     */
    protected $set;

    /**
     * @var string url配置
     */
    protected $urlconfig;

    /**
     * @var 资源链表
     */
    protected $res_list;

    /**
     * @var 默认连接
     */
    protected $link_base;
    
    /**
     * @var 静态资源路径
     */
    protected $static_url;    
    
    /**
     * 初始化模板
     *
     * @param null $action
     * @param null $controller
     * @param null $params
     * @param null $cacheConfig
     */
    function __construct( )
    {
        parent::__construct();

        $this->urlconfig  = $this->config->get("url");
        $this->link_base  = $this->config->get("sys", "site_url");
        $this->static_url = $this->config->get("sys", "static_url");
        
        $this->set("static_url", $this->static_url);
    }

    /**
     * 模板的绝对路径
     *
     * @param $tplname
     * @return string
     */
    function tpl($tplname)
    {
        return $this->getTplPath().$tplname.'.tpl.php';
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
     * 生成资源路径
     *
     * @param $res_url
     * @return string
     */
    function res($res_url)
    {
        return rtrim($this->static_url, "/").'/'.$res_url;
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
     * @param null $_controller 控制器:方法
     * @param null $params
     * @param bool $sec
     * @return string
     */
    function link($_controller=null, $params=null, $sec = false)
    {
        $_linkurl = $this->link_base;

        if($_controller) {
            $_linkurl .= $this->makeController($_controller);
        }

        if($params != null) {
            $_linkurl .= $this->urlconfig['dot'].$this->makeParams($params, $sec);
        }

        if($this->urlconfig['ext']) {
            $_linkurl .= $this->urlconfig['ext'];
        }

        return $_linkurl;
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
     * @param $_controller
     * @return string
     */
    private function makeController($_controller)
    {
        $_linkurl = '';
        if(false !== strpos($_controller, ":")){
            list($controller,$action) = explode(":", $_controller);
        } else {
            $controller = $_controller;
        }

        if($this->urlconfig ['rewrite']) {
            $_linkurl .= '/'.$controller;
        }else{
            $index = $this->urlconfig ["index"];
            $_dot = "?";
            
            if($index == 'index.php') {                
                if($this->urlconfig ["type"] == 2) {
                	$_dot = $index;	
                }                
                $_linkurl .= '/'.$_dot.'/'.$controller;
            } else {
                $_linkurl .= '/'.$index.$_dot.$controller;
            }
        }

        if(isset($action)){
            $_linkurl .= $this->urlconfig['dot'].$action;
        }

        return $_linkurl;
    }

    /**
     * 生成link参数
     *
     * @param $params
     * @param bool $sec
     * @return string
     */
    private function makeParams($params, $sec = false)
    {
        $_params = '';
        
        if($params) {            
            $_piex = '';
            if($this->urlconfig ["type"] == 1)
            {
                if(is_array($params)) {
                    $_params = implode($this->urlconfig['dot'], $params);
                } else {
                    $_params = $params;
                }
            } else {
                $_piex = '?';
                if(is_array($params)) {
                    $_params = http_build_query($params);                    
                } else {
                    $_params = $params;
                }            
            }
            
            if(true === $sec) {
                $_params = $this->encode_params($_params, "crossphp");
            }
        }

        return $_piex.$_params;
    }

    /**
     * 带生成静态页面的_display方法
     * 
     * @param $date 要渲染的数据
     * @param $method 调用的方法
     * @return HTML
     */
    final function display(  $date=null, $method = null )
    {
        $this->data = $date;
        if($this->cache_config)
        {
            $cache_type = $this->cache_config["type"];
            $cache_extime = $this->cache_config["extime"];

            if($cache_type === 1)
            {
                return $this->buildHtml($date, $method);
            }
        }

        if($method === null)
        {
            $display_type = $this->config->get("sys", "display");

            if($display_type && $display_type != "HTML") {
                $method = strtoupper( $display_type );
            }
        }
        return $this->_display($date, $method);
    }

    /**
     * 输出带Layer的HTML页面
     *
     * @param string $date
     * @param string $method
     */
    function _display($data = null, $method = null)
    {
        if(null == $method)
        {
            $method = $this->action;
        }

        if(! $method) {
            $method = Router::$default_action;
        }

        ob_start();
        $this->$method( $data );
        $this->loadLayer( ob_get_clean() );
    }

    /**
     * 生成静态页面
     *
     * @param null $date 要渲染的数据
     * @param null $method
     * @throws CoreException
     */
    function buildHtml( $date=null, $method=null )
    {
        ob_start();
        $this->_display($date, $method);
        $html = ob_get_clean();

        $static_path = $this->config->get("sys", "cache_path").'html'.DS.$this->controller.DS.$this->action.DS;

        if($this->params) {
            $htmlfile = $static_path.$this->params.'.html';
        } else {
            $htmlfile = $static_path.'index.html';
        }

        if(! is_file($htmlfile)) {
            if(!is_dir(dirname($htmlfile))) {
                mkdir(dirname($htmlfile), 0777, true);
            }
            if(false === file_put_contents($htmlfile, $html)) {
                throw new CoreException("生成失败!");
            }
        } else {
            if(md5($html) == md5( file_get_contents($htmlfile) ) ) {
                touch($htmlfile);
            } else {
                if(false === file_put_contents($htmlfile, $html)) {
                    throw new CoreException("生成失败!");
                }
            }
        }

        echo $html;
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
        //正则表达式,匹配不同手机浏览器UA关键词。
        $regex_match ="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
        $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
        $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";
        $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
        $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
        $regex_match.=")/i";

        return isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']) or preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']));
    }

    /**
     * 添加资源 css|js
     * @param $res_url
     * @param string $location
     */
    function addres($res_url, $location="header"){
        $this->res_list [ $location ] = $res_url;
    }

    /**
     * 加载 css|js
     * @param string $location
     * @return string
     */
    function loadres($location="header")
    {
        $result = '';
        if(isset($this->res_list [$location]) && !empty($this->res_list [$location])) {
            $data = $this->res_list [$location];
        }

        if(! empty($data))
        {
            if(is_array($data)) {
                foreach($data as $r) {
                    $result .= $this->output_reslink($r);
                }
            } else {
                $result .= $this->output_reslink($data);
            }
        }

        return $result;
    }

    /**
     * 输出js/css连接
     * @param $res_link
     * @return string
     */
    function output_reslink($res_link)
    {
        $t = Helper::getExt($res_link);

        switch( strtolower($t) )
        {
            case 'js' :
                $tpl = '<script type="text/javascript" src="%s"></script>';
                break;
            case 'css' :
                $tpl = '<link rel="stylesheet" type="text/css" href="%s"/>';

            default :
                $tpl = null;
        }

        if(null !== $tpl) {
            return sprintf($tpl, $res_link);
        }
        return null;
    }

    /**
     * 加载其他视图
     *
     * @param $controller_name
     * @param null $action
     * @return mixed
     */
    function load($controller_name, $action=null)
    {
        return $this->loadView( $controller_name, $action );
    }

    /**
     * 输出JSON
     *
     * @param $data
     */
    function JSON($data)
    {
        $this->set(
            array("layer"=>"json")
        );
        
        return Response::getInstance("json")->output(200, json_encode($data));
    }

    /**
     * 输出XML
     *
     * @param $data
     */
    function XML($data)
    {
        $this->set(
            array("layer"=>"xml")
        );

        $xml = new SimpleXMLElement('<root/>');
        $this->array_to_xml($data, $xml);

        return Response::getInstance("xml")->output(200, $xml->asXML());
    }

    /**
     * 数组转XML 数字会被转换成_num_
     *
     * @param $array_data
     * @param $xml_res
     */
    function array_to_xml($array_data, & $xml_res) {
        foreach($array_data as $key => $value) {
            if(is_array($value)) {
                if(! is_numeric($key)){
                    $subnode = $xml_res->addChild($key);
                    $this->array_to_xml($value, $subnode);
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
     * @param null $p
     * @throws CoreException
     */
    function loadLayer($content, $p=null)
    {
        if($this->set) {
            extract($this->set, EXTR_PREFIX_SAME, "USER_DEFINED");
        }
        $_realpath = $this->getTplPath();
        $controller_config = $this->config->get("controller", strtolower($this->controller));

        //运行时高于配置 配置高于默认
        if( isset($layer) ) {
            $layer = $_realpath.$layer.'.layer.php';
        } else if( $controller_config && isset($controller_config["layer"]) ) {
            $layer = $_realpath.$controller_config["layer"].'.layer.php';
        } else {
            $layer = $_realpath.'default.layer.php';
        }

        if( file_exists($layer) ) include $layer;
        else throw new CoreException($layer.' layer Not found!');
    }
    
    /**
     * 访问未定义的方法时 通过__call 抛出异常
     *
     * @param $action
     * @param $args
     * @throws FrontException
     */
    function __call($action, $argv)
    {
        throw new FrontException("未定义的方法 {$action}");
    }
}


