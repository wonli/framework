<?php
/**
 * DES ECB
 *
 * @Auth: wonli <wonli@live.com>
 * Class DESMcrypt
 */
class DESMcrypt extends DEcode
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var HexCrypt
     */
    protected $hexCrypt;

    /**
     * 设置key
     *
     * @param $key
     */
    function __construct($key)
    {
		$this->key = $key;
        $this->hexCrypt = new HexCrypt ( );
	}

    /**
     * @param $input
     * @return string
     */
    public function enCode ($input)
    {
		$size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);
		$input = $this->pkcs5_pad($input, $size);
		$key = $this->key;
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		@mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = $this->hexCrypt->EnCode($data);
		return $data;
	}

    /**
     * @param $encrypted
     * @return bool|string
     */
    public function deCode ($encrypted) {
		$encrypted = $this->hexCrypt->DeCode($encrypted);
		$key =$this->key;
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,ecb模式
		$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size($td);
		@mcrypt_generic_init($td, $key, $iv); //初始处理
		$decrypted = mdecrypt_generic($td, $encrypted); //解密
		mcrypt_generic_deinit($td); //结束
		mcrypt_module_close($td);
		$y=$this->pkcs5_unpad($decrypted);
		return $y;
	}
}

