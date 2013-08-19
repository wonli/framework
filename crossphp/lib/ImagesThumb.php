<?php
/**
 * @Auth wonli <wonli@live.com>
 * Class ImagesThumb 生成图片缩略图
 */

class ImagesThumb
{
    /**
     * @var 文件路径
     */
    protected $dir;

    /**
     * @var 文件名
     */
    protected $image;

    /**
     * @var 缩略图宽度
     */
    protected $widht;

    /**
     * @var 缩略图高度
     */
    protected $height;

    /**
     * 设置文件路径和文件名
     *
     * @param $dir
     * @param $image
     * @return $this
     */
    function set_file($dir, $image)
    {
        $this->dir = $dir;
        $this->image = $image;

        return $this;
    }

    /**
     * 设置高宽
     *
     * @param $size
     * @return $this
     */
    function set_size( $size )
    {
        if(false !== strpos($size, ":"))
        {
            list($width, $height) = explode("x", $size);
            $this->width = $width;
            $this->height = $height;
        }

        $this->width = $this->height = $size;
        return $this;
    }

    /**
     * 取得图像信息
     *
     * @static
     * @access public
     * @param string $image 图像文件名
     * @return mixed
     */
    function getImageInfo( $img )
    {
        $imageInfo = getimagesize($img);
        if( $imageInfo!== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]),1));
            $imageSize = filesize($img);
            $info = array(
                "width"		=>$imageInfo[0],
                "height"	=>$imageInfo[1],
                "type"		=>$imageType,
                "size"		=>$imageSize,
                "mime"		=>$imageInfo['mime'],
            );
            return $info;
        }else {
            return false;
        }
    }

    /**
     * 生成缩略图
     * @static
     * @access public
     * @param boolean $is_save 是否保留原图
     * @param boolean $interlace 启用隔行扫描
     * @return void
     */
    function thumb($is_save=true, $location= false, $interlace=true)
    {
         if(! $this->dir || ! $this->image)
         {
             throw new CoreException("请设置文件路径和文件名");
         }

         /**
          * 图片源文件绝对路径
          */
         $image = $this->dir.$this->image;
         // 获取原图信息
         $info  = $this->getImageInfo( $image );


         if($info !== false)
         {
             /**
              * 原图高宽
              */
             $srcWidth  = $info['width'];
             $srcHeight = $info['height'];
             $type = empty($type)?$info['type']:$type;
             $type = strtolower($type);
             $interlace  =  $interlace? 1:0;
             unset($info);

             /**
              * 缩放比例
              */
             $scale = min($this->width/$srcWidth, $this->height/$srcHeight);

             if($scale>=1)
             {
                 // 超过原图大小不再缩略
                 $width   =  $srcWidth;
                 $height  =  $srcHeight;
             }
             else
             {
                 // 缩略图尺寸
                 $width  = (int)($srcWidth*$scale);	//147
                 $height = (int)($srcHeight*$scale);	//199
             }

             // 载入原图
             $createFun = 'ImageCreateFrom'.($type=='jpg'?'jpeg':$type);
             $srcImg     = $createFun($image);

             //创建缩略图
             if($type!='gif' && function_exists('imagecreatetruecolor')) {
                 $thumbImg = imagecreatetruecolor($width, $height);
             }
             else
             {
                 $thumbImg = imagecreate($width, $height);
             }

             // 复制图片
             if(function_exists("ImageCopyResampled")) {
                 imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
             }
             else
             {
                 imagecopyresized($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height,  $srcWidth,$srcHeight);
             }

             if('gif'==$type || 'png'==$type) {
                 //imagealphablending($thumbImg, false);//取消默认的混色模式
                 //imagesavealpha($thumbImg,true);//设定保存完整的 alpha 通道信息
                 $background_color  =  imagecolorallocate($thumbImg,  0, 255, 0);  //  指派一个绿色
                 imagecolortransparent($thumbImg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
             }

             // 对jpeg图形设置隔行扫描
             if('jpg'==$type || 'jpeg'==$type) 	imageinterlace($thumbImg, $interlace);
             //$gray=ImageColorAllocate($thumbImg,255,0,0);
             //ImageString($thumbImg,2,5,5,"ThinkPHP",$gray);
             // 生成图片
             $imageFun = 'imagejpeg';
             //图片质量
             $image_quality = 100;

             $src_type = explode(".", $image);
             $src_type = end($src_type);

             $_type = "_small.".$type;

             $small_thumb_name = str_replace(".{$src_type}", $_type, $this->image);
             $small_thumb_file = $this->dir.$small_thumb_name;

             if ($location == 1)
             {
                 $thumbname01 = substr_replace($image,"01.".$type,$length);		//大头像
                 $thumbname02 = substr_replace($image,"02.".$type,$length);		//小头像

                 $imageFun($thumbImg, $thumbname01, $image_quality);
                 $imageFun($thumbImg, $thumbname02, $image_quality);

                 $thumbImg01 = imagecreatetruecolor(120,125);

                 imagecopyresampled($thumbImg01,$thumbImg,0,0,$location['x'],$location['y'],
                     120,125,$location['w'],$location['h']);

                 $thumbImg02 = imagecreatetruecolor(48,48);
                 imagecopyresampled($thumbImg02,$thumbImg,0,0,$location['x'],$location['y'],
                     48,48,$location['w'],$location['h']);

                 $imageFun($thumbImg01, $thumbname01, $image_quality);
                 $imageFun($thumbImg02, $thumbname02, $image_quality);
                 unlink($image);//删除原图
                 imagedestroy($thumbImg01);
                 imagedestroy($thumbImg02);
                 imagedestroy($thumbImg);
                 imagedestroy($srcImg);

                 return array('big' => $thumbname01 , 'small' => $thumbname02);	//返回包含大小头像路径的数组
             }
             else
             {
                 if($is_save == false)
                 {
                     $imageFun($thumbImg, $image, $image_quality);
                 }
                 else
                 {
                     $imageFun($thumbImg,  $small_thumb_file, $image_quality);
                 }

                 imagedestroy($thumbImg);
                 imagedestroy($srcImg);

                 return $small_thumb_name;
             }
         }
         return false;
    }

}
    
    
