<?php
/**
 * @Auth crossphp 优化
 * Class Captcha 验证码
 */
namespace cross\lib\images;

use cross\exception\CoreException;

class Captcha
{
    /**
     * 宽
     *
     * @var int
     */
    public $width;

    /**
     * 高
     *
     * @var int
     */
    public $height;

    /**
     * 图片资源
     *
     * @var resource
     */
    public $img;

    /**
     * 图片类型
     *
     * @var string
     */
    public $imgType;

    /**
     * 文字
     *
     * @var string
     */
    public $checkCode;

    /**
     * 验证码类型
     *
     * @var int
     */
    public $codeType;

    /**
     * 文字个数
     *
     * @var int
     */
    public $num;

    /**
     * 构造方法，初使化各个成员属性包括根据文字类型，产生文字
     *
     * @param int $num
     * @param int $width
     * @param int $height
     * @param string $imgType
     */
    public function __construct($num = 4, $width = 120, $height = 40, $imgType = 'jpeg')
    {
        $this->width = $width;
        $this->height = $height;
        $this->imgType = $imgType;
        $this->num = $num;
    }

    /**
     * 外部设置code
     *
     * @param $code
     * @param string $type
     */
    public function setCheckCode($code, $type = "en")
    {
        $this->checkCode = $code;
        $this->codeType = $type;
    }

    /**
     * 获取要产生的文字
     *
     * @return Array
     * @throws CoreException
     */
    private function getCheckCode()
    {
        if ($this->checkCode) {
            if ($this->codeType == "cn") {
                $this->checkCode = Helper::str_split($this->checkCode);
            }

            return $this->checkCode;
        }
        else throw new CoreException("error captcha code!");
    }

    /**
     * 创建临时图片
     */
    protected function createImg()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
    }

    /**
     * 产生背景颜色,颜色值越大，越浅，越小越深
     *
     * @return int
     */
    protected function bgColor()
    {
        return imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
    }

    /**
     * 字的颜色
     *
     * @return int
     */
    protected function fontColor()
    {
        return imagecolorallocate($this->img, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120));
    }

    /**
     * 填充背景颜色
     */
    protected function filledColor()
    {
        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $this->bgColor());
    }

    /**
     * 画上干扰点
     */
    protected function pix()
    {
        for ($i = 0; $i < 60; $i++) {
            imagesetpixel($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), $this->fontColor());
        }
    }

    /**
     * 画上干扰线
     */
    protected function arc()
    {
        for ($i = 0; $i < 5; $i++) {
            imagearc($this->img, mt_rand(10, $this->width - 10), mt_rand(10, $this->height - 10),
                200, 50, mt_rand(0, 90), mt_rand(100, 390), $this->fontColor());
        }
    }

    /**
     * 写字
     */
    protected function write()
    {
        if (!$this->checkCode) {
            $this->getCheckCode();
        }

        for ($i = 0; $i < $this->num; $i++) {
            $x = ceil($this->width / $this->num) * $i + 5;
            $y = mt_rand(5, $this->height - 25);
            imagechar($this->img, 5, $x, $y, $this->checkCode[$i], $this->fontColor());
            /**
             * if($this->codeType == "cn") {
             * $angle=mt_rand(-5,1)*mt_rand(1,5);
             * imagettftext($this->img,16,$angle,5+$i*floor(16*1.8),floor($this->height*0.75),$this->fontColor(),$front, $this->checkCode[$i]);
             * } else {
             * }
             */
        }
    }

    /**
     * 输出图片
     */
    protected function output()
    {
        $func = 'image' . $this->imgType;

        if (function_exists($func)) {
            header("Content-type:image/{$this->imgType}");
            $func($this->img);
        }
        else {
            echo '不支持该图片类型';
            exit;
        }
    }

    /**
     * 组装得到图片
     */
    public function getImage()
    {
        $this->createImg();
        $this->filledColor();
        $this->pix();
        $this->arc();
        $this->write();
        $this->output();
    }

    /**
     * 销毁内存中的临时图片
     */
    public function __destruct()
    {
        if (!empty($this->img)) {
            imagedestroy($this->img);
        }
    }
}
