<?php
namespace Cross\Lib\Images;

use Exception;

class ImageCut
{
    /**
     * 临时创建的图象
     *
     * @var resource
     */
    private $im;

    /**
     * 图片类型
     *
     * @var string
     */
    private $type;

    /**
     * 实际宽度
     *
     * @var int
     */
    private $width;

    /**
     * 实际高度
     *
     * @var int
     */
    private $height;

    /**
     * 原始图片
     *
     * @var string
     */
    protected $src_images;

    /**
     * 图片保存路径
     *
     * @var string
     */
    protected $save_path;

    /**
     * 图片保存名
     *
     * @var string
     */
    protected $save_name;

    /**
     * 原始图片信息
     *
     * @var array|bool
     */
    protected $images_info;

    /**
     * @var int
     */
    private $resize_width;

    /**
     * @var int
     */
    private $resize_height;

    function __construct($src_images)
    {
        $this->src_images = $src_images;
        $this->images_info = $this->getImageInfo($src_images);
        $this->type = $this->images_info['file_type'];

        //初始化图象
        $this->createImageResource();

        //目标图象地址
        $this->width = $this->images_info['width'];
        $this->height = $this->images_info['height'];
    }

    /**
     * 设置保存路径
     *
     * @param $path
     * @param $name
     * @return $this
     */
    function setSaveInfo($path, $name)
    {
        $this->save_path = $path;
        $this->save_name = $name;

        return $this;
    }

    /**
     * 设置剪切大小
     *
     * @param $width
     * @param $height
     * @return $this
     */
    function setCutSize($width, $height)
    {
        $this->resize_width = $width;
        $this->resize_height = $height;

        return $this;
    }

    /**
     * 剪切图象
     *
     * @param $coordinate
     * @param bool $return_path
     * @return string
     * @throws Exception
     */
    function cut($coordinate, $return_path = false)
    {
        if (!isset($coordinate['x']) || !isset($coordinate['y']) ||
            !isset($coordinate['w']) || !isset($coordinate['h'])
        ) {
            throw new Exception('请设置剪切坐标x, y, w, h');
        }

        $save_path = $this->getSavePath();

        //改变后的图象的比例
        if (!empty($this->resize_height)) {
            $resize_ratio = ($this->width) / ($this->height);
        } else {
            $resize_ratio = 0;
        }

        //实际图象的比例
        $ratio = ($this->width) / ($this->height);

        if ($ratio >= $resize_ratio) //高度优先
        {
            $thumb_images_width = $this->height * $resize_ratio;
            $thumb_images_height = $this->height;
        } else {
            $thumb_images_width = $this->width;
            $thumb_images_height = $this->width / $resize_ratio;
        }

        //创建缩略图
        if ($this->images_info['file_type'] != 'gif' && function_exists('imagecreatetruecolor')) {
            $thumb_images = imagecreatetruecolor($this->width, $this->height);
        } else {
            $thumb_images = imagecreate($this->width, $this->height);
        }

        imagecopyresampled(
            $thumb_images,
            $this->im,
            0,
            0,
            $coordinate['x'],
            $coordinate['y'],
            $thumb_images_width,
            $thumb_images_height,
            $coordinate['w'],
            $coordinate['h']
        );

        $this->saveImage($thumb_images, $save_path, $this->images_info['file_type'], 100);
        if (true === $return_path) {
            return $save_path;
        }

        return $this->save_name;
    }

    /**
     * 获取图片详细信息
     *
     * @param $images
     * @return array|bool
     */
    protected function getImageInfo($images)
    {
        $image_info = getimagesize($images);
        if (false !== $image_info) {
            $image_ext = strtolower(image_type_to_extension($image_info[2]));
            $image_type = substr($image_ext, 1);
            $image_size = filesize($images);

            $info = array(
                'width' => $image_info[0],
                'height' => $image_info[1],
                'ext' => $image_ext,
                'file_type' => $image_type,
                'size' => $image_size,
                'mime' => $image_info['mime'],
            );

            return $info;
        } else {
            return false;
        }
    }

    /**
     * 创建临时图象
     */
    private function createImageResource()
    {
        switch ($this->type) {
            case 'jpg':
            case 'jpeg':
            case 'pjpeg':
                $this->im = imagecreatefromjpeg($this->src_images);
                break;

            case 'gif':
                $this->im = imagecreatefromgif($this->src_images);
                break;

            case 'png':
                $this->im = imagecreatefrompng($this->src_images);
                break;

            case 'bmp':
                $this->im = imagecreatefromwbmp($this->src_images);
                break;

            default:
                $this->im = imagecreatefromgd2($this->src_images);
                break;
        }
    }

    /**
     * 存储图片
     *
     * @param $resource
     * @param $save_path
     * @param $image_type
     * @param int $quality
     * @return bool
     */
    protected function saveImage($resource, $save_path, $image_type, $quality = 100)
    {
        switch ($image_type) {
            case 'jpg':
            case 'jpeg':
            case 'pjpeg':
                $ret = imagejpeg($resource, $save_path, $quality);
                break;

            case 'gif':
                $ret = imagegif($resource, $save_path);
                break;

            case 'png':
                $ret = imagepng($resource, $save_path);
                break;

            default:
                $ret = imagegd2($resource, $save_path);
                break;
        }

        return $ret;
    }

    /**
     * 图象目标地址
     *
     * @throws Exception
     * @return string
     */
    protected function getSavePath()
    {
        $name = $this->getSaveName();
        if (!$name) {
            throw new Exception('请设置缩略图名称');
        }

        $path = $this->getSaveDir();
        if (!$path || !is_dir($path)) {
            throw new Exception('请设置路径');
        }

        return $path . $name . $this->images_info['ext'];
    }

    /**
     * 获取文件名
     *
     * @return string
     */
    private function getSaveName()
    {
        return $this->save_name;
    }

    /**
     * 获取文件保存文件夹
     *
     * @return string
     */
    private function getSaveDir()
    {
        return $this->save_path;
    }
}
