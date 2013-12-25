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
    public function EnCode ($data)
    {
        // bin2hex($data);
        return @unpack('H*', $data);
    }

    /**
     * 解密
     *
     * @param $data
     * @return string
     */
    public function DeCode ($data)
    {
        return @pack('H*', $data);
    }
}
