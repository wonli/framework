<?php defined('CROSSPHP_PATH')or die('Access Denied');
/*使用MCRYPT_BLOWFISH加密算法*/
class Mcrypt extends DEcode
{
    /**
     * iv
     *
     * @var string
     */
    private $iv;

    /**
     * HexCrypt
     *
     * @var HexCrypt
     */
    private $hexCrypt;

    /**
     * key
     *
     * @var string
     */
    private $key = "corssphp(*)9<>@$12v";

    /**
     * 初始化参数
     */
    function __construct ()
    {
        $this->iv = substr(md5($this->key), 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
        $this->hexCrypt = new HexCrypt ( );
    }

    /**
     * 加密
     *
     * @param $data
     * @return array
     */
    public function enCode ($data)
    {
        $s = mcrypt_cbc(MCRYPT_RIJNDAEL_128, $this->key, $data, MCRYPT_ENCRYPT, $this->iv);
        return $this->hexCrypt->EnCode($s);
    }

    /**
     * 解密
     *
     * @param $data
     * @return string
     */
    public function deCode ($data)
    {
        $s = $this->hexCrypt->DeCode($data);
        $str= mcrypt_cbc(MCRYPT_RIJNDAEL_128, $this->key, $s, MCRYPT_DECRYPT, $this->iv);
        return trim($str);
    }
}
