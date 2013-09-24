<?php defined('CROSSPHP_PATH')or die('Access Denied');
/*使用MCRYPT_BLOWFISH加密算法*/
class Mcrypt extends DEcode
{
    private $key;
    private $iv;
    private $hexCrypt;
    const key = "corssphp(*)9<>@$12v";

    function __construct ()
    {
        $this->key = self::key;
        //$this->iv = substr(md5($this->key), 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
        $this->iv="snsocrossphp2012";
        $this->hexCrypt = new HexCrypt ( );
    }

    public function enCode ($data)
    {
        $s = mcrypt_cbc(MCRYPT_RIJNDAEL_128, $this->key, $data, MCRYPT_ENCRYPT, $this->iv);
        return $this->hexCrypt->EnCode($s);
    }

    public function deCode ($data)
    {
        $s = $this->hexCrypt->DeCode($data);
        $str= mcrypt_cbc(MCRYPT_RIJNDAEL_128, $this->key, $s, MCRYPT_DECRYPT, $this->iv);
        return trim($str);
    }
}
