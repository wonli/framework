<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author:  wonli<wonli@live.com>
 */
class Helper
{
    const AUTH_KEY = "crossphp";

    /**
     * @var array 星座
     */
    public static $su = array(1=>'白羊座',2=>'金牛座',3=>'双子座',4=>'巨蟹座',5=>'狮子座',
        6=>'处女座',7=>'天枰座',8=>'天蝎座',9=>'射手座',10=>'摩羯座',11=>'水瓶座',12=>'双鱼座');

    /**
     * @var array 性别类型
     */
    public static $gender = array(1=>'女', 2=>'男', 3=>'女偏男', 4=>'男偏女', 5=>'中性', 6=>'由女变成男', 7=>'由男变成女');

    /**
     * 显示友好时间格式
     *
     * @param $time 时间戳
     * @return mixd
     */
    static function ftime($time){
        $t=time()-$time;

        if($t > 2592000 ) {

            return date('Y-m-d H:i:s', $time);

        } else {

            $f=array(
                '31536000'=>'年',
                '2592000'=>'个月',
                '604800'=>'星期',
                '86400'=>'天',
                '3600'=>'小时',
                '60'=>'分钟',
                '1'=>'秒'
            );
            foreach ($f as $k=>$v) {
                if (0!=$c=floor($t/(int)$k)){
                    return $c.$v.'前';
                }
            }
        }
    }

    /**
     * 截取字符串
     *
     * @param $str 要截取的字符串参数
     * @param $len 截取的长度
     * @param string $enc 字符串编码
     * @return string
     */
    public static function subStr($str, $len, $enc = 'utf8')
    {
        if(self::strLen($str) > $len) {
            return mb_substr($str, 0, $len, $enc).'...';
        } else {
            return $str;
        }
    }

    /**
     * 计算字符串长度
     * @param $str
     * @param string $enc 默认utf8编码
     * @return int
     */
    public static function strLen($str, $enc = 'gb2312')
    {
        return min( array(mb_strlen($str,$enc), mb_strlen($str,'utf-8')) );
    }

    /**
    * 将指定编码的字符串分割为数组
    *
    * @param string $str
    * @param string $charset 字符编码 默认utf-8
    * @return Array
    */
    static function str_split($str,$charset='utf-8') 
    {
        if($charset != 'utf-8') {
            $str = iconv($charset,'utf-8',$str);
        }

        $split=1;
        $array = array();
        
        for ( $i=0; $i < strlen( $str ); )
        {
            $value = ord($str[$i]);
            if($value > 127)
            {
                if($value >= 192 && $value <= 223) 
                $split=2;
                elseif($value >= 224 && $value <= 239)
                $split=3;
                elseif($value >= 240 && $value <= 247)
                $split=4;
            } else {
                $split=1;
            }
            $key = NULL;
            for ( $j = 0; $j < $split; $j++, $i++ ) {
                $key .= $str[$i];
            }
            array_push( $array, $key );
        }

        if($charset != 'utf-8') {
            foreach($array as $key=>$value) {
                $array[$key] = iconv('utf-8',$charset,$value);
            }
        }
        
        return $array;
    }

    /**
     * 返回一个10位的md5编码后的str
     *
     * @param string $str
     * @return string
     */
    static function md10($str='')
    {
        return substr(md5($str),10,10);
    }

    /**
     * 取得文件扩展名
     *
     * @param $file 文件名
     * @return mixed
     */
    static function getExt($file)
    {
        $_info = pathinfo($file);
        return $_info['extension'];
    }

    /**
     * 创建文件夹
     *
     * @param $path
     */
    static function createFolders($path)
    {
        if (!file_exists($path))
        {
            self::createFolders(dirname($path));
            mkdir($path,0777);
        }
    }

    /**
     * 验证电子邮件格式
     *
     * @param $email
     * @return bool
     */
    static function valid_email($email)
    {
        if (!@ereg("^[^@]{1,64}@[^@]{1,255}$",$email))
        {
            return false;
        }

        $email_array = explode("@",$email);
        $local_array = explode(".",$email_array[0]);
        for ($i = 0;$i <sizeof($local_array);$i++)
        {
            if (!@ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",$local_array[$i]))
            {
                return false;
            }
        }

        if (!@ereg("^\[?[0-9\.]+\]?$",$email_array[1]))
        {
            $domain_array = explode(".",$email_array[1]);
            if (sizeof($domain_array) <2)
            {
                return false;
            }

            for ($i = 0;$i <sizeof($domain_array);$i++){
                if (!@ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",$domain_array[$i]))
                {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 返回一个指定长度的随机数
     *
     * @param $length
     * @param int $numeric
     * @return string
     */
    static function random($length,$numeric = 0)
    {
        PHP_VERSION <'4.2.0'? mt_srand((double)microtime() * 1000000) : mt_srand();
        $seed = base_convert(md5(print_r($_SERVER,1).microtime()),16,$numeric ?10 : 35);
        $seed = $numeric ?(str_replace('0','',$seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
        $hash = '';
        $max = strlen($seed) -1;
        for($i = 0;$i <$length;$i++) {
            $hash .= $seed[mt_rand(0,$max)];
        }

        return $hash;
    }

    /**
     * 过滤非法标签
     *
     * @param $str
     * @param string $disallowable
     * @return mixed
     */
    static function strip_selected_tags($str,$disallowable="<script><iframe><style><link>")
	{
		$disallowable	= trim(str_replace(array(">","<"),array("","|"),$disallowable),'|');
		$str			= str_replace(array('&lt;', '&gt;'),array('<', '>'),$str);
		$str			= preg_replace("~<({$disallowable})[^>]*>(.*?<\s*\/(\\1)[^>]*>)?~is",'$2',$str);

		return $str;
	}

    /**
     * 转换html实体编码
     *
     * @param $str
     * @return mixed
     */
    static function convert_tags($str)
	{
		if($str) {
            $str = str_replace(array('<', '>',"'",'"'),array('&lt;', '&gt;','&#039;','&quot;'),$str);
        }
	 	return $str;
	}

    /**
     * 字符串加密解密算法
     *
     * @param $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @return string
     */
    static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

        $ckey_length = 4;
        $key = md5($key ? $key : self::AUTH_KEY);

        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ?
            ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE'
            ? base64_decode(substr($string, $ckey_length)) :
            sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;

        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
                && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 简单字符串加解密
     *
     * @param $tex
     * @param $key
     * @param string $type
     * @return bool|string
     */
    static function encode_params($tex, $key, $type="encode")
    {
        if($type=="decode") {
            if( strlen($tex) < 5 )return false;
            $verity_str=substr($tex, 0, 3);
            $tex=substr($tex, 3);
            if($verity_str!=substr(md5($tex), 0, 3)){
                //完整性验证失败
                return false;
            }
        }
        $rand_key=md5($key);

        if($type == "decode") {
            $tex = base64_decode($tex);
        } else {
            $tex = strval($tex);
        }

        $texlen=strlen($tex);
        $reslutstr="";
        for($i=0;$i<$texlen;$i++){
            $reslutstr.=$tex{$i}^$rand_key{$i%32};
        }

        if($type!="decode"){
            $reslutstr=trim(base64_encode($reslutstr),"==");
            $reslutstr=substr(md5($reslutstr), 0,3).$reslutstr;
        }
        return $reslutstr;
    }
    
    /**
     * 按类型和长度生成随机字符串
     *
     * @param int $type <pre>
     * [1] 纯数字
     * [2] 英文字符
     * [3] 过滤掉0,O,i,I,1,L这些后的英文字符
     * [4] 中文字符
     * </pre>
     * @param int $length
     * @return string
     */
    public static function getRandomStr( $type=3, $length=4 ) {
        $string='';

        switch($type){
            case 1:
                $string=join('',array_rand(range(0,9),$length));
                break;
            case 2:
                $string=implode('',array_rand(array_flip(range('a','z')),$length));
                break;
            case 3:
                $str='abcdefghijkmnprstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                $string=substr(str_shuffle($str),0,$length);
                break;
            case 4:
                for($i=0;$i<$length;$i++) {
                    $string=$string.chr(rand(0xB0,0xCC)).chr(rand(0xA1,0xBB));
                }
                $string=iconv('GB2312','UTF-8',$string); //转换编码到utf8
                break;
        }

        return $string;
    }

    /**
     * 生成四层深度的路径
     *
     * @param $id
     * @param string $path_name
     * @return string
     */
    static function get_path($id, $path_name='') {
        $id = strval(abs($id)); //ID取整数绝对值
        $id = str_pad(strval($id), 9, "0", STR_PAD_LEFT);//前边加0补齐9位，例如ID31变成 000000031
        $dir1 = substr($id, 0, 3);  //取左边3位，即 000
        $dir2 = substr($id, 3, 2);  //取4-5位，即00
        $dir3 = substr($id, 5, 2);  //取6-7位，即00
        // 下面拼成路径，即000/00/00/31
        return  $path_name.'/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($id, -2).'/';
    }     
    
	/**
	 * 发送一个http请求
     *
	 * @param  $url    请求链接
	 * @param  $method 请求方式
	 * @param array $vars 请求参数
	 * @param  $time_out  请求过期时间
	 * @return JsonObj
	 */
	static function curl_request($url, array $vars=array(), $method = 'post')
	{
		$method = strtolower($method);
		if($method == 'get' && !empty($vars))
		{
			if(strpos($url, '?') === false)
            {
				$url = $url . '?' . http_build_query($vars);
            }
			else
            {
				$url = $url . '&' . http_build_query($vars);
            }
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
		if ($method == 'post') 
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		}	
		$result = curl_exec($ch);
		if(!curl_errno($ch))
		{
			$result = trim($result);
		}
		else
		{
			$result = '[error：1]';
		}
		
		curl_close($ch);
		return $result;
	}  

    /**
     * 递归方式的对变量中的特殊字符进行转义以及过滤标签
     *
     * @param $value
     * @return array|string
     */
    static function addslashes_deep($value)
	{
		if (empty($value))return $value;
		return is_array($value) ? array_map('addslashes_deep', $value) : strip_tags(addslashes($value));
	}

    /**
     * 反引用一个字符串引用项
     *
     * @param $value
     * @return array|string
     */
    static function stripslashes_deep($value)
	{
		if (empty($value))return $value;
		return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
	}

    /**
     * htmlspecialchars 函数包装
     *
     * @param $str
     * @param int $quote_style
     * @return string
     */
    static function escape($str,  $quote_style = ENT_COMPAT )
	{
		return htmlspecialchars($str, $quote_style);
	}

    /**
     * 判断是否是中文字符串
     *
     * @param $string
     * @return bool
     */
    static function isChinese($string)
	{
		if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$string))
			return true;
		return false;
	}

    /**
     * 验证是否是一个正确的手机号
     *
     * @param $mobile
     * @return bool
     */
    static function isMobile($mobile)
	{
		if(preg_match("/^1[345689]\d{9}$/", $mobile))
			return true;
		return false;
	}

    /**
     * 取得当前日期星期几
     *
     * @param null $time
     * @return mixed
     */
    static function dayToWeek($time=null)
	{
		$time = empty($time) ? time() : $time;
		$date[0] = '周日';
		$date[1] = '周一';
		$date[2] = '周二';
		$date[3] = '周三';
		$date[4] = '周四';
		$date[5] = '周五';
		$date[6] = '周六';
		return $date[Date('w',$time)];
	}

    /**
     * encrypt 加密解密
     *
     * @param $crypt
     * @param string $mode
     * @return mixed|string
     */
    static function encrypt($crypt,$mode='DECODE')
    {
        $key = '!@#6<>?*';//任意8位字符串
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES,MCRYPT_MODE_ECB),MCRYPT_RAND);

        if( 'ENCODE' == $mode )
        {   
            $passcrypt = mcrypt_encrypt(MCRYPT_DES ,$key, $crypt, MCRYPT_MODE_ECB, $iv);
            $str =  str_replace( array('=','/','+'), array('','-','_'), base64_encode($passcrypt) );
        }else{
           $decoded = base64_decode( str_replace(array('-','_'), array('/','+'), $crypt ) );
           $str = mcrypt_decrypt(MCRYPT_DES ,$key, $decoded, MCRYPT_MODE_ECB, $iv); 
        }

        return $str;
    }

    /**
     * 取得用户真实ip
     *
     * @return string
     */
    static function getIp()
    {
        $ip = null;
        if(getenv('HTTP_CLIENT_IP') &&strcasecmp(getenv('HTTP_CLIENT_IP'),'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') &&strcasecmp(getenv('HTTP_X_FORWARDED_FOR'),'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') &&strcasecmp(getenv('REMOTE_ADDR'),'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) &&$_SERVER['REMOTE_ADDR'] &&strcasecmp($_SERVER['REMOTE_ADDR'],'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        //是否是一个合法的ip地址
        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }

    /**
     * 格式化数据大小(单位byte)
     *
     * @param $size
     * @return string
     */
    static function convert($size) { 
        $unit=array('b','kb','mb','gb','tb','pb'); 
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]; 
    }  
}
