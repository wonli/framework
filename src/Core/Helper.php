<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.5
 */
namespace Cross\Core;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Helper
 * @package Cross\Core
 */
class Helper
{
    /**
     * 截取字符串
     *
     * @param string $str 要截取的字符串参数
     * @param string $len 截取的长度
     * @param string $enc 字符串编码
     * @return string
     */
    public static function subStr($str, $len, $enc = 'utf8')
    {
        if (self::strLen($str) > $len) {
            return mb_substr($str, 0, $len, $enc) . '...';
        } else {
            return $str;
        }
    }

    /**
     * 计算字符串长度
     *
     * @param string $str 要计算的字符串
     * @param string $enc 默认utf8编码
     * @return int
     */
    public static function strLen($str, $enc = 'gb2312')
    {
        return min(array(mb_strlen($str, $enc), mb_strlen($str, 'utf-8')));
    }

    /**
     * 将指定编码的字符串分割为数组
     *
     * @param string $str
     * @param string $charset 字符编码 默认utf-8
     * @return Array
     */
    static function stringToArray($str, $charset = 'utf-8')
    {
        if ($charset != 'utf-8') {
            $str = iconv($charset, 'utf-8', $str);
        }

        $result = array();
        for ($i = 0, $str_len = mb_strlen($str, 'utf-8'); $i < $str_len; $i++) {
            $result[] = mb_substr($str, $i, 1, 'utf-8');
        }

        return $result;
    }

    /**
     * 返回一个10位的md5编码后的str
     *
     * @param string $str
     * @return string
     */
    static function md10($str = '')
    {
        return substr(md5($str), 10, 10);
    }

    /**
     * 取得文件扩展名
     *
     * @param string $file 文件名
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
     * @return void
     */
    static function createFolders($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    /**
     * 根据文件名创建文件
     *
     * @param string $file_name
     * @param int $chmod
     * @return bool
     */
    static function mkfile($file_name, $chmod = 0777)
    {
        if (!file_exists($file_name)) {
            $file_path = dirname($file_name);
            self::createFolders($file_path);

            $fp = fopen($file_name, 'w+');
            if ($fp) {
                fclose($fp);
                chmod($file_name, $chmod);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 验证电子邮件格式
     *
     * @param string $email
     * @param string $add_valid_expr
     * @return bool
     */
    static function validEmail($email, $add_valid_expr = "/^[^-_0][a-zA-Z0-9._-]+$/")
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            if ($add_valid_expr) {
                list($valid_string,) = explode("@", $email);
                if (!preg_match($add_valid_expr, $valid_string)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * 返回一个指定长度的随机数
     *
     * @param int $length
     * @param int $numeric
     * @return string
     */
    static function random($length, $numeric = 0)
    {
        $seed = md5(print_r($_SERVER, 1) . microtime(true));
        if ($numeric) {
            $seed = str_replace('0', '', base_convert($seed, 16, 10)) . '0123456789';
        } else {
            $seed = base_convert($seed, 16, 35) . 'zZz' . strtoupper($seed);
        }

        $hash = '';
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed[mt_rand(0, $max)];
        }

        return $hash;
    }

    /**
     * 解析@到某某
     *
     * @param string $str
     * @return array
     */
    static function parseAt($str)
    {
        preg_match_all("/@([^@^\\s^:]{1,})([\\s\\:\\,\\;]{0,1})/", $str, $result);
        return $result;
    }

    /**
     * 过滤非法标签
     *
     * @param string $str
     * @param string $disallowable
     * @return mixed
     */
    static function stripSelectedTags($str, $disallowable = "<script><iframe><style><link>")
    {
        $disallowable = trim(str_replace(array(">", "<"), array("", "|"), $disallowable), '|');
        $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);
        $str = preg_replace("~<({$disallowable})[^>]*>(.*?<\s*\/(\\1)[^>]*>)?~is", '$2', $str);

        return $str;
    }

    /**
     * 转换html实体编码
     *
     * @param string $str
     * @return string
     */
    static function convertTags($str)
    {
        if ($str) {
            $str = str_replace(array('<', '>', "'", '"'), array('&lt;', '&gt;', '&#039;', '&quot;'), $str);
        }

        return $str;
    }

    /**
     * 字符串加密解密算法
     *
     * @param string $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @return string
     */
    static function authCode($string, $operation = 'DECODE', $key = 'crossphp', $expiry = 0)
    {
        $c_key_length = 4;
        $key = md5($key);

        $key_a = md5(substr($key, 0, 16));
        $key_b = md5(substr($key, 16, 16));
        $key_c = $c_key_length ? ($operation == 'DECODE' ? substr($string, 0, $c_key_length) :
            substr(md5(microtime()), -$c_key_length )) : '';

        $crypt_key = $key_a . md5($key_a . $key_c);
        $key_length = strlen($crypt_key);

        $string = $operation == 'DECODE' ?
            base64_decode(substr($string, $c_key_length)) :
            sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $key_b), 0, 16) . $string;

        $result = '';
        $box = range(0, 255);
        $string_length = strlen($string);

        $rnd_key = array();
        for ($i = 0; $i <= 255; $i++) {
            $rnd_key[$i] = $crypt_key[$i % $key_length];
        }
        $rnd_key = array_map('ord', $rnd_key);

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rnd_key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        $p1 = $p2 = array();
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $p1[] = $string[$i];
            $p2[] = $box[($box[$a] + $box[$j]) % 256];
        }

        if (!empty($p1)) {
            $p1 = array_map('ord', $p1);
            foreach ($p1 as $k => $pv) {
                $result[] = $pv ^ $p2[$k];
            }

            unset($p1, $p2, $box, $tmp, $rnd_key);
            $result = implode('', array_map('chr', $result));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
                substr($result, 10, 16) == substr(md5(substr($result, 26) . $key_b), 0, 16)
            ) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $key_c . str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 简单字符串加解密
     *
     * @param string $str
     * @param string $key
     * @param string $operation
     * @return bool|string
     */
    static function encodeParams($str, $key, $operation = "encode")
    {
        if ($operation == "encode") {
            $str = (string) $str;
        } else {
            $verity_str = substr($str, 0, 3);
            $str = substr($str, 3);
            //完整性验证
            if ($verity_str != substr(md5($str), 0, 3)) {
                return false;
            }

            $str = base64_decode($str);
        }

        $rand_key = md5($key);
        $str_len = strlen($str);

        $result_str = "";
        for ($i = 0; $i < $str_len; $i++) {
            $result_str .= chr(ord($str[$i]) ^ ord($rand_key[$i % 32]));
        }

        if ($operation == "encode") {
            $result_str = trim(base64_encode($result_str), "==");
            $result_str = substr(md5($result_str), 0, 3) . $result_str;
        }

        return $result_str;
    }

    /**
     * 生成四层深度的路径
     * <pre>
     * 如 id = 31 拼成如下路径
     * 000/00/00/31
     * </pre>
     *
     * @param int $id
     * @param string $path_name
     * @return string
     */
    static function getPath($id, $path_name = '')
    {
        $id = (string) abs($id);
        $id = str_pad($id, 9, '0', STR_PAD_LEFT);
        $dir1 = substr($id, 0, 3);
        $dir2 = substr($id, 3, 2);
        $dir3 = substr($id, 5, 2);

        return $path_name . '/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($id, -2) . '/';
    }

    /**
     * 发送一个http请求
     *
     * @param string $url
     * @param array $vars
     * @param string $method
     * @return mixed|string
     */
    static function curlRequest($url, $vars = array(), $method = 'post')
    {
        if (strcasecmp($method, 'GET') == 0 && !empty($vars)) {
            if (false === strpos($url, '?')) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= is_array($vars) ? http_build_query($vars) : $vars;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: {$method}"));

        if (strcasecmp($method, 'POST') == 0 || strcasecmp($method, 'PUT') == 0) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        }
        $result = curl_exec($ch);
        if (!curl_errno($ch)) {
            $result = trim($result);
        } else {
            $result = '[error：1]';
        }

        curl_close($ch);
        return $result;
    }

    /**
     * htmlspecialchars 函数包装
     *
     * @param string $str
     * @param int $quote_style
     * @return string
     */
    static function escape($str, $quote_style = ENT_COMPAT)
    {
        return htmlspecialchars($str, $quote_style);
    }

    /**
     * 求概率 返回key
     * <pre>
     * array(
     *  'a' => 60
     *  'b' => 30
     *  'c' => 10
     * );
     * </pre>
     *
     * @param array $array
     * @return int|bool
     */
    static function arrayRandomRate(array $array)
    {
        $max = array_sum($array);
        foreach ($array as $a_key => $a_value) {
            $rand = mt_rand(0, $max);

            if ($rand <= $a_value) {
                return $a_key;
            } else {
                $max -= $a_value;
            }
        }

        return false;
    }

    /**
     * 判断是否是中文字符串
     *
     * @param string $string
     * @return bool
     */
    static function isChinese($string)
    {
        if (preg_match("/^[\\x{4e00}-\\x{9fa5}]+$/u", $string)) {
            return true;
        }

        return false;
    }

    /**
     * 验证是否是一个正确的手机号
     *
     * @param int $mobile
     * @return bool
     */
    static function isMobile($mobile)
    {
        if (preg_match("/^1[3456789]\\d{9}$/", $mobile)) {
            return true;
        }

        return false;
    }

    /**
     * encrypt 加密解密
     *
     * @param string $crypt
     * @param string $mode
     * @param string $key
     * @return string
     */
    static function encrypt($crypt, $mode = 'DECODE', $key = '!@#6<>?*')
    {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
        if ('ENCODE' == $mode) {
            $pass_crypt = mcrypt_encrypt(MCRYPT_DES, $key, $crypt, MCRYPT_MODE_ECB, $iv);
            $str = str_replace(array('=', '/', '+'), array('', '-', '_'), base64_encode($pass_crypt));
        } else {
            $decoded = base64_decode(str_replace(array('-', '_'), array('/', '+'), $crypt));
            $str = mcrypt_decrypt(MCRYPT_DES, $key, $decoded, MCRYPT_MODE_ECB, $iv);
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
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp(
                $_SERVER['REMOTE_ADDR'],
                'unknown'
            )
        ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        //是否是一个合法的ip地址
        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }

    /**
     * 返回IP的整数形式
     *
     * @param string $ip
     * @return string
     */
    static function getLongIp($ip = "")
    {
        if ($ip == "") {
            $ip = self::getIp();
        }

        return sprintf("%u", ip2long($ip));
    }

    /**
     * 显示友好时间格式
     *
     * @param int $time 时间戳
     * @return string
     */
    static function ftime($time)
    {
        $t = time() - $time;
        if ($t < 63072000) {
            $f = array(
                '31536000' => '年',
                '2592000' => '个月',
                '604800' => '星期',
                '86400' => '天',
                '3600' => '小时',
                '60' => '分钟',
                '1' => '秒'
            );

            foreach ($f as $k => $v) {
                if (0 != $c = floor($t / (int) $k)) {
                    return $c . $v . '前';
                }
            }
        }

        return date('Y-m-d H:i:s', $time);
    }

    /**
     * 格式化数据大小(单位byte)
     *
     * @param int $size
     * @return string
     */
    static function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}
