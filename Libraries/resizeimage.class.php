<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 我们网 项目的一部分
//
// Copyright (c) 2005 - 2011 www.wmw.cn
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.wmw.cn/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * FILE_NAME : resizeimage.class.php   FILE_PATH : /libraries/resizeimage.class.php
 * ....生成缩略图操作类
 *
 * @copyright Copyright (c) 2005 - 2011 www.wmw.cn
 * @author javie jvsys999@hotmail.com
 * @package 
 * @subpackage 
 * @version Sun Apr 20 12:26:32 CST 2008
 */

class resizeimage
{

	public $type;//扩展名
	public $width;//宽
	public $height;//高
	public $resize_width;//生成的宽
	public $resize_height;//生成的高
	public $cut;
	public $srcimg;//文件名
	public $dstimg;//缩略图文件名
	private $im;//图像对象

	/** 
	* 初始化类
	* @param $filename 文件名
	* @param $width 宽
	* @param $height 高
	* @param $cut
	* @return void
	*/
	function __construct($fileName, $width, $height, $cut)
	{
		$this->srcimg = $fileName;
		$this->resize_width = $width;
		$this->resize_height = $height;
		$this->cut = $cut;
		$this->type = strtolower(substr(strrchr($this->srcimg, "."), 1));//扩展名
		$this->initi_img();
		$this->dst_img();
		$this->width = imagesx($this->im);
		$this->height = imagesy($this->im);
		$this->newimg();
		imagedestroy($this->im);
	}

	/** 
	* 生成缩略图
	* @param void
	* @return void
	*/
	function newimg()
	{
		$reSca = $this->resize_width / $this->resize_height;
		$sca = $this->width / $this->height;
		if($this->cut == "1")
		{
			if ($reSca <= $sca)
			{
				$image = imagecreatetruecolor($this->resize_width, $this->resize_height);
				imagecopyresampled($image, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->height * $reSca, $this->height);
				$this->createImageByType($image, $this->dstimg);
			}
			if ($sca < $reSca)
			{
				$image = imagecreatetruecolor($this->resize_width, $this->resize_height);
				imagecopyresampled($image, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, $this->width / $reSca);
				$this->createImageByType($image, $this->dstimg);
			}
		}
		else
		{
			if ($reSca <= $sca)
			{
				$image = imagecreatetruecolor($this->resize_width, $this->resize_width / $sca);
				imagecopyresampled($image, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_width / $sca, $this->width, $this->height);
				$this->createImageByType($image, $this->dstimg);
			}
			if ($sca < $reSca)
			{
				$image = imagecreatetruecolor($this->resize_height * $sca, $this->resize_height);
				imagecopyresampled($image, $this->im, 0, 0, 0, 0, $this->resize_height * $sca, $this->resize_height, $this->width, $this->height);
				$this->createImageByType($image, $this->dstimg);
			}
		}
	}
	protected function createImageByType($image, $dstimg) {
		$fun = 'image';
		if(in_array($this->type, array('gif', 'png'))) {
			$fun .= $this->type;	
		} elseif($this->type == 'jpg') {
			$fun .= 'jpeg';
		}
		$fun($image, $dstimg);
	}

	/** 
	* 生成相应图片类型句柄
	* @param void
	* @return void
	*/
	function initi_img()
	{
		if ($this->type == "jpg")
		{
			$this->im = imagecreatefromjpeg($this->srcimg);
		}
		if ($this->type == "gif")
		{
			$this->im = imagecreatefromgif($this->srcimg);
		}
		if ($this->type == "png")
		{
			$this->im = imagecreatefrompng($this->srcimg);
		}
		//if ($this->type == "bmp")
		//{
		//	$this->im = imagecreatefromwbmp($this->srcimg);
		//}
	}

	/** 
	* 生成缩略图文件名
	* @param void
	* @return void
	*/
	function dst_img()
	{
		$lenSrc = strlen($this->srcimg);
		$lenExt = strlen($this->type);
		$lenName = $lenSrc - $lenExt;
		$strName = substr($this->srcimg, 0, $lenName - 1);
		$this->dstimg = $strName."_small.".$this->type;
	}

}

?>
