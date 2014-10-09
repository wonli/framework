<?php
/**
 * @Auth crossphp 优化
 * Class Captcha 验证码
 */
namespace Cross\Lib\Images;

use Cross\Core\Helper;
use Cross\Exception\CoreException;

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
    public $fontFamily;

    /**
     * 字体大小
     *
     * @var int
     */
    public $fontSize;

    /**
     * 文字个数
     *
     * @var int
     */
    public $num;

    /**
     * 构造方法，初使化各个成员属性包括根据文字类型，产生文字
     *
     * @param int $width
     * @param int $height
     * @param int $fontSize
     * @param string $imgType
     */
    public function __construct($width = 120, $height = 40, $fontSize = 20, $imgType = 'jpeg')
    {
        $this->width = $width;
        $this->height = $height;
        $this->imgType = $imgType;
        $this->fontSize = $fontSize;
    }

    /**
     * 外部设置code
     *
     * @param string $code
     * @param string $fontFamily
     */
    public function setCheckCode($code, $fontFamily = "")
    {
        $this->checkCode = $code;
        $this->fontFamily = $fontFamily;
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
            $ret = array();
            $this->num = Helper::strLen($this->checkCode);
            for ($i = 0; $i < $this->num; $i++) {
                $ret[] = mb_substr($this->checkCode, $i, 1, "UTF-8");
            }

            return $ret;
        } else {
            throw new CoreException("error captcha code!");
        }
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
        return imagecolorallocate($this->img, mt_rand(230, 255), mt_rand(230, 255), mt_rand(230, 255));
    }

    /**
     * 字的颜色
     *
     * @return int
     */
    protected function fontColor()
    {
        return imagecolorallocate($this->img, mt_rand(0, 90), mt_rand(0, 90), mt_rand(0, 90));
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
        for ($i = 0; $i < 10; $i++) {
            imagesetpixel($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), $this->fontColor());
        }
    }

    /**
     * 画上干扰线
     */
    protected function arc()
    {
        for ($i = 0; $i < 5; $i++) {
            imagearc(
                $this->img,
                mt_rand(10, $this->width - 10),
                mt_rand(10, $this->height - 10),
                200,
                50,
                mt_rand(0, 90),
                mt_rand(100, 390),
                $this->fontColor()
            );
        }
    }

    /**
     * 写字
     */
    protected function write()
    {
        $checkCode = $this->getCheckCode();
        $y_base = floor($this->height * 0.75);
        $x_base = ceil($this->width / $this->num);

        for ($i = 0; $i < $this->num; $i++) {
            $x = $x_base * $i + 10;
            $fontSize = mt_rand($this->fontSize - 5, $this->fontSize + 5);

            if ($this->fontFamily) {
                $y = mt_rand($y_base - 5, $y_base + 5);
                $angle = mt_rand(-5, 5) * mt_rand(1, 5);
                imagettftext(
                    $this->img,
                    $fontSize,
                    $angle,
                    $x,
                    $y,
                    $this->fontColor(),
                    $this->fontFamily,
                    $checkCode[$i]
                );
            } else {
                $y = mt_rand(5, $y_base);
                imagechar($this->img, 5, $x, $y, $checkCode[$i], $this->fontColor());
            }
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
            exit;
        } else {
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
