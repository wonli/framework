<?php
namespace Cross\Lib\Images;

use cross\exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class ImageThumb
 * @package Cross\Lib\Images
 */
class ImageThumb
{
    /**
     * 剪切后图片宽度
     *
     * @var int
     */
    protected $width;

    /**
     * 剪切后图片高度
     *
     * @var int
     */
    protected $height;

    /**
     * 文件路径
     *
     * @var string
     */
    protected $save_dir;

    /**
     * 原文件路径
     *
     * @var string
     */
    protected $src_images;

    /**
     * 缩略图文件名
     *
     * @var string
     */
    protected $thumb_image_name;

    function __construct($src_images)
    {
        $this->src_images = $src_images;
    }

    /**
     * 设置文件路径和文件名
     *
     * @param $dir
     * @param $thumb_image_name
     * @return $this
     */
    function setFile($dir, $thumb_image_name)
    {
        $this->save_dir = $dir;
        $this->thumb_image_name = $thumb_image_name;

        return $this;
    }

    /**
     * 设置高宽
     *
     * @param int $width
     * @param int $height
     * @return $this
     */
    function setSize($width = 0, $height = 0)
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
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
     * 生成缩略图
     *
     * @param bool $interlace
     * @param bool $return_full_path
     * @param int $quality
     * @return bool|string
     * @throws CoreException
     */
    function thumb($interlace = true, $return_full_path = false, $quality = 100)
    {
        if (!$this->save_dir || !$this->src_images) {
            throw new CoreException('请设置文件路径和文件名');
        }

        // 获取原图信息
        $info = $this->getImageInfo($this->src_images);
        if (!$info) {
            return false;
        }

        $src_width = $info['width'];
        $src_height = $info['height'];
        $type = strtolower($info['file_type']);
        $file_ext = strtolower($info['ext']);
        $thumb_file_name = $this->thumb_image_name . $file_ext;
        unset($info);

        $scale = max($this->width / $src_width, $this->height / $src_height);
        if ($scale >= 1) {
            $width = $src_width;
            $height = $src_height;
        } else {
            $width = round($src_width * $scale);
            $height = round($src_height * $scale);
        }

        //载入原图
        $src_images = $this->createImage($this->src_images, $type);

        //创建缩略图
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $thumb_images = imagecreatetruecolor($width, $height);
        } else {
            $thumb_images = imagecreate($width, $height);
        }

        // 复制图片
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($thumb_images, $src_images, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
        } else {
            imagecopyresized($thumb_images, $src_images, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
        }

        if ('gif' == $type || 'png' == $type) {
            $background_color = imagecolorallocate($thumb_images, 0, 255, 0); //指派一个绿色
            imagecolortransparent($thumb_images, $background_color); //设置为透明色，若注释掉该行则输出绿色的图
        }

        // 对jpeg图形设置隔行扫描
        if ('jpg' == $type || 'jpeg' == $type) {
            imageinterlace($thumb_images, (int)$interlace);
        }

        //返回缩略图的路径，字符串
        $save_path = $this->save_dir . $thumb_file_name;
        $this->saveImage($thumb_images, $save_path, $type, $quality);
        imagedestroy($thumb_images);
        imagedestroy($src_images);

        if ($return_full_path) {
            return $save_path;
        }

        return $thumb_file_name;
    }

    /**
     * 创建图片
     *
     * @param $image
     * @param $image_type
     * @return resource
     */
    protected function createImage($image, $image_type)
    {
        switch ($image_type) {
            case 'jpg':
            case 'jpeg':
            case 'pjpeg':
                $res = imagecreatefromjpeg($image);
                break;

            case 'gif':
                $res = imagecreatefromgif($image);
                break;

            case 'png':
                $res = imagecreatefrompng($image);
                break;

            case 'bmp':
                $res = imagecreatefromwbmp($image);
                break;

            default:
                $res = imagecreatefromgd2($image);
                break;
        }

        return $res;
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

}


