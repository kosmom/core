<?php
namespace c;

/**
 * @author Kosmom <Kosmom.ru>
 * @example
 *  if (c\pics::is_pic(c\u::filename())){
 *  c\pics::getpic(c\u::filename());
 *  c\pics::resize_box(100); // 100x100px
 *  c\pics::save(c\u::filename(),75); //75% quality
 *  }
 */
class pics{
	private static $image;
	private static $prop;
	function __construct() {
		if (core::$debug)debug::trace('Create pic with static class pics',error::WARNING);
	}
	/**
	 * memory test before picture loading
	 * @param integer $x width
	 * @param integer $y height
	 * @return boolean
	 * @deprecated since version 3.4
	 */
	static function memory_test($x,$y){
			return self::memoryTest($x,$y);
		}
	/**
	 * memory test before picture loading
	 * @param integer $x width
	 * @param integer $y height
	 * @return boolean
	 */
	static function memoryTest($x,$y){
		$val=trim(ini_get('memory_limit'));
		$last = $val[strlen($val)-1];
		switch($last){
		case 'G':
			$val *= 1024;
		case 'M':
			$val *= 1024;
		case 'K':
			$val *= 1024;
	}
		$limit=$val;
		$m=memory_get_usage();
		$usage=($x*$y*5.07)+7900+($y*112)+$m;
		if (core::$debug){
			debug::group('Pic memory test',$usage>$limit?error::ERROR:error::INFO);
			debug::consoleLog('Pic '.$x.'x'.$y.' will get '.round(($usage-$m)/1000).'Kb of memory');
			debug::consoleLog('Free memory is '.round(($val-$m)/1000).'Kb');
		}
		if ($usage>$limit){
			error::add(translate::t('Image is too large to be loaded'),error::WARNING);
			if (core::$debug)debug::groupEnd();
			return false;
		}
		if (core::$debug)debug::groupEnd();
		return true;
	}
	/**
	 * is pic filename or not
	 * @param string $filename filename of image or inline string
	 * @return boolean
	 * @deprecated since version 3.4
	 */
	static function is_pic($filename){
		return self::isPic($filename);
	}
	/**
	 * is pic filename or not
	 * @param string $filename filename of image or inline string
	 * @return boolean
	 */
	static function isPic($filename){
			if (file_exists($filename)){
				$prop=getimagesize($filename);
			}else{
				$prop=getimagesizefromstring($filename);
				if (!$prop){
					if (core::$debug)debug::consoleLog('Filename '.$filename.' not found',error::WARNING);
					return false;
				}
			}
			if (empty($prop[2]))return false;
			if (!in_array($prop[2],array(IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_PNG)))return false;
			return true;
	}
	/**
	 * Load picture into memory
	 * @param string $filename
	 * @return boolean result
	 */
	static function getpic($filename){
		if (file_exists($filename)){
			$fromString=false;
			self::$prop=getimagesize($filename);
		}else{
			$fromString=true;
			self::$prop=getimagesizefromstring($filename);
		}
		if (!self::memoryTest(self::$prop[0],self::$prop[1]))return false;
		if (self::$image)imagedestroy(self::$image);
		if ($fromString)self::$image =imagecreatefromstring($filename);
		switch(self::$prop[2]){
			case IMAGETYPE_JPEG:
				self::$image = imagecreatefromjpeg($filename);
				$exif = read_exif_data($filename);
				if (isset($exif['Orientation'])){
					switch($exif['Orientation']){
						case 3: self::$image=imagerotate(self::$image,180,0);
							break;
						case 6: self::$image=imagerotate(self::$image,270,0);
							$temp=self::$prop[0];
							self::$prop[0]=self::$prop[1];
							self::$prop[1]=$temp;
							break;
						case 8:self::$image=imagerotate(self::$image,90,0);
							$temp=self::$prop[0];
							self::$prop[0]=self::$prop[1];
							self::$prop[1]=$temp;
					}
				}
				return true;
			case IMAGETYPE_GIF:
				self::$image = imagecreatefromgif($filename);
				return true;
			case IMAGETYPE_PNG:
				self::$image = imagecreatefrompng($filename);
				return true;
		}
	}

	/**
	 * Resize buffer image into prop
	 * @param integer $x width
	 * @param integer $y height
	 * @return boolean result of operation
	 */
	static function resize($x,$y=false){
		if (!$y)$y=$x;
		if (!self::$image)return false;
		if (!self::memoryTest($x,$y))return false;
		$copy = imagecreatetruecolor($x, $y);
		imagecopyresampled($copy,self::$image,0,0,0,0,$x,$y,self::$prop[0],self::$prop[1]);
		self::$prop[0]=$x;
		self::$prop[1]=$y;
		imagedestroy(self::$image);
		self::$image=$copy;
	}

	/**
	 * Resize pic with fix rate to max of params
	 * @param type $x width
	 * @param type $y height
	 * @return boolean operation result
	 * @deprecated since version 3.4
	 */
	static function resize_box($x,$y=false){
		return self::resizeBox($x,$y);
	}
	/**
	 * Resize pic with fix rate to max of params
	 * @param type $x width
	 * @param type $y height
	 * @return boolean operation result
	 */
	static function resizeBox($x,$y=false){
		if (!$y)$y=$x;
		if ($x<1 or $y<1)return false;
		if (!self::$image)return false;
		if (($x>self::$prop[0]) && ($y>self::$prop[1]))return true;
		if ((self::$prop[0]/$x)<(self::$prop[1]/$y)){
			$reduce=self::$prop[1]/$y;
		}else{
			$reduce=self::$prop[0]/$x;
		}
		if ($reduce==0)return false;
		if (!self::memoryTest(self::$prop[0]/$reduce,self::$prop[1]/$reduce))return false;
		$copy = imagecreatetruecolor(self::$prop[0]/$reduce, self::$prop[1]/$reduce);
		imagecopyresampled($copy,self::$image,0,0,0,0,self::$prop[0]/$reduce,self::$prop[1]/$reduce,self::$prop[0],self::$prop[1]);
		self::$prop[0]=self::$prop[0]/$reduce;
		self::$prop[1]=self::$prop[1]/$reduce;
		imagedestroy(self::$image);
		self::$image=$copy;
		return true;
	}
	/**
	 * Change size with save dimensions for max of values
	 * @deprecated since version 3.4
	 */
	static function resize_box_fit($x,$y=false){
		return self::resizeBoxFit($x,$y);
	}
	/**
	 * Change size with save dimensions for max of values
	 */
	static function resizeBoxFit($x,$y=false){
		if ($y==false)$y=$x;
		if ($x<1 or $y<1)return false;
		if (!self::$image)return false;
		if (($x>self::$prop[0]) && ($y>self::$prop[1]))return true;
		if (self::$prop[0]/$x<self::$prop[1]/$y){
			$reduce=self::$prop[1]/$x;
			$width=($x-self::$prop[0]/$reduce)*.5;
			$height=0;
		}else{
			$reduce=self::$prop[0]/$x;
			$width=0;
			$height=($y-self::$prop[1]/$reduce)*.5;
		}
		if ($reduce==0)return false;
		if (!self::memoryTest($x,$y))return false;
		$copy = imagecreatetruecolor($x, $y);
		imagesavealpha($copy,true);
		$transparent = imagecolorallocatealpha($copy,255,255,255,127);
		imagefill($copy, 0, 0, $transparent);
		imagecopyresampled($copy,self::$image,$width,$height,0,0,self::$prop[0]/$reduce,self::$prop[1]/$reduce,self::$prop[0],self::$prop[1]);
		self::$prop[0]=self::$prop[0]/$reduce;
		self::$prop[1]=self::$prop[1]/$reduce;
		imagedestroy(self::$image);
		self::$image=$copy;
		return true;
	}
	/**
	 * crop picture
	 * @param number $x left position
	 * @param number $y top position
	 * @param number|null $width width
	 * @param number|null $height height
	 * @return boolean operation result
	 */
	static function crop($x,$y,$width=false,$height=false){
		if (!self::$image)return false;
		if (($x>self::$prop[0]) && ($y>$self::$prop[1]))return false;
		if (!$height)$height=$width?$width:self::$prop[1]-$y;
				if (!$width)$width=self::$prop[0]-$x;
		$copy = imagecreatetruecolor($width, $height);
		imagesavealpha($copy,true);
		$transparent = imagecolorallocatealpha($copy,255,255,255,127);
		imagefill($copy, 0, 0, $transparent);
		imagecopyresampled($copy,self::$image,0,0,$x,$y,$width,$height,$width,$height);
		self::$prop[0]=$width;
		self::$prop[1]=$height;
		imagedestroy(self::$image);
		self::$image=$copy;
		return true;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function resize_fit($x,$y=false){
	   return self::resizeFit($x,$y);
	}
	static function resizeFit($x,$y=false){
		if (!$y)$y=$x;
		if ($x<1 or $y<1)return false;
		if (!self::$image)return false;
		if (self::$prop[0]/$x<self::$prop[1]/$y){
			$reduce=self::$prop[0]/$x;
			$height=$y*.5-self::$prop[1]/$reduce*.5;
			$width=0;
		}else{
			$reduce=self::$prop[1]/$y;
			$height=0;
			$width=$x*.5-self::$prop[0]/$reduce*.5;
		}
		if ($reduce==0)return false;
		if (!self::memoryTest($x,$y))return false;
		$copy = imagecreatetruecolor($x, $y);
		imagecopyresampled($copy,self::$image,$width,$height,0,0,self::$prop[0]/$reduce,self::$prop[1]/$reduce,self::$prop[0],self::$prop[1]);
		self::$prop[0]=self::$prop[0]/$reduce;
		self::$prop[1]=self::$prop[1]/$reduce;
		imagedestroy(self::$image);
		self::$image=$copy;
		return true;

	}
	/**
	 * crop center frame of picture
	 * @param number $x width
	 * @param number $y height
	 * @return boolean operaiotn result
	 * @deprecated since version 3.4
	 */
	static function crop_center($x,$y=false){
		return self::cropCenter($x,$y);
	}
	/**
	 * crop center frame of picture
	 * @param number $x width
	 * @param number $y height
	 * @return boolean operaiotn result
	 */
	static function cropCenter($x,$y=false){
		if (!$y)$y=$x;
		if ($x<1 or $y<1)return false;
		if (!self::$image)return false;
		$copy = imagecreatetruecolor($x, $y);
		imagesavealpha($copy,true);
		$transparent = imagecolorallocatealpha($copy,0,0,0,127);
		imagefill($copy, 0, 0, $transparent);
		$width=($x-self::$prop[0])/2;
		$height=($y-self::$prop[1])/2;
		imagecopyresampled($copy,self::$image,$width,$height,0,0,self::$prop[0],self::$prop[1],self::$prop[0],self::$prop[1]);
		self::$prop[0]=$x;
		self::$prop[1]=$y;
		imagedestroy(self::$image);
		self::$image=$copy;
		return true;
	}
	/**
	 * add watermart to picture
	 * @param type $x % width position
	 * @param type $y % height position
	 * @param type $watermarkFile watermark file
	 * @param type $opacity watermart opacity
	 * @return boolean operation result
	 * @deprecated since version 3.4
	 */
	static function add_watermark($x,$y,$watermarkFile,$opacity=100){
		return self::addWatermark($x,$y,$watermarkFile,$opacity);
	}
	/**
	 * add watermart to picture
	 * @param type $x % width position
	 * @param type $y % height position
	 * @param type $watermarkFile watermark file
	 * @param type $opacity watermart opacity
	 * @return boolean operation result
	 */
	static function addWatermark($x,$y,$watermarkFile,$opacity=100){
		if (!self::$image)return false;
		if (!is_readable($watermarkFile))return false;
		$prop=getimagesize($watermarkFile);
		if (!self::memoryTest($prop[0],$prop[1]))return false;
		switch($prop[2]){
			case IMAGETYPE_JPEG:
				$watermark = imagecreatefromjpeg($watermarkFile);
				break;
			case IMAGETYPE_GIF:
				$watermark = imagecreatefromgif($watermarkFile);
				break;
			case IMAGETYPE_PNG:
				$watermark = imagecreatefrompng($watermarkFile);
				break;
			default: return false;
		}
		imagealphablending(self::$image, true);
		imagealphablending($watermark, true);
		//imagesavealpha(self::$image, true);
		//imagesavealpha($watermark, true);
		imagecopymerge(self::$image, $watermark,(self::$prop[0]-$prop[0])*.01*$x, (self::$prop[1]-$prop[1])*.01*$y, 0, 0, $prop[0], $prop[1],$opacity);
		imagedestroy($watermark);
	}

	/**
	 * Save buffer picture
	 * @param type $filename filename
	 * @param type $quality quality for some formats. Quality must be between 0 - 100
	 */
	static function save($filename,$quality=50){
		$quality=(int)$quality;
		if (core::$debug){
			if ($quality<0)debug::trace('Save image quality must be between 0 and 100. Current val is: '.$quality);
		}
		$extension=substr($filename,strripos($filename,'.')+1);
		switch(strtolower($extension)){
			case 'jpg':
			case 'jpeg':
				imagejpeg(self::$image,$filename,$quality);
				break;
			case 'gif':
				imagegif(self::$image,$filename);
				break;
			case 'png':
								imagesavealpha(self::$image, true);
				imagepng(self::$image,$filename,9-floor($quality/11));
				break;
		}
	}
	/**
	 * Clear buffer image and release memory
	 * @deprecated since version 3.4
	 */
	static function free_image(){
		imagedestroy(self::$image);
	}
	/**
	 * Clear buffer image and release memory
	 */
	static function freeImage(){
		imagedestroy(self::$image);
	}
}