<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Upload\Filter;

use Cross\Lib\Upload\IFilter;

/**
 * 图片过滤
 *
 * @package lib\Upload\Filter
 * @author wonli <wonli@live.com>
 */
class Image implements IFilter
{

    /**
     * 最小宽度
     *
     * @var int
     */
    protected int $width;

    /**
     * 最小高度
     *
     * @var int
     */
    protected int $height;

    /**
     * 限定最大还是最小高宽 默认最小高宽
     *
     * @var string
     */
    protected string $minOrMax = 'min';

    /**
     * 图片宽度使用绝对值
     *
     * @var bool
     */
    protected bool $absoluteWidth = false;

    /**
     * 图片高度使用绝对值
     *
     * @var bool
     */
    protected bool $absoluteHeight = false;

    /**
     * 允许上传的文件类型
     *
     * @var array
     */
    protected array $imageType = ['jpg', 'png', 'jpeg', 'gif'];

    /**
     * 上传文件过滤
     *
     * @param mixed $file 文件信息
     * @param string $error
     * @return bool 成功返回true
     */
    function filter(mixed $file, string &$error = ''): bool
    {
        $tmpFile = &$file['tmp_name'];
        if (empty($tmpFile)) {
            $error = '没有找到临时文件';
            return false;
        }

        $imageInfo = @getimagesize($tmpFile);
        if (false === $imageInfo) {
            $error = '获取图片信息失败';
            return false;
        }

        $imageType = substr(strtolower(image_type_to_extension($imageInfo[2])), 1);
        if (!in_array($imageType, $this->imageType)) {
            $error = '不允许上传的文件类型: ' . $imageType;
            return false;
        }

        //不验证高宽
        if (!$this->width && !$this->height) {
            return true;
        }

        $width = &$imageInfo[0];
        $height = &$imageInfo[1];
        if ($this->absoluteWidth && $width != $this->width) {
            $error = '图片宽度限定: ' . $this->width . 'px';
            return false;
        }

        if ($this->absoluteHeight && $height != $this->height) {
            $error = '图片高度限定: ' . $this->height . 'px';
            return false;
        }

        if ($this->minOrMax === 'min') {
            if ($width < $this->width) {
                $error = '图片宽度小于: ' . $this->width . 'px';
                return false;
            } elseif ($height < $this->height) {
                $error = '图片高度小于: ' . $this->width . 'px';
                return false;
            }
        } else {
            if ($width > $this->width) {
                $error = '图片宽度不能大于: ' . $this->width . 'px';
                return false;
            } elseif ($height > $this->height) {
                $error = '图片高度不能大于: ' . $this->height . 'px';
                return false;
            }
        }

        return true;
    }

    /**
     * 设置允许上传的图片类型
     *
     * @param string $fileType
     * @return $this
     */
    function fileType(string $fileType): static
    {
        $this->imageType = explode('|', strtolower($fileType));
        return $this;
    }

    /**
     * 宽度相等通过验证
     *
     * @return $this
     */
    function useAbsoluteWidth(): static
    {
        $this->absoluteWidth = true;
        return $this;
    }

    /**
     * 高度相等通过验证
     *
     * @return $this
     */
    function useAbsoluteHeight(): static
    {
        $this->absoluteHeight = true;
        return $this;
    }

    /**
     * 设置图片高宽
     *
     * @param int $width
     * @param int $height
     * @param string $minOrMax 限定最小还是最大宽度
     * @return Image
     */
    function setWidthHeight(int $width, int $height, string $minOrMax = 'min'): static
    {
        $this->width = (int)$width;
        $this->height = (int)$height;
        $this->minOrMax = (0 === strcasecmp($minOrMax, 'min')) ? 'min' : 'max';
        return $this;
    }
}