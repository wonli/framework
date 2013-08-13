<?php
class ReSizeImage
{
    /**
     * @var 图片类型
     */
    private $type;

    /**
     * @var int 实际宽度
     */
    private $width;
	
    /**
     * @var int 实际高度
     */
    private $height;
	
    /**
     * @var 改变后的宽度
     */
    private $resize_width;
	
    /**
     * @var 改变后的高度
     */
    private $resize_height;
	
    /**
     * @var 是否裁图
     */
    private $cut;

    /**
     * @var 源图象
     */
    private $srcimg;

    /**
     * @var 目标图象地址
     */
    private $dstimg;
	
    /**
     * @var 临时创建的图象
     */
    private $im;
	
	function __contruct($img, $wid, $hei, $c, $dstpath) {
        #原图片信息
        $arr_img=getimagesize($img);
        #元图片
		$this->srcimg = $img;
		#宽度
        $this->resize_width = $wid;
		#高度
        $this->resize_height = $hei;
		#是否剪切
        $this->cut = $c;
        
		#图片的类型  
		$this->type = $arr_img['mime'];

		//初始化图象  
		$this->initi_img ();
		
		//目标图象地址  
		$this->dst_img ( $dstpath );
		
		$this->width = imagesx ( $this->im );
		$this->height = imagesy ( $this->im );
		
		//生成图象  
		$this->newimg ();
		ImageDestroy ( $this->im );
	}

    /**
     * 生成图象
     */
    function newimg()
    {
		//改变后的图象的比例  
		if(!empty($this->resize_height)) {
			$resize_ratio = ($this->resize_width) / ($this->resize_height);
		} else {
			$resize_ratio = 0;		
		}

		//实际图象的比例 
		$ratio = ($this->width) / ($this->height);

		if (($this->cut) == "1") //裁图  
		{
			if ($ratio >= $resize_ratio) //高度优先  
			{
				$newimg = imagecreatetruecolor ( $this->resize_width, $this->resize_height );
                imagecopyresampled ( $newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, (($this->height) * $resize_ratio), $this->height );
				ImageJpeg ( $newimg, $this->dstimg,100);
			}
			if ($ratio < $resize_ratio) //宽度优先  
			{
				$newimg = imagecreatetruecolor ( $this->resize_width, $this->resize_height );
				imagecopyresampled ( $newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, (($this->width) / $resize_ratio) );
				ImageJpeg ( $newimg, $this->dstimg,100);
			}

		}
        else
        {

			if ($ratio >= $resize_ratio) {
				$newimg = imagecreatetruecolor ( $this->resize_width, ($this->resize_width) / $ratio );
				imagecopyresampled ( $newimg, $this->im, 0, 0, 0, 0, $this->resize_width, ($this->resize_width) / $ratio, $this->width, $this->height );
				ImageJpeg ( $newimg, $this->dstimg );
			}
			if ($ratio < $resize_ratio) {
				$newimg = imagecreatetruecolor ( ($this->resize_height) * $ratio, $this->resize_height );
				imagecopyresampled ( $newimg, $this->im, 0, 0, 0, 0, ($this->resize_height) * $ratio, $this->resize_height, $this->width, $this->height );
				ImageJpeg ( $newimg, $this->dstimg );
			}
		}
	}
	
    /**
     * 创建临时图象
     */
    function initi_img() {
		if ($this->type == "image/jpeg") {
			$this->im = imagecreatefromjpeg ( $this->srcimg );
		}
		if ($this->type == "image/gif") {
			$this->im = imagecreatefromgif ( $this->srcimg );
		}
		if ($this->type == "image/png") {
			$this->im = imagecreatefrompng ( $this->srcimg );
		}
	}

    /**
     * 图象目标地址
     *
     * @param $dstpath
     */
    function dst_img($dstpath) {
		$full_length = strlen ( $this->srcimg );
		$type_length = strlen ( $this->type );
		$name_length = $full_length - $type_length;
		$name = substr ( $this->srcimg, 0, $name_length - 1 );
		$this->dstimg = $dstpath;
	}
}