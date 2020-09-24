<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;

use DOMDocument;
use Exception;
use DateTime;

/**
 * @author wonli <wonli@live.com>
 * Class Helper
 * @package Cross\Core
 */
class Helper
{
    /**
     * 截取字符串
     *
     * @param string $str 要截取的字符串参数
     * @param int $len 截取的长度
     * @param string $enc 字符串编码
     * @return string
     */
    public static function subStr(string $str, int $len, string $enc = 'utf8'): string
    {
        if (self::strLen($str) > $len) {
            return mb_substr($str, 0, $len, $enc) . '...';
        } else {
            return $str;
        }
    }

    /**
     * 安全的截取HTML字符串
     *
     * @param string $str 要截取的字符串参数
     * @param int $len 截取的长度
     * @param string $enc 字符串编码
     * @return string
     */
    public static function subStrHTML(string $str, int $len, string $enc = 'utf8'): string
    {
        $str = self::subStr($str, $len, $enc);
        return self::formatHTMLString($str);
    }

    /**
     * 处理HTML字符串，清除未闭合的HTML标签等
     *
     * @param string $str HTML字符串
     * @param bool $removingDoctype
     * @return string
     */
    public static function formatHTMLString(string $str, bool $removingDoctype = true): string
    {
        $DOCUMENT = new DOMDocument();
        @$DOCUMENT->loadHTML(mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8'));
        $content = $DOCUMENT->saveHTML($DOCUMENT->documentElement);
        if ($removingDoctype) {
            return preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $content);
        }

        return $content;
    }

    /**
     * 计算字符串长度
     *
     * @param string $str 要计算的字符串
     * @param string $enc 默认utf8编码
     * @return int
     */
    public static function strLen(string $str, string $enc = 'gb2312'): int
    {
        return min([mb_strlen($str, $enc), mb_strlen($str, 'utf-8')]);
    }

    /**
     * 将指定编码的字符串分割为数组
     *
     * @param string $str
     * @param string $charset 字符编码 默认utf-8
     * @return array
     */
    static function stringToArray(string $str, string $charset = 'utf-8'): array
    {
        if ($charset != 'utf-8') {
            $str = iconv($charset, 'utf-8', $str);
        }

        $result = [];
        for ($i = 0, $strLen = mb_strlen($str, 'utf-8'); $i < $strLen; $i++) {
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
    static function md10($str = ''): string
    {
        return substr(md5($str), 10, 10);
    }

    /**
     * 取得文件扩展名
     *
     * @param string $file 文件名
     * @return string
     */
    static function getExt(string $file): string
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * 创建文件夹
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    static function createFolders(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $mode, $recursive);
        }

        return true;
    }

    /**
     * 根据文件名创建文件
     *
     * @param string $fileName
     * @param int $mode
     * @param int $dirMode
     * @return bool
     */
    static function mkfile(string $fileName, int $mode = 0644, int $dirMode = 0755): bool
    {
        if (!file_exists($fileName)) {
            $filePath = dirname($fileName);
            $createFolder = self::createFolders($filePath, $dirMode, true);
            if ($createFolder) {
                $fp = fopen($fileName, 'w+');
                if ($fp) {
                    fclose($fp);
                    chmod($fileName, $mode);
                    return true;
                }
            }

            return false;
        }
        return true;
    }

    /**
     * 验证电子邮件格式
     *
     * @param string $email
     * @param string $addValidExpr
     * @return bool
     */
    static function validEmail(string $email, string $addValidExpr = "/^[a-zA-Z0-9]([\w\-\.]?)+/"): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            if ($addValidExpr) {
                list($validString,) = explode('@', $email);
                if (!preg_match($addValidExpr, $validString)) {
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
     * @throws Exception
     */
    static function random(int $length, int $numeric = 0): string
    {
        $seed = md5(microtime(true));
        if ($numeric) {
            $seed = str_replace('0', '', base_convert($seed, 16, 10)) . '0123456789';
        } else {
            $seed = base_convert($seed, 16, 35) . 'zZz' . strtoupper($seed);
        }

        $hash = '';
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed[random_int(0, $max)];
        }

        return $hash;
    }

    /**
     * 解析@到某某
     *
     * @param string $str
     * @return array
     */
    static function parseAt(string $str): array
    {
        preg_match_all("/@([^@^\\s^:]{1,})([\\s\\:\\,\\;]{0,1})/", $str, $result);
        return $result;
    }

    /**
     * 过滤非法标签
     *
     * @param string $str
     * @param string $disable
     * @return mixed
     */
    static function stripSelectedTags(string $str, string $disable = '<script><iframe><style><link>'): string
    {
        $disable = trim(str_replace(['>', '<'], ['', '|'], $disable), '|');
        $str = str_replace(['&lt;', '&gt;'], ['<', '>'], $str);
        $str = preg_replace("~<({$disable})[^>]*>(.*?<\s*\/(\\1)[^>]*>)?~is", '$2', $str);

        return $str;
    }

    /**
     * 转换html实体编码
     *
     * @param string $str
     * @return string
     */
    static function convertTags(string $str): string
    {
        return str_replace(['<', '>', "'", '"'], ['&lt;', '&gt;', '&#039;', '&quot;'], $str);
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
    static function authCode(string $string, string $operation = 'DECODE', string $key = 'crossphp', int $expiry = 0): string
    {
        $cKeyLength = 4;
        $key = md5($key);

        $key_a = md5(substr($key, 0, 16));
        $key_b = md5(substr($key, 16, 16));
        $key_c = $cKeyLength ? ($operation == 'DECODE' ? substr($string, 0, $cKeyLength) :
            substr(md5(microtime()), -$cKeyLength)) : '';

        $cryptKey = $key_a . md5($key_a . $key_c);
        $keyLength = strlen($cryptKey);

        $string = $operation == 'DECODE' ?
            base64_decode(substr($string, $cKeyLength)) :
            sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $key_b), 0, 16) . $string;

        $result = [];
        $box = range(0, 255);
        $stringLength = strlen($string);

        $rndKey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndKey[$i] = $cryptKey[$i % $keyLength];
        }
        $rndKey = array_map('ord', $rndKey);

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndKey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        $p1 = $p2 = [];
        for ($a = $j = $i = 0; $i < $stringLength; $i++) {
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

            unset($p1, $p2, $box, $tmp, $rndKey);
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
     * @param string $operation encode加密 其他任意字符解密
     * @return string
     */
    static function encodeParams(string $str, string $key, string $operation = 'encode'): string
    {
        $result = '';
        static $keyCache;
        if (!isset($keyCache[$key])) {
            $keyCache[$key] = md5($key);
        }

        $key = $keyCache[$key];
        if ($operation == 'encode') {
            $str = (string)$str;
        } else {
            //校验数据完整性
            //省略校验要解密的参数是否是一个16进制的字符串
            $strHead = substr($str, 0, 5);
            $str = substr($str, 5);
            if ($strHead != substr(md5($str . $key), 9, 5)) {
                return $result;
            }

            $str = pack('H*', $str);
        }

        if (!$str) {
            return $result;
        }

        for ($strLen = strlen($str), $i = 0; $i < $strLen; $i++) {
            $result .= chr(ord($str[$i]) ^ ord($key[$i % 32]));
        }

        if ($operation == 'encode') {
            $result = bin2hex($result);
            $result = substr(md5($result . $key), 9, 5) . $result;
        }

        return $result;
    }

    /**
     * 生成四层深度的路径
     * <pre>
     * 如 id = 31 拼成如下路径
     * 000/00/00/31
     * </pre>
     *
     * @param int $id
     * @param string $pathName
     * @return string
     */
    static function getPath(int $id, string $pathName = ''): string
    {
        $id = (string)abs($id);
        $id = str_pad($id, 9, '0', STR_PAD_LEFT);
        $dir1 = substr($id, 0, 3);
        $dir2 = substr($id, 3, 2);
        $dir3 = substr($id, 5, 2);

        return $pathName . '/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($id, -2) . '/';
    }

    /**
     * 发送一个curl请求
     *
     * @param string $url
     * @param array|string $vars
     * @param string $method
     * @param int $timeout
     * @param bool $CA
     * @param string $cacert http://curl.haxx.se/ca/cacert.pem
     * @return string
     * @throws CoreException
     */
    static function curlRequest(string $url, $vars = [], string $method = 'POST', int $timeout = 10, bool $CA = false, string $cacert = ''): string
    {
        $method = strtoupper($method);
        $SSL = substr($url, 0, 8) == 'https://';
        if ($method == 'GET' && !empty($vars)) {
            $params = is_array($vars) ? http_build_query($vars) : $vars;
            $url = rtrim($url, '?');
            if (false === strpos($url . $params, '?')) {
                $url = $url . '?' . ltrim($params, '&');
            } else {
                $url = $url . $params;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-HTTP-Method-Override: {$method}"]);

        if ($SSL && $CA && $cacert) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']); //避免data数据过长
        }
        $result = curl_exec($ch);
        $errorCode = curl_errno($ch);
        if (!empty($errorCode)) {
            throw new CoreException("curl请求失败({$errorCode})");
        }

        curl_close($ch);
        return $result;
    }

    /**
     * htmlspecialchars 函数包装
     *
     * @param string $str
     * @param int $quoteStyle
     * @return string
     */
    static function escape(string $str, int $quoteStyle = ENT_COMPAT): string
    {
        return htmlspecialchars($str, $quoteStyle);
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
     * @throws Exception
     */
    static function arrayRandomRate(array $array)
    {
        asort($array);
        $max = array_sum($array);
        foreach ($array as $aKey => $aValue) {
            $rand = random_int(0, $max);

            if ($rand <= $aValue) {
                return $aKey;
            } else {
                $max -= $aValue;
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
    static function isChinese(string $string): bool
    {
        if (preg_match("/^[\\x{4e00}-\\x{9fa5}]+$/u", $string)) {
            return true;
        }

        return false;
    }

    /**
     * 验证是否是一个正确的手机号
     *
     * @param string $mobile
     * @return bool
     */
    static function isMobile($mobile): bool
    {
        if (preg_match("/^1[3456789]\\d{9}$/", $mobile)) {
            return true;
        }

        return false;
    }

    /**
     * 校验身份证号码
     *
     * @param string $idCard
     * @param bool|true $justCheckLength 是否只校验长度
     * @return bool
     * @throws Exception
     */
    static function checkIDCard(string $idCard, bool $justCheckLength = true): bool
    {
        //长度校验
        $lengthValidate = preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $idCard) === 1;
        if ($justCheckLength) {
            return $lengthValidate;
        }

        if (!$lengthValidate) {
            return false;
        }

        $cityCode = [
            11 => true, 12 => true, 13 => true, 14 => true, 15 => true,
            21 => true, 22 => true, 23 => true,
            31 => true, 32 => true, 33 => true, 34 => true, 35 => true, 36 => true, 37 => true,
            41 => true, 42 => true, 43 => true, 44 => true, 45 => true, 46 => true,
            50 => true, 51 => true, 52 => true, 53 => true, 54 => true,
            61 => true, 62 => true, 63 => true, 64 => true, 65 => true,
            71 => true,
            81 => true, 82 => true,
            91 => true,
        ];

        //地区校验
        if (!isset($cityCode[$idCard[0] . $idCard[1]])) {
            return false;
        }

        //生成校验码
        $makeVerifyBit = function ($idCard) {
            if (strlen($idCard) != 17) {
                return null;
            }

            $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
            //校验码对应值
            $verifyNumberList = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
            $checksum = 0;
            for ($i = 0; $i < 17; $i++) {
                $checksum += $idCard[$i] * $factor[$i];
            }

            $mod = $checksum % 11;
            return $verifyNumberList[$mod];
        };

        $idCardLength = strlen($idCard);
        if ($idCardLength == 15) {
            //超出百岁特殊编码
            if (array_search(substr($idCard, 12, 3), ['996', '997', '998', '999']) !== false) {
                $idCard = substr($idCard, 0, 6) . '18' . substr($idCard, 6, 9);
            } else {
                $idCard = substr($idCard, 0, 6) . '19' . substr($idCard, 6, 9);
            }

            $idCard .= $makeVerifyBit($idCard);
        } else {
            //校验最后一位
            if (strcasecmp($idCard[17], $makeVerifyBit(substr($idCard, 0, 17))) != 0) {
                return false;
            }
        }

        //校验出生日期
        $birthDay = substr($idCard, 6, 8);
        $d = new DateTime($birthDay);
        if ($d->format('Y') > date('Y') || $d->format('m') > 12 || $d->format('d') > 31) {
            return false;
        }

        return true;
    }

    /**
     * 加解密
     *
     * @param string $data
     * @param string $op
     * @param string $key
     * @param string $method
     * @return bool|string
     */
    static function encrypt($data, string $op = 'DECODE', string $key = '!@#%c*r&o*s^s%p$h~p&', string $method = 'AES-256-CBC')
    {
        $encryptKey = md5($key);
        if ($op == 'ENCODE') {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            $encrypted = openssl_encrypt($data, $method, $encryptKey, 0, $iv);
            $result = str_replace(['=', '/', '+'], ['', '-', '_'], base64_encode($encrypted . '::' . $iv));
        } else {
            $data = base64_decode(str_replace(['-', '_'], ['/', '+'], $data));
            list($encrypted, $iv) = explode('::', $data);
            $result = openssl_decrypt($encrypted, $method, $encryptKey, 0, $iv);
        }

        return $result;
    }

    /**
     * 返回IP的整数形式
     *
     * @param string $ip
     * @return int
     */
    static function getLongIp(string $ip): int
    {
        return sprintf("%u", ip2long($ip));
    }

    /**
     * 显示友好时间格式
     *
     * @param int $time 时间戳
     * @param string $format
     * @param int $startTime
     * @param string $suffix
     * @return string
     */
    static function ftime(int $time, string $format = 'Y-m-d H:i:s', int $startTime = 0, string $suffix = '前'): string
    {
        if ($startTime == 0) {
            $startTime = time();
        }

        $t = $startTime - $time;
        if ($t < 63072000) {
            $f = [
                '31536000' => '年',
                '2592000' => '个月',
                '604800' => '星期',
                '86400' => '天',
                '3600' => '小时',
                '60' => '分钟',
                '1' => '秒'
            ];

            foreach ($f as $k => $v) {
                if (0 != $c = floor($t / (int)$k)) {
                    return $c . $v . $suffix;
                }
            }
        }

        return date($format, $time);
    }

    /**
     * 格式化数据大小(单位byte)
     *
     * @param int $size
     * @return string
     */
    static function convert(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        $s = floor(log($size, 1024));
        $i = (int)$s;

        if (isset($unit[$i])) {
            return sprintf('%.2f ' . $unit[$i], $size / pow(1024, $s));
        }

        return $size . ' ' . $unit[0];
    }
}
