<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Lib\Upload;

use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Uploader
 * @package Cross\Lib\Upload
 */
class Uploader
{
    /**
     * 文件名
     *
     * @var string
     */
    protected $file;

    /**
     * 允许的文件类型
     *
     * @var array
     */
    protected $allowed_file_type;

    /**
     * 允许的文件大小
     *
     * @var int
     */
    protected $allowed_file_size;

    /**
     * 文件储存路径
     *
     * @var string
     */
    protected $save_dir;

    /**
     * 添加post上来的文件
     *
     * @param string $file 文件名
     * @throws CoreException
     */
    function addFile($file)
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new CoreException('无法识别的文件');
        }
        $this->file = $this->getUploadedInfo($file);
    }

    /**
     * 设定允许上传的文件类型
     *
     * @param string $type （小写）示例：gif|jpg|jpeg|png
     */
    function setAllowedType($type)
    {
        $this->allowed_file_type = explode('|', $type);
    }

    /**
     * 允许的大小
     *
     * @param int $size
     */
    function setAllowedSize($size)
    {
        $this->allowed_file_size = $size;
    }

    /**
     * 若没有指定root，则将会按照所指定的path来保存
     *
     * @param string $dir
     */
    function setSaveDir($dir)
    {
        $this->save_dir = $dir;
    }

    /**
     * 保存文件
     *
     * @param string $dir 文件路径
     * @param string $name 文件名
     * @return bool
     */
    function save($dir, $name = null)
    {
        if (!$this->file) {
            return false;
        }

        if (!$name) {
            $name = $this->file['filename'];
        } else {
            $name .= '.' . $this->file['extension'];
        }

        $path = trim($dir, '/') . '/' . $name;
        return $this->moveUploadedFile($this->file['tmp_name'], $path);
    }

    /**
     * 获取上传的文件的大小
     *
     * @param string $file
     * @throws CoreException
     * @return bool
     */
    private function getUploadedInfo($file)
    {
        $path_info = pathinfo($file['name']);
        $file['extension'] = $path_info['extension'];
        $file['filename'] = $path_info['basename'];

        if (!$path_info || !$this->isAllowedType($file['extension'])) {
            throw new CoreException('不支持的类型');
        }

        if (!$this->isAllowedSize($file['size'])) {
            throw new CoreException("文件大小超出限制 {$file['size']}");
        }

        return $file;
    }

    /**
     * 检查上传文件类型
     *
     * @param int $type
     * @return bool
     */
    private function isAllowedType($type)
    {
        if (!$this->allowed_file_type) {
            return true;
        }

        return in_array(strtolower($type), $this->allowed_file_type);
    }

    /**
     * 检查上传文件的大小
     *
     * @param int $size
     * @return bool
     */
    private function isAllowedSize($size)
    {
        if (!$this->allowed_file_size) {
            return true;
        }

        return is_numeric($this->allowed_file_size) ?
            ($size <= $this->allowed_file_size) :
            ($size >= $this->allowed_file_size[0] && $size <= $this->allowed_file_size[1]);
    }

    /**
     * 将上传的文件移动到指定的位置
     *
     * @param string $src
     * @param string $target
     * @throws CoreException
     * @return bool
     */
    private function moveUploadedFile($src, $target)
    {
        $abs_path = $this->save_dir ? trim($this->save_dir . '/') . $target : $target;
        $dir_name = dirname($abs_path);

        if (!file_exists($dir_name)) {
            if (!mkdir($dir_name, 0600, true)) {
                throw new CoreException('保存文件的目录不存在');
            }
        }

        if (move_uploaded_file($src, $abs_path)) {
            @chmod($abs_path, 0600);
            return $target;
        } else {
            return false;
        }
    }
}


