<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  Helper
*/
class Helper
{
    const AUTH_KEY = "crossphp";
    public static $su = array(1=>'白羊座',2=>'金牛座',3=>'双子座',4=>'巨蟹座',5=>'狮子座',6=>'处女座',7=>'天枰座',8=>'天蝎座',9=>'射手座',10=>'摩羯座',11=>'水瓶座',12=>'双鱼座');
    public static $gender = array(1=>'女', 2=>'男', 3=>'女偏男', 4=>'男偏女', 5=>'中性', 6=>'由女变成男', 7=>'由男变成女');
    
    /**
     * 友好时间
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
    
    static function getUserInfo($uid)
    {
        $db = new DataAccess();
        return $db->fetchOne("SELECT * FROM `user` WHERE `uid`={$uid}");
    }
    
    //如果UTF8字符串超过指定长度则返回指定长度的字符串
    public static function subStr($str, $len, $enc = 'utf8')
    {
        if(self::strLen($str) > $len) {
            return mb_substr($str, 0, $len, $enc).'...';
        } else {
            return $str;
        }
    }

    public static function strLen($str, $enc = 'utf8')
    {
        return mb_strlen($str, $enc);
    }   
    
    static function md10($str='')
    {
        return substr(md5($str),10,10);
    } 

    static function getExt($file)
    {
        $_info = pathinfo($file);
        return $_info['extension'];
    }

    static function createFolders($path)  
    {
        if (!file_exists($path))
        {
            self::createFolders(dirname($path));
            mkdir($path,0777);
        }
    }

    function editor2html($str,$topicid)
    {
        global $db;

        preg_match_all('/\[(photo)=(\d+)\]/is',$str,$photos);
        foreach ($photos[2] as $item) 
        {
            $strPhoto = aac('photo')->getPhotoForApp($item);
            $str = str_replace("[photo={$item}]",'<a href="'.SITE_URL.'go/url/goid-'.$topicid.'" target="_blank">
                                        <img class="thumbnail" src="'.SITE_URL.miniimg($strPhoto['photourl'],'photo','500','500',$strPhoto['path']).'" title="查看购买信息" /></a>',$str);
        }

        preg_match_all('/\[(attach)=(\d+)\]/is',$str,$attachs);
        if($attachs[2])
        {
            foreach ($attachs[2] as $aitem) 
            {
                $strAttach = aac('attach')->getOneAttach($aitem);
                
                if($strAttach['isattach'] == '1')
                {
                    $str = str_replace("[attach={$aitem}]",'<span class="attach_down">附件下载：<a href="'.SITE_URL.'index.php?app=attach&ac=ajax&ts=down&attachid='.$aitem.'" target="_blank">'.$strAttach["attachname"].'</a></span>',$str);
                }else{
                    $str = str_replace("[attach={$aitem}]",'',$str);
                }
            }
        }
        $find = array("http://","-",'.',"/",'?','=','&');
        $replace = array("",'_','','','','','');
        
        preg_match_all('/\[(video)=(.*?)\]/is',$str,$video);
        if($video[2])
        {
            foreach ($video[2] as $aitem) 
            {
                $arr = explode(',',$aitem);
                $id = str_replace($find,$replace,$arr[0]);
                $html = '<div id="img_'.$id.'"><a href="javascript:void(0)" onclick="showVideo(\''.$id.'\',\''.$arr[1].'\');"><img src="'.$arr[0].'"/></a></div>';
                $html .= '<div id="play_'.$id.'" style="display:none">'.$arr['2'].' <a href="javascript:void(0)" onclick="showVideo(\''.$id.'\',\''.$arr[1].'\');">收起</a>
                            <div id="swf_'.$id.'"></div> </div>';
                $str = str_replace("[video={$aitem}]",$html,$str);
            }
        }
        
        preg_match_all('/\[(mp3)=(.*?)\]/is',$str,$music);
        if($music[2])
        {
            foreach ($music[2] as $aitem) 
            {
                $arr = explode(',',$aitem);
                $id = str_replace($find,$replace,$arr[0]);
                $mp3flash ='<div id="mp3swf_'.$id.'" class="mp3player">
                            <div>'.$arr[1].' <a href="'.$aitem.'" target="_blank">下载</a> </div>
                            <object height="24" width="290" data="'.SITE_URL.'public/flash/player.swf" type="application/x-shockwave-flash">
                                <param value="'.SITE_URL.'public/flash/player.swf" name="movie"/>
                                <param value="autostart=no&bg=0xCDDFF3&leftbg=0x357DCE&lefticon=0xF2F2F2&rightbg=0xF06A51&rightbghover=0xAF2910&righticon=0xF2F2F2&righticonhover=0xFFFFFF&text=0x357DCE&slider=0x357DCE&track=0xFFFFFF&border=0xFFFFFF&loader=0xAF2910&soundFile='.$aitem.'" name="FlashVars"/>
                                <param value="high" name="quality"/>
                                <param value="false" name="menu"/>
                                <param value="#FFFFFF" name="bgcolor"/>
                                </object></div>';
                $str = str_replace("[mp3={$aitem}]",$mp3flash,$str);
            }
        }
        return $str;
        unset($db);
    }

    static function hview($text)
    {
        $text = stripslashes($text);
        $text = nl2br($text);
        return $text;
    }

    static function paginationv($count,$perlogs,$page,$url,$suffix='')
    {
        $pnums = @ceil($count / $perlogs);
        $re = '';
        for ($i = $page-5;$i <= $page+5 &&$i <= $pnums;$i++)
        {
            if ($i >0)
            {
                if ($i == $page) {
                    $re .= ' <span class="current">'.$i.'</span> ';
                }else {
                    $re .= '<a href="'.SITE_URL.$url.$i.$suffix.'">'.$i.'</a>';
                }
            }
        }
        if ($page >6) $re = '<a href="'.SITE_URL.$url.'1'.$suffix.'" title="首页">&laquo;</a> ...'.$re;
        if ($page +5 <$pnums) $re .= '... <a href="'.SITE_URL.$url.$pnums.$suffix.'" title="尾页">&raquo;</a>';
        if ($pnums <= 1) $re = '';
        return $re;
    }

    static function pagination($count,$perlogs,$page,$url,$suffix='')
    {
        $pnums = @ceil($count / $perlogs);
        $re = '';
        for ($i = $page-5;$i <= $page+5 &&$i <= $pnums;$i++)
        {
            if ($i >0)
            {
                if ($i == $page)
                {
                    $re .= ' <span class="current">'.$i.'</span> ';
                }else {
                    $re .= '<a href="'.SITE_URL.$url.$i.$suffix.'">'.$i.'</a>';
                }
            }
        }
        if ($page >6) $re = '<a href="'.$url.'1'.$suffix.'" title="首页">&laquo;</a> ...'.$re;
        if ($page +5 <$pnums) $re .= '... <a href="'.$url.$pnums.$suffix.'" title="尾页">&raquo;</a>';
        if ($pnums <= 1) $re = '';
        return $re;
    }

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
    
    static function getIp()
    {
        if(getenv('HTTP_CLIENT_IP') &&strcasecmp(getenv('HTTP_CLIENT_IP'),'unknown')) {
            $PHP_IP = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') &&strcasecmp(getenv('HTTP_X_FORWARDED_FOR'),'unknown')) {
            $PHP_IP = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') &&strcasecmp(getenv('REMOTE_ADDR'),'unknown')) {
            $PHP_IP = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) &&$_SERVER['REMOTE_ADDR'] &&strcasecmp($_SERVER['REMOTE_ADDR'],'unknown')) {
            $PHP_IP = $_SERVER['REMOTE_ADDR'];
        }
        preg_match("/[\d\.]{7,15}/",$PHP_IP,$ipmatches);
        $PHP_IP = $ipmatches[0] ?$ipmatches[0] : 'unknown';
        return $PHP_IP;
    }   

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
    
    static function url2Path($url)
    {
        return str_replace('/',DS,$url);
    }
    
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

        $ckey_length = 4;

        $key = md5($key ? $key : self::AUTH_KEY);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
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
            
            echo $result."--";
            
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    } 

	public static function echoStr($str,$len,$IsNotPoint=false)
	{
		//$len = ceil($len / 3) + 3 ;
		$temp=self::GetShowLen($str);
		if($temp == $len) {
			return $str ;
		}else{
			$len = $len - 1 ;
		}
		$start = 0 ;	
        $chars = $str;   
        $i=0; 
        $m=0;
        $n=0;
        do {  
            if (isset($chars[$i]) && preg_match ("/[0-9a-zA-Z ,:.;?!'\"]/", $chars[$i])){ 
                $m++;  
            } else {   $n++;  }
            $k = $n/3+$m/2;  
            $l = $n/3+$m;
            $i++;  
        } while($k < $len);  
        $str1 = mb_substr($str,$start,$l,'utf-8');
        if(strlen($str)==strlen($str1)) return $str ;
        else if($IsNotPoint) {
            return $str1;
        }
        else return $str1."..";  	  
	}   

    /*获取字符长度*/
    public static function GetStrLen ($str)
    {
        return mb_strlen($str);
    }
    
    /*获取显示长度,2英文算一个长度 $s必须为utf-8编码*/
    public static function GetShowLen ($s)
    {
        $al = self::GetStrLen($s);

        $el = 0;
        foreach (unpack("C*", $s) as $e)
        {
            if ($e <= 0x7F) //<=0x7F的是英文字符
            {
                $el ++;
            }
        }

        return ($al - $el) + intval($el / 2) + $el % 2;
    }
    
}
