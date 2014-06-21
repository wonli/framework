<?php
/**
 * AES CBC
 *
 * @Auth: wonli <wonli@live.com>
 * Class Mcrypt
 */
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
    private $default_key = "corssphp(*)9<>@$12v";

    /**
     * @var string
     */
    private $key;

    /**
     * 初始化参数
     */
    function __construct ()
    {
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
        $key = $this->getKey();
        $iv = $this->getIV();

        $s = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $this->pkcs5_pad($data), MCRYPT_MODE_CBC, $iv);
        return $this->hexCrypt->EnCode($this->iv.$s);
    }

    /**
     * 解密
     *
     * @param $data
     * @return string
     */
    public function deCode ($data)
    {
        $key = $this->getKey();
        $s = $this->hexCrypt->DeCode($data);
        $iv = substr($s, 0, 16);
        $str= mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, substr($s, 16), MCRYPT_MODE_CBC, $iv);
        return $this->pkcs5_unpad($str);
    }

    /**
     * 获取key
     *
     * @return string
     */
    function getKey()
    {
        if (! $this->key) {
            return md5($this->default_key);
        }

        return $this->key;
    }

    /**
     * 设置key
     *
     * @param $key
     */
    function setKey( $key )
    {
        $this->key = $key;
    }

    /**
     * 设置IV
     */
    function setIV($iv)
    {
        $this->iv = $iv;
    }

    /**
     * 获取IV
     */
    function getIV()
    {
        if (empty($this->iv))
        {
            $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
            $this->setIV($iv);
        }

        return $this->iv;
    }
}
