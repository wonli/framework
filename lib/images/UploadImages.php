<?php
/**
 * @Auth: wonli <wonli@live.com>
 * UploadImages.php
 */
namespace cross\lib\images;

use Exception;

class UploadImages
{
    /**
     * 保存的文件名
     *
     * @var string
     */
    protected $save_name;

    /**
     * 文件保存路径
     *
     * @var string
     */
    protected $save_path = '';

    /**
     * 要上传处理的文件名
     *
     * @var string
     */
    protected $upload_file_name;

    /**
     * 要上传处理的文件的基础信息
     *
     * @var array
     */
    protected $upload_file_base_info;

    /**
     * 返回的消息内容
     *
     * @var string
     */
    protected $status_message = '';

    /**
     * 限制文件上传的大小
     *
     * @var int
     */
    protected $allow_size = 2097152;

    /**
     * 允许上传的文件类型
     *
     * @var array
     */
    protected $allow_type = array(
        'gif', 'jpg', 'png', 'jpeg'
    );

    /**
     * 状态码
     *
     * @var array
     */
    protected $status_code = array(
        -8 => '文件上传失败',
        -4 => '大小超出限制',
        -3 => '不允许上传的类型',
        0 => '上传成功',
        1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
        2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
        3 => '文件只有部分被上传',
        4 => '没有文件被上传',
        6 => '找不到临时文件夹',
        7 => '文件写入失败',
    );

    function __construct($upload_file_name, $save_name = '')
    {
        if (empty($upload_file_name)) {
            return $this->result(-1);
        }

        if ('' === $save_name) {
            $save_name = $upload_file_name;
        }

        $this->save_name = $save_name;
        $this->upload_file_name = $upload_file_name;
    }

    /**
     * 设置文件上传大小
     *
     * @param $size
     * @return $this
     */
    protected function set_allow_size($size)
    {
        $this->allow_size = $size;

        return $this;
    }

    /**
     * 返回文件大小
     *
     * @return int
     */
    protected function get_allow_size()
    {
        return $this->allow_size;
    }

    /**
     * 设置保存路径
     *
     * @param string $path
     * @return $this
     */
    public function set_save_path($path = '')
    {
        $this->save_path = $path;

        return $this;
    }

    /**
     * 获取文件的保存路径
     *
     * @return string
     */
    protected function get_save_path()
    {
        if (!empty($this->save_path) && !is_dir($this->save_path)) {
            mkdir($this->save_path, 755, true);
        }

        return rtrim($this->save_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取允许上传的文件类型
     *
     * @return array
     */
    protected function get_allow_type()
    {
        return $this->allow_type;
    }

    /**
     * 设置允许上传的文件类型
     *
     * @param array $type
     * @return $this
     */
    public function set_allow_type(array $type)
    {
        $this->allow_type = $type;

        return $this;
    }

    /**
     * 获取要上传文件的基础信息
     *
     * @throws Exception
     * @return array
     */
    protected function get_base_info()
    {
        if (!$this->upload_file_base_info) {
            if (isset($_FILES[$this->upload_file_name])) {
                $base_info = $_FILES[$this->upload_file_name];
                if ($base_info['error'] !== 0) {
                    throw new Exception($base_info['error']);
                }
                else {
                    $extend_info = $this->get_image_info($base_info ['tmp_name']);
                    if (!empty($extend_info)) {
                        $base_info = array_merge($base_info, $extend_info);
                    }
                }
                $this->set_base_info($base_info);
            }
        }

        return $this->upload_file_base_info;
    }

    /**
     * 返回上传文件的基础信息
     *
     * @param $upload_file_base_info
     */
    protected function set_base_info($upload_file_base_info)
    {
        $this->upload_file_base_info = $upload_file_base_info;
    }

    /**
     * 保存文件
     *
     * @return string
     */
    protected function get_save_file_name()
    {
        $base_file = $this->get_base_info();

        return $this->save_name . $base_file['ext'];
    }

    /**
     * 检查上传文件
     *
     * @return bool|int
     */
    protected function check()
    {
        $file_ret = $this->check_file_type();
        if (true !== $file_ret) {
            return $file_ret;
        }

        $size_ret = $this->check_file_size();
        if (true !== $size_ret) {
            return $size_ret;
        }

        return true;
    }

    /**
     * 移动上传的文件到指定目录
     *
     * @return array
     */
    protected function move_upload_file()
    {
        try {
            $base_info = $this->get_base_info();
        } catch (Exception $e) {
            return $this->result($e->getMessage());
        }

        $check_ret = $this->check();
        if (true !== $check_ret) {
            return $this->result($check_ret);
        }
        else {
            $saved_file_path = $this->get_save_file_full_path();
            if (move_uploaded_file($base_info['tmp_name'], $saved_file_path)) {
                return $this->result(0, $saved_file_path);
            }
        }

        return $this->result(-8);
    }

    /**
     * 保存图片
     */
    public function save()
    {
        $ret = $this->move_upload_file();
        if ($ret['status'] === 0) {
            return $this->result('ok', array(
                'url' => $this->get_save_file_name(),
                'path' => $ret['message'],
            ));
        }

        return $this->result($ret['status']);
    }

    /**
     * 生成缩略图
     *
     * @param array $thumb_config
     * @param bool $save_ori_images
     * @return array
     */
    public function thumb($thumb_config = array(), $save_ori_images = true)
    {
        $ret = $this->move_upload_file();
        if ($ret['status'] === 0) {
            $result_file_url['ori'] = $this->get_save_file_name();
            //生成缩略图
            if (!empty($thumb_config)) {
                $ori_images_path = $ret['message'];
                if (!is_array($thumb_config)) {
                    $thumb_config = array($thumb_config);
                }

                $result_file_url['thumb'] = $this->make_thumb($ori_images_path, $thumb_config);
                if (!$save_ori_images) {
                    unlink($ori_images_path);
                    unset($result_file_url['ori']);
                }
            }

            return $this->result('ok', $result_file_url);
        }

        return $this->result($ret['status']);
    }

    /**
     * 剪切图片
     *
     * @param $thumb_config
     * @param array $coordinate
     * @param bool $save_ori_images
     * @return array
     */
    public function cut($thumb_config, $coordinate = array(), $save_ori_images = true)
    {
        $ret = $this->move_upload_file();
        if ($ret['status'] === 0) {
            $result_file_url['ori'] = $this->get_save_file_name();
            if (!empty($thumb_config)) {
                $ori_images_path = $ret['message'];
                if (!is_array($thumb_config)) {
                    $thumb_config = array($thumb_config);
                }

                $result_file_url['thumb'] = $this->make_cut_thumb($ori_images_path, $thumb_config, $coordinate);
                if (!$save_ori_images) {
                    unlink($ori_images_path);
                    unset($result_file_url['ori']);
                }
            }

            return $this->result('ok', $result_file_url);
        }

        return $this->result($ret['status']);
    }

    /**
     * 剪切后生成缩略图
     *
     * @param string $images_path 原图地址
     * @param array $thumb_size_config 缩略图尺寸
     * @param array $coordinate 坐标(x,y,w,h)
     * @return array
     */
    protected function make_cut_thumb($images_path, $thumb_size_config, $coordinate)
    {
        $result = array();
        if (!$images_path || !$thumb_size_config) {
            return $result;
        }

        $cut = new ImageCut($images_path);
        foreach ($thumb_size_config as $val) {
            if (false !== strpos($val, 'x')) {
                list($width, $height) = explode('x', $val);
            }
            else {
                $width = $height = $val;
            }

            $thumb_file_name = sprintf("%s-%sx%s", $this->save_name, $width, $height);
            $save_path = $this->get_save_path();
            $result[] = $cut->set_cut_size($width, $height)->set_save_info($save_path, $thumb_file_name)->cut($coordinate);
        }

        return $result;
    }

    /**
     * 按参数生成缩略图
     *
     * @param $images_path
     * @param $thumb_size_config
     * @return array
     */
    protected function make_thumb($images_path, $thumb_size_config)
    {
        $result = array();
        if (!$images_path || !$thumb_size_config) {
            return $result;
        }

        $Thumb = new ImageThumb($images_path);
        foreach ($thumb_size_config as $val) {
            if (false !== strpos($val, 'x')) {
                list($width, $height) = explode('x', $val);
            }
            else {
                $width = $height = $val;
            }

            $thumb_file_name = sprintf("%s-%sx%s", $this->save_name, $width, $height);
            $result[] = $Thumb->set_file($this->get_save_path(), $thumb_file_name)->set_size($width, $height)->thumb();
        }

        return $result;
    }

    /**
     * 检查文件类型
     */
    protected function check_file_type()
    {
        $base_info = $this->get_base_info();
        $allow_type = $this->get_allow_type();
        if (!in_array($base_info['file_type'], $allow_type)) {
            return -3;
        }

        return true;
    }

    /**
     * 检查文件大小
     *
     * @return bool|int
     */
    protected function check_file_size()
    {
        $base_info = $this->get_base_info();
        $allow_size = $this->get_allow_size();

        if ($base_info['size'] > $allow_size) {
            return -4;
        }

        return true;
    }

    /**
     * 保存的文件全路径
     *
     * @return string
     */
    protected function get_save_file_full_path()
    {
        $save_path = $this->get_save_path();
        $save_name = $this->get_save_file_name();

        return $save_path . $save_name;
    }

    /**
     * 获取图片详细信息
     *
     * @param $images
     * @return array|bool
     */
    protected function get_image_info($images)
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
        }
        else {
            return false;
        }
    }

    /**
     * 通用返回消息
     *
     * @param $code
     * @param string $message
     * @return array
     */
    protected function result($code, $message = '')
    {
        $result = array('status' => 0, 'message' => 'ok');
        $result['status'] = $code;
        if ('' === $message) {
            $message = $this->status_code[$code];
        }
        $result['message'] = $message;

        return $result;
    }

}
