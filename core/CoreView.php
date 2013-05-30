<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:       wonli
 * @Version $Id: CoreView.php 78 2013-05-23 14:41:24Z ideaa $
 */
class CoreView extends FrameBase
{
    protected $set;
    protected $staticurl;
    protected $urlconfig;
    
    function __construct($action = null, $controller = null, $params=null, $cacheConfig = null)
    {
        $this->cache_config = $cacheConfig;
        $this->action = $action;
        $this->controller = $controller;
        $this->params = $params;
        $this->config = Cross::Config();

        $this->site_url = $this->config->get("sys", "site_url");
        $this->staticurl = $this->config->get("sys", "static_url");
        $this->urlconfig = $this->config->get("url");
        $this->set("static_url", $this->staticurl);
    }

    /**
     * 组装模板文件路径
     *
     * @param $tplname 模板名
     * @return string 模板名
     */
    function tpl($tplname)
    {
        return $this->getTplPath().$tplname.'.tpl.php';
    }

    /**
     * 设置layer显示属性
     * @param string/array $name  要设置的属性名称或数组
     * @param string/null $value 要设置的名称对应的值
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
     * 资源绝对路径
     * @param unknown $res_url
     * @return string
     */
    function res($res_url)
    {
        $url = isset($static_url)?$static_url:$this->staticurl;
        return $url.$res_url;
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
     * 生成链接
     * @param $controller 控制器:方法
     * @param $params 参数
     * @return string
     */
    function link($_controller=null, $params=null, $sec = false)
    {
        $_linkurl = $this->site_url;

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
     * 生成参数加密的url
     * @param string $_controller
     * @param string $params
     * @return Ambigous <string, string或array, boolean, 配置文件, multitype:NULL >
     */
    function slink($_controller=null, $params=null)
    {
        return $this->link($_controller, $params, true);
    }

    /**
     * 生成控制器连接
     * @param unknown $_controller
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
     * 生成参数
     * @param unknown $params
     * @param string $sec
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
            
            if($sec === true) {
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
        if($this->cache_config) {
            $cache_type = $this->cache_config["type"];
            $cache_extime = $this->cache_config["extime"];
            if($cache_type === 1) {
                return $this->buildHtml($date, $method);
            }
        }

        if($method === null) {
            $display_type = $this->config->get("sys", "display");

            if($display_type && $display_type !== "HTML") {
                $method = $display_type;
            }
        }

        return $this->_display($date, $method);
    }

    /**
     * 输出带Layer的HTML页面
     * @param string $date
     * @param string $method
     */
    function _display($date = null, $method = null)
    {
        $_action = $this->action;

        if(! $_action) {
            $_action = 'index';
        }

        ob_start();
        if($method) {
            $this->$method($date);
        } else $this->$_action( $date );

        $_content = ob_get_clean();
        $this->loadLayer($_content);
    }

    /**
     * 生成静态html页面
     *
     * @param $date 要渲染的数据
     * @param $method 调用的方法
     * @return
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
	 * alert提示
     * @param $message   要alert的消息内容
	 * @return javascript
	 */
    function showMessage($message, $ref = false)
    {
        if($ref) {
            echo '<script type="text/javascript">alert("'.$message.'");location.replace("'.$_SERVER['HTTP_REFERER'].'")</script>';
        } else {
            echo '<script type="text/javascript">alert("'.$message.'")</script>';
        }
    }

    /**
     * 错误页面提示
     *
     * @param
     * @return
     */
    static function error($message, $type='')
    {
        include CROSSPHP_PATH.'/exception/_tpl'.$type.'error.php';
    }

    /**
     * 内容信息
     *
     * @param
     * @return
     */
    function notes($message='出错啦!')
    {
        include  CROSSPHP_PATH.'/exception/_tpl/notes.php';
    }

    /**
     * 返回response
     * @return html 返回信息
     */
    function response()
    {
        $a = new Response;
        var_dump($a);
    }

    /**
     * 取得模板路径前缀
     *
     * @param
     * @return
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
        return 'web';
    }

    /**
     * 判断是否是蜘蛛
     *
     * @param
     * @return
     */
    function is_robot()
    {
        $agent= strtolower($_SERVER['HTTP_USER_AGENT']);
        if (!empty($agent))
        {
            $spiderSite= array( "TencentTraveler", "Baiduspider+", "BaiduGame", "Googlebot", "msnbot", "Sosospider+", "Sogou web spider", "ia_archiver", "Yahoo! Slurp", "YoudaoBot", "Yahoo Slurp", "MSNBot", "Java (Often spam bot)","BaiDuSpider", "Voila", "Yandex bot", "BSpider", "twiceler", "Sogou Spider","Speedy Spider","Google AdSense", "Heritrix", "Python-urllib", "Alexa (IA Archiver)", "Ask", "Exabot", "Custo", "OutfoxBot/YodaoBot", "yacy", "SurveyBot", "legs", "lwp-trivial", "Nutch", "StackRambler", "The web archive (IA Archiver)", "Perl tool", "MJ12bot", "Netcraft", "MSIECrawler", "WGet tools", "larbin", "Fish search");
            foreach($spiderSite as $val) {
                if (stripos($agent, $val) !== false) {
                    return $val;
                }
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 判断是否是移动设备
     *
     * @param
     * @return
     */
    function is_mobile()
    {
        //正则表达式,批配不同手机浏览器UA关键词。
        $regex_match ="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
        $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
        $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";
        $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
        $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
        $regex_match.=")/i";

        return isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']) or preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT'])); //如果UA中存在上面的关键词则返回真。
    }

    /**
     * 加载视图
     *
     * @param
     * @return
     */
    function load($controller_name, $action=null)
    {
        return $this->initView($action, $controller_name);
    }

    /**
     * 输出JSON
     * @param unknown $data
     */
    function JSON($data)
    {
        $this->set(
            array("layer"=>"json")
        );

        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * 输出XML
     * @param unknown $data
     */
    function XML($data)
    {
        $this->set(
            array("layer"=>"xml")
        );

        header('Content-Type: text/xml');
        $xml = new SimpleXMLElement('<root/>');
        $this->array_to_xml($data, $xml);

        echo $xml->asXML();
    }

    /**
     * 数组转xml
     * @param unknown $array_data
     * @param unknown $xml_res
     */
    function array_to_xml($array_data, &$xml_res) {
        foreach($array_data as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml_res->addChild("$key");
                    $this->array_to_xml($value, $subnode);
                }
                else{
                    $this->array_to_xml($value, $xml_res);
                }
            }
            else {
                $xml_res->addChild("$key","$value");
            }
        }
    }

    /**
     * 加载layer
     *
     * @param
     * @return mix
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
}


