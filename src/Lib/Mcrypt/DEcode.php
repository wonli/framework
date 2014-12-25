<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.0
 */
namespace Cross\Lib\Mcrypt;

/**
 * @Auth: wonli <wonli@live.com>
 * Class DEcode
 * @package Cross\Lib\Mcrypt
 */
abstract class DEcode
{
    /**
     * 编码函数
     *
     * @param $data
     * @return mixed
     */
    abstract function enCode($data);

    /**
     * 解码函数
     *
     * @param $data
     * @return mixed
     */
    abstract function deCode($data);

    /**
     * PKCS5 补码
     *
     * @param $text
     * @param $blocksize
     * @return string
     */
    function pkcs5_pad($text, $blocksize = 16)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * PKCS5解码
     *
     * @param $text
     * @return bool|string
     */
    function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;

        return substr($text, 0, -1 * $pad);
    }
}
