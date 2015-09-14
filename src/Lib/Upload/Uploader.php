<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
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
    protected $_file = null;

    /**
     * 允许的文件类型
     *
     * @var array
     */
    protected $_allowed_file_type = null;

    /**
     * 允许的文件大小
     *
     * @var int
     */
    protected $_allowed_file_size = null;

    /**
     * 文件储存路径
     *
     * @var string
     */
    protected $_root_dir;

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
        $this->_file = $this->_get_uploaded_info($file);
    }

    /**
     * 设定允许上传的文件类型
     *
     * @param int $type （小写）示例：gif|jpg|jpeg|png
     */
    function allowed_type($type)
    {
        $this->_allowed_file_type = explode('|', $type);
    }

    /**
     * 允许的大小
     *
     * @param int $size
     */
    function allowed_size($size)
    {
        $this->_allowed_file_size = $size;
    }

    /**
     * 获取上传的文件的大小
     *
     * @param string $file
     * @throws CoreException
     * @return bool
     */
    function _get_uploaded_info($file)
    {
        $path_info = pathinfo($file['name']);
        $file['extension'] = $path_info['extension'];
        $file['filename'] = $path_info['basename'];

        if (!$path_info || !$this->_is_allowed_type($file['extension'])) {
            throw new CoreException('不支持的类型');
        }

        if (!$this->_is_allowed_size($file['size'])) {
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
    private function _is_allowed_type($type)
    {
        if (!$this->_allowed_file_type) {
            return true;
        }

        return in_array(strtolower($type), $this->_allowed_file_type);
    }

    /**
     * 检查上传文件的大小
     *
     * @param int $size
     * @return bool
     */
    private function _is_allowed_size($size)
    {
        if (!$this->_allowed_file_size) {
            return true;
        }

        return is_numeric($this->_allowed_file_size) ?
            ($size <= $this->_allowed_file_size) :
            ($size >= $this->_allowed_file_size[0] && $size <= $this->_allowed_file_size[1]);
    }

    /**
     * 获取上传文件的信息
     *
     * @return string
     */
    function file_info()
    {
        return $this->_file;
    }

    /**
     * 若没有指定root，则将会按照所指定的path来保存
     *
     * @param string $dir
     */
    function root_dir($dir)
    {
        $this->_root_dir = $dir;
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
        if (!$this->_file) {
            return false;
        }

        if (!$name) {
            $name = $this->_file['filename'];
        } else {
            $name .= '.' . $this->_file['extension'];
        }

        $path = trim($dir, '/') . '/' . $name;
        return $this->move_uploaded_file($this->_file['tmp_name'], $path);
    }

    /**
     * 将上传的文件移动到指定的位置
     *
     * @param string $src
     * @param string $target
     * @throws CoreException
     * @return bool
     */
    function move_uploaded_file($src, $target)
    {
        $abs_path = $this->_root_dir ? trim($this->_root_dir . '/') . $target : $target;
        $dir_name = dirname($abs_path);

        if (!file_exists($dir_name)) {
            if (!mkdir($dir_name, 0666, true)) {
                throw new CoreException('保存文件的目录不存在');
            }
        }

        if (move_uploaded_file($src, $abs_path)) {
            @chmod($abs_path, 0666);
            return $target;
        } else {
            return false;
        }
    }
}


