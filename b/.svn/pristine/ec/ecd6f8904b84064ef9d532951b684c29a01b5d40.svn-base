<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:	    wonli@live.com
* Date:	        2011.08
* Description:  view
*/
class CoreView extends Response
{
    protected $set;
    protected $staticurl;
    protected $urlconfig;

    function __construct($action = null, $controller = null, $params=null, $config = null)
    {
        $this->action = $action;
        $this->controller = $controller;
        $this->params = $params;
        $this->config = $config;

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
        return $this->getTplPath().'page'.DS.$tplname.'.tpl.php';
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
    function link($_controller=null, $params=null)
    {
        $_linkurl = $this->site_url;
        
        if($_controller) {
            $_linkurl .= $this->makeController($_controller);
            $params = $this->makeParams($params);
        }

        if($params != '') {
            $_linkurl .= $this->urlconfig['dot'].$this->makeParams($params);
        }

        if($this->urlconfig['ext']) {
            $_linkurl .= $this->urlconfig['ext'];
        }

        return $_linkurl;
    }

    function slink($_controller=null, $params=null)
    {
        $_linkurl = $this->site_url;
        
        if($_controller) {
            $_linkurl .= $this->makeController($_controller);
        }

        if($_params != '') {
            $_params = $this->makeParams($params);
            $mcrypt = new Mcrypt;
            $_params = $mcrypt->enCode($_params);
            $_linkurl .= $_params[1];
        }

        if($this->urlconfig['ext']) {
            $_linkurl .= $this->urlconfig['ext'];
        }

        return $_linkurl;
    }

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

    private function makeParams($params)
    {
        $_linkurl = '';
        if($params) {
            if(is_array($params)) {
                $_linkurl = implode($this->urlconfig['dot'], $params);
            } else {
                $_linkurl = $params;
            }
        }

        return $_linkurl;
    }

    /**
     * 输出带Layer的HTML页面
     *
     * @param $date 要渲染的数据
     * @param $method 调用的方法
     * @return HTML
     */
    final function display(  $date=null, $method = null )
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
     * 生成html
     *
     * @param $date 要渲染的数据
     * @param $method 调用的方法
     * @return
     */
    function buildHtml( $date=null, $method=null )
    {
        ob_start();
        $this->display($date, $method);
        $html = ob_get_clean();

        $static_path = $this->config->get("sys", "app_path").DS.'cache'.DS.'html'.DS.$this->controller.DS.$this->action.DS;

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

    function exeJs($js)
    {
        echo '<script type="text/javascript">'.$js.'</script>';
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
        // if($type == 'front')
        // {
            // include CROSSPHP_PATH.'/exception/_tpl/fronterror.php';
        // } else {
            // include  CROSSPHP_PATH.'/exception/_tpl/error.php';
        // }
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
        */
        if ($this->is_mobile()) {
            return 'mobile';
        }

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
     * 加载模板
     *
     * @param
     * @return
     */
    static function loadtpl($tplname, $data = null)
    {

    }

    /**
     * 加载layer
     *
     * @param
     * @return mix
     */
    function loadLayer($content, $seo=null)
    {
        if($this->set) {
            extract($this->set, EXTR_PREFIX_SAME, "USER_DEFINED");
        }

        $_realpath = $this->getTplPath().'layer'.DS;

        if( isset($layer) ) {
            $layer = $_realpath.$layer.'.layer.php';            
        } else {
            $layer = $_realpath.'default.layer.php';
        }

        if( file_exists($layer) ) include $layer;
        else throw new CoreException($layer.' layer Not found!');
    }
}


