<?php
//验证码生成
class Captcha
{
	//宽
	public $width;
	//高
	public $height;
	//图片资源
	public $img;
	//图片类型
	public $imgType;
	//文字
	public $checkCode;
    //验证码类型
    public $codeType;
	//文字个数
	public $num;

	//构造方法，初使化各个成员属性包括根据文字类型，产生文字
	public function __construct($num=4, $width=120,$height=40, $imgType='jpeg'){
		$this->width=$width;
		$this->height=$height;
		$this->imgType=$imgType;
		$this->num=$num;
		//$this->checkCode=$this->getCheckCode();
	}
    
    //外部设置code
    public function setCheckCode($code, $type="en")
    {
        $this->checkCode = $code;
        $this->codeType = $type;
    }
    
	//产生文字得分为1,2,3    1为数字   2为字母   3为字母数字混合
	private function getCheckCode(){
        if($this->checkCode) {        
            if($this->codeType == "cn") {
                $this->checkCode = Helper::str_split($this->checkCode);
            }
            return $this->checkCode;
        } else throw new CoreException("error captcha code!");
	}

	//创建图片
	protected function createImg(){

		$this->img=imagecreatetruecolor($this->width,$this->height);
	}

	//产生背景颜色,颜色值越大，越浅，越小越深
	protected function bgColor(){
		return imagecolorallocate($this->img,mt_rand(130,255),mt_rand(130,255),mt_rand(130,255));
	}

	//字的颜色
	protected function fontColor(){
		return imagecolorallocate($this->img,mt_rand(0,120),mt_rand(0,120),mt_rand(0,120));
	}

	//填充背景颜色
	protected function filledColor(){
		imagefilledrectangle($this->img,0,0,$this->width,$this->height,$this->bgColor());

	}
	//画上干扰点
	protected function pix(){
		for($i=0;$i<60;$i++){
			imagesetpixel($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),$this->fontColor());
		}
	}

	//画上干扰线
	protected function arc(){
		for($i=0;$i<5;$i++){
			imagearc($this->img,mt_rand(10,$this->width-10),mt_rand(10,$this->height-10),200,50,mt_rand(0,90),mt_rand(100,390),$this->fontColor());
		}
	}

	//写字，得到x,y
	protected function write()
    {
        if(! $this->checkCode ) {
            $this->getCheckCode();		
        }
        
        for($i=0;$i<$this->num;$i++){
            $x=ceil($this->width/$this->num)*$i+5;
            $y=mt_rand(5,$this->height-25);
            imagechar($this->img,9,$x,$y,$this->checkCode[$i],$this->fontColor());
            /**
            if($this->codeType == "cn") {
                $angle=mt_rand(-5,1)*mt_rand(1,5);
                imagettftext($this->img,16,$angle,5+$i*floor(16*1.8),floor($this->height*0.75),$this->fontColor(),$front, $this->checkCode[$i]);
            } else {
            }
            */
		}
	}
    
	//输出
	protected function output()
    {
		$func='image'.$this->imgType;
		$header='Content-type:image/'.$this->imgType;

		if(function_exists($func)){
			header($header);
			$func($this->img);
		}else{
			echo '不支持该图片类型';
			exit;
		}
	}

	//组装得到图片
	public function getImage()
    {
		$this->createImg();
		$this->filledColor();
		$this->pix();
		$this->arc();
		$this->write();
		$this->output();
	}

	//销毁
	public function __destruct(){
		if(!empty($this->img)) {
			imagedestroy($this->img);
		}
	}

}
