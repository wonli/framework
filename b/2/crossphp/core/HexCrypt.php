<?php defined('CROSSPHP_PATH')or die('Access Denied');
/*16进制编码方式利用0-f进行编码*/
class HexCrypt extends DEcode
{

    public function __construct ()
    {

    }
    
    public function EnCode ($data)
    {
        // bin2hex($data);
        return @unpack('H*', $data); 
    }
    
    public function DeCode ($data)
    {
        return @pack('H*', $data);
    }
}