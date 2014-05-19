<?php
class ImageCut
{
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
     * @var 临时创建的图象
     */
    private $im;

	function __construct($src_images)
    {
        $this->src_images = $src_images;
        $this->images_info = $this->get_image_info($src_images);
        $this->type = $this->images_info['file_type'];

		//初始化图象
		$this->create_im();

		//目标图象地址
		$this->width = $this->images_info['width'];
		$this->height = $this->images_info['height'];
	}

    /**
     * 设置保存路径
     *
     * @param $path
     */
    function set_save_info( $path, $name )
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
     */
    function set_cut_size($width, $height)
    {
        $this->resize_width = $width;
        $this->resize_height = $height;
        return $this;
    }

    /**
     * 生成图象
     */
    function cut( $coordinate, $return_path = false )
    {
        if ( ! isset($coordinate['x']) || ! isset($coordinate['y']) ||
            ! isset($coordinate['w']) || ! isset($coordinate['h'])
        ) {
            throw new Exception('请设置剪切坐标x, y, w, h');
        }

        $save_path = $this->get_save_path( );

		//改变后的图象的比例
		if(!empty($this->resize_height)) {
			$resize_ratio = ($this->resize_width) / ($this->resize_height);
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
            $thumb_images = imagecreatetruecolor($this->resize_width, $this->resize_height);
        } else {
            $thumb_images = imagecreate($this->resize_width, $this->resize_height);
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

        $this->save_image($thumb_images, $save_path, $this->images_info['file_type'], 100);
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
    protected function get_image_info($images)
    {
        $image_info = getimagesize($images);
        if (false !== $image_info)
        {
            $image_ext  = strtolower(image_type_to_extension($image_info[2]));
            $image_type = substr($image_ext, 1);
            $image_size = filesize($images);

            $info = array(
                'width'		=>  $image_info[0],
                'height'	=>  $image_info[1],
                'ext'       =>  $image_ext,
                'file_type' =>  $image_type,
                'size'		=>  $image_size,
                'mime'      =>  $image_info['mime'],
            );
            return $info;
        }else {
            return false;
        }
    }

    /**
     * 创建临时图象
     */
    private function create_im()
    {
        switch($this->type)
        {
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
    protected function save_image($resource, $save_path, $image_type, $quality = 100)
    {
        switch($image_type)
        {
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
     * 获取文件名
     *
     * @return string
     */
    private function get_save_name()
    {
        return $this->save_name;
    }

    /**
     * 获取文件保存文件夹
     *
     * @return string
     */
    private function get_save_dir()
    {
        return $this->save_path;
    }

    /**
     * 图象目标地址
     *
     * @param $dstpath
     */
    protected function get_save_path( )
    {
        $name = $this->get_save_name();
        if (! $name) {
            throw new Exception('请设置缩略图名称');
        }

        $path = $this->get_save_dir();
        if (! $path || ! is_dir($path)) {
            throw new Exception('请设置路径');
        }

        return $path.$name.$this->images_info['ext'];
	}
}
