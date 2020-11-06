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
	static $pics=array();
	private static $activePic='default';
	private static function checkObject($pic){
		if ($pic===\null)$pic=self::$activePic;
		if (!isset(self::$pics[$pic]))throw new \Exception('need create picture object first');
		return $pic;
	}
	/**
	 * Load picture into memory
	 * @param string $filename
	 * @param string $autorotate
	 * @return pic
	 */
	static function createPic($filename,$autorotate=\true, $pic=\null){
		if ($pic===\null)$pic=self::$activePic;
		return self::$pics[$pic]=new pic($filename,$autorotate);
	}

	function __construct() {
		if (core::$debug)debug::trace('Create pic with static class pics',error::WARNING);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function memory_test($x,$y){
		return pic::memoryTest($x,$y);
	}
	/**
	 * memory test before picture loading
	 * @param integer $x width
	 * @param integer $y height
	 * @return boolean
	 */
	static function memoryTest($x,$y){
		return pic::memoryTest($x,$y);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function is_pic($filename){
		return pic::isPic($filename);
	}
	/**
	 * is pic filename or not
	 * @param string $filename filename of image or inline string
	 * @return boolean
	 */
	static function isPic($filename){
		return pic::isPic($filename);
	}
	/**
	 * Load picture into memory
	 * @param string $filename
	 * @return pic
	 */
	static function getpic($filename,$autorotate=\true,$pic=\null){
		return self::createPic($filename, $autorotate,$pic);
	}

	/**
	 * Resize buffer image into prop
	 * @param integer $x width
	 * @param integer $y height
	 * @return pic
	 */
	static function resize($x,$y=\null,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->resize($x,$y);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function resize_box($x,$y=\null,$pic=\null){
		return self::resizeBox($x,$y,$pic);
	}
	/**
	 * Resize pic with fix rate to max of params
	 * @param int $x width
	 * @param int $y height
	 * @return pic
	 */
	static function resizeBox($x,$y=\null,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->resizeBox($x,$y);
	}
	static function resizeMaxWidth($x,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->resizeMaxWidth($x);
	}
	static function resizeMaxHeight($y,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->resizeMaxHeight($y);
	}
	/**
	 * Change size with save dimensions for max of values
	 * @deprecated since version 3.4
	 */
	static function resize_box_fit($x,$y=\null,$pic=\null){
		return self::resizeBoxFit($x,$y,$pic);
	}
	/**
	 * Change size with save dimensions for max of values
	 */
	static function resizeBoxFit($x,$y=\null,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->resizeBoxFit($x,$y);
	}
	/**
	 * crop picture
	 * @param number $x left position
	 * @param number $y top position
	 * @param number|null $width width
	 * @param number|null $height height
	 * @return pic
	 */
	static function crop($x,$y,$width=\null,$height=\null,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->crop($x,$y,$width,$height);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function resize_fit($x,$y=\null,$pic=\null){
	   return self::resizeFit($x,$y,$pic);
	}
	static function resizeFit($x,$y=\null,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->resizeFit($x,$y);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function crop_center($x,$y=\null,$pic=\null){
		return self::cropCenter($x,$y,$pic);
	}
	/**
	 * crop center frame of picture
	 * @param number $x width
	 * @param number $y height
	 * @return pic
	 */
	static function cropCenter($x,$y=\null,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->cropCenter($x,$y);
	}
	static function flip($direction='xy',$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->flip($direction);
	}
	static function getImage($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->getImage();
	}
	static function getWidth($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->getWidth();
	}
	static function getHeight($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->getHeight();
	}
	static function blurSelective($passes=1, $pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->blurSelective($passes);
	}
	static function blurGaussian($passes=1, $pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->blurGaussian($passes);
	}
	static function brightness($level, $pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->brightness($level);
	}
	static function contrast($level, $pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->contrast($level);
	}
	static function desaturate($percentage, $pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->desaturate($percentage);
	}
	static function rotate($angleClockwise,$bgdColor=0,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->rotate($angleClockwise,$bgdColor=0);
	}
	static function edges($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->edges();
	}
	static function emboss($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->emboss();
	}
	static function invert($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->invert();
	}
	static function meanRemove($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->meanRemove();
	}
	static function pixelate($blockSize=10, $pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->pixelate($blockSize);
	}
	static function sepia($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->sepia();
	}
	static function output($quality=50,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->output($quality);
	}
	static function sketch($pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->sketch();
	}
	static function smooth($level,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->smooth($level);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_watermark($x,$y,$watermarkFile,$opacity=100,$pic=\null){
		return self::addWatermark($x,$y,$watermarkFile,$opacity,$pin=\null);
	}
	/**
	 * add watermart to picture
	 * @param int $x % width position
	 * @param int $y % height position
	 * @param string $watermarkFile watermark file
	 * @param float $opacity watermart opacity
	 * @return pic
	 */
	static function addWatermark($x,$y,$watermarkFile,$opacity=100,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->addWatermark($x,$y,$watermarkFile,$opacity);
	}

	/**
	 * Save buffer picture
	 * @param string $filename filename
	 * @param float $quality quality for some formats. Quality must be between 0 - 100
	 */
	static function save($filename,$quality=50,$pic=\null){
		$pic=self::checkObject($pic);
		return self::$pics[$pic]->save($filename,$quality);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function free_image($pic=\null){
		self::freeImage($pic);
	}
	/**
	 * Clear buffer image and release memory
	 */
	static function freeImage($pic=\null){
		$pic=self::checkObject($pic);
		unset(self::$pics[$pic]);
	}
	
	/**
	 * Set or return active pic
	 * @param string|null $form
	 * @return null|string
	 */
	static function activePic($pic=\null){
		if ($pic===\null)return self::$activePic;
		self::$activePic=(string)$pic;
	}
}