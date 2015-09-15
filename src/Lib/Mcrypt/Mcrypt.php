<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Lib\Mcrypt;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Mcrypt
 * @package Cross\Lib\Mcrypt
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
     * 是否解码
     *
     * @var bool
     */
    private $isDecode = true;

    /**
     * 加密/解密的字符串是否包含iv前16个字节
     *
     * @var bool
     */
    private $isContainIV = true;

    /**
     * key
     *
     * @var string
     */
    private $default_key = 'corssphp(*)9<>@$12v';

    /**
     * @var string
     */
    private $key;

    /**
     * 初始化参数
     */
    function __construct()
    {
        if ($this->isDecode) {
            $this->hexCrypt = new HexCrypt ();
        }
    }

    /**
     * 加密
     *
     * @param $data
     * @return array
     */
    public function enCode($data)
    {
        $key = $this->getKey();
        $iv = $this->getIV();
        $s = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $this->pkcs5_pad($data), MCRYPT_MODE_CBC, $iv);
        if ($this->isContainIV) {
            $s = $iv . $s;
        }

        if ($this->isDecode && $this->hexCrypt) {
            return $this->hexCrypt->EnCode($s);
        }

        return $s;
    }

    /**
     * 解密
     *
     * @param $data
     * @return string
     */
    public function deCode($data)
    {
        $key = $this->getKey();

        if ($this->isDecode && $this->hexCrypt) {
            $data = $this->hexCrypt->DeCode($data);
        }

        if ($this->isContainIV) {
            $iv = substr($data, 0, 16);
            $data = substr($data, 16);
        }
        else {
            $iv = $this->getIV();
        }

        $str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);

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
     * 设置用于加解密的key
     *
     * @param $key
     * @return $this
     */
    function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * 设置IV
     *
     * @param $iv
     * @return $this
     */
    function setIV($iv)
    {
        $this->iv = $iv;

        return $this;
    }

    /**
     * 加解密是否需要先解码
     *
     * @param $isDecode
     * @return $this
     */
    function isDecode($isDecode)
    {
        $this->isDecode = $isDecode;

        return $this;
    }

    /**
     * 设置加解密是否包含iv
     *
     * @param $isContainIV
     * @return $this
     */
    function isContainIV($isContainIV)
    {
        $this->isContainIV = $isContainIV;

        return $this;
    }

    /**
     * 获取IV
     */
    function getIV()
    {
        if (empty($this->iv)) {
            $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
            $this->setIV($iv);
        }

        return $this->iv;
    }
}
