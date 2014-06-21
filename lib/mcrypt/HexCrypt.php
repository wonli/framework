<?php
/*16进制编码方式利用0-f进行编码*/
class HexCrypt extends DEcode
{

    public function __construct ()
    {

    }

    /**
     * 加密
     *
     * @param $data
     * @return array
     */
    public function enCode ($data)
    {
        //return @unpack('H*', $data);
        return bin2hex($data);
    }

    /**
     * 解密
     *
     * @param $data
     * @return string
     */
    public function deCode ($data)
    {
        return @pack('H*', $data);
    }
}
