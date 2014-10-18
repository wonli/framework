<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.4
 */
namespace Cross\Lib\Mcrypt;

/**
 * @Auth: wonli <wonli@live.com>
 * Class HexCrypt
 * @package Cross\Lib\Mcrypt
 */
class HexCrypt extends DEcode
{
    public function __construct()
    {

    }

    /**
     * 加密
     *
     * @param $data
     * @return array
     */
    public function enCode($data)
    {
        return bin2hex($data);
    }

    /**
     * 解密
     *
     * @param $data
     * @return string
     */
    public function deCode($data)
    {
        return @pack('H*', $data);
    }
}
