<?php
namespace c;

/**
 * @author Kosmom <Kosmom.ru>
 * @example <p>c\pic::load(c\u::filename())<br>->resizeBox(100)<br>->save(c\u::filename(),75); <br>//75% quality</p>
  */
class pic{
	private $image;
	private $x;
	private $y;

	/**
	 * memory test before picture loading
	 * @param integer $x width
	 * @param integer $y height
	 * @return boolean
	 */
	static function memoryTest($x,$y){
		$val=\trim(\ini_get('memory_limit'));
		$last = $val[\strlen($val)-1];
		switch($last){
		case 'G':
			$val *= 1024;
		case 'M':
			$val *= 1024;
		case 'K':
			$val *= 1024;
		}
		$limit=$val;
		$m=\memory_get_usage();
		$usage=($x*$y*5.07)+7900+($y*112)+$m;
		if (core::$debug){
			debug::group('Pic memory test',$usage>$limit?error::ERROR:error::INFO);
			debug::consoleLog('Pic '.$x.'x'.$y.' will get '.\round(($usage-$m)/1000).'Kb of memory');
			debug::consoleLog('Free memory is '.\round(($val-$m)/1000).'Kb');
		}
		if ($usage>$limit){
			error::add(translate::t('Image is too large to be loaded'),error::WARNING);
			if (core::$debug)debug::groupEnd();
			return \false;
		}
		if (core::$debug)debug::groupEnd();
		return \true;
	}
	/**
	 * is pic filename or not
	 * @param string $filename filename of image or inline string
	 * @return boolean
	 */
	static function isPic($filename){
		if (!\file_exists($filename)){
			$prop=getimagesizefromstring($filename);
			if (!$prop){
				if (core::$debug)debug::consoleLog('Filename '.$filename.' not found',error::WARNING);
				return \false;
			}
		}else{
			$prop=getimagesize($filename);
		}
		if (empty($prop[2]))return \false;
		if (!\in_array($prop[2],array(18, \IMAGETYPE_JPEG,\IMAGETYPE_GIF,\IMAGETYPE_PNG,\IMAGETYPE_BMP)))return \false;
		return \true;
	}

	function rotate($angleClockwise,$bgdColor=0){
		$new=\imagerotate($this->image,-\intval($angleClockwise),$bgdColor);
		\imagesavealpha($new,\true);
		\imagealphablending($new,\true);
		$this->x=\imagesx($new);
		$this->y=\imagesy($new);
		$this->image=$new;
		return $this;
	}

	function flip($direction='xy'){
		$new = \imagecreatetruecolor($this->x, $this->y);
		\imagealphablending($new, \false);
		\imagesavealpha($new, \true);
		$dirs=array(
			'xy'=>3,//IMG_FLIP_BOTH,
			'y'=>2,//IMG_FLIP_VERTICAL,
			'x'=>1//IMG_FLIP_HORIZONTAL
		);
		$this->imageflip($this->image,isset($dirs[$direction])?$dirs[$direction]:$direction);
		return $this;
	}

	private function imageflip($image, $mode) {
		if (\function_exists('imageflip'))return imageflip($image,$mode);
		switch ($mode) {
			case 1:
		$max_x = \imagesx($image) - 1;
		$half_x = $max_x / 2;
		$sy = \imagesy($image);
		$temp_image = \imageistruecolor($image)?\imagecreatetruecolor(1, $sy):\imagecreate(1, $sy);
		for ($x = 0; $x < $half_x; ++$x) {
			\imagecopy($temp_image, $image, 0, 0, $x, 0, 1, $sy);
			\imagecopy($image, $image, $x, 0, $max_x - $x, 0, 1, $sy);
			\imagecopy($image, $temp_image, $max_x - $x, 0, 0, 0, 1, $sy);
		}
		break;

			case 2:
		$sx = \imagesx($image);
		$max_y = \imagesy($image) - 1;
		$half_y = $max_y / 2;
		$temp_image = \imageistruecolor($image)?\imagecreatetruecolor($sx, 1):\imagecreate($sx, 1);
		for ($y = 0; $y < $half_y; ++$y) {
			\imagecopy($temp_image, $image, 0, 0, 0, $y, $sx, 1);
			\imagecopy($image, $image, 0, $y, 0, $max_y - $y, $sx, 1);
			\imagecopy($image, $temp_image, 0, $max_y - $y, 0, 0, $sx, 1);
		}
		break;

			case 3:
		$sx = \imagesx($image);
		$sy = \imagesy($image);
		$temp_image = \imagerotate($image, 180, 0);
		\imagecopy($image, $temp_image, 0, 0, 0, 0, $sx, $sy);
		break;

	default: return ;
	}
	\imagedestroy($temp_image);
	}

	function load($filename,$autorotate=\true){
		return new $this($filename,$autorotate);
	}

	/**
	* Load picture into memory
	* @param string $filename or $imageAsString
	*/
	function __construct($filename,$autorotate=\true){
		if (\file_exists($filename)){
			$fromString=\false;
			$prop=\getimagesize($filename);
		}else{
			if (\function_exists('getimagesizefromstring')){
				$prop=\getimagesizefromstring($filename);
			}else{
				$tempresult=\ini_get('upload_tmp_dir').(\ini_get('upload_tmp_dir')==''?'':'/').'temp.pic';
				\unlink($tempresult);
				\file_put_contents($tempresult, $filename);
				$prop=\getimagesize($tempresult);
			}
			$fromString=\true;
		}
		if (!$prop) throw new \Exception('Image not recognized',3);
		if (!$this->memoryTest($prop[0],$prop[1]))throw new \Exception('Need more memory',4);
		$this->x=$prop[0];
		$this->y=$prop[1];
		if ($this->image)\imagedestroy($this->image);
		if ($fromString)$this->image=\imagecreatefromstring($filename);
		switch($prop[2]){
			case \IMAGETYPE_JPEG:
				if (!$fromString)$this->image=\imagecreatefromjpeg($filename);
				$exif=\exif_read_data($filename);
				if ($autorotate && isset($exif['Orientation'])){
					switch($exif['Orientation']){
						case 3: $this->image=\imagerotate($this->image,180,0);
							break;
						case 6: $this->image=\imagerotate($this->image,270,0);
							$this->x=$prop[1];
							$this->y=$prop[0];
							break;
						case 8:$this->image=\imagerotate($this->image,90,0);
							$this->x=$prop[1];
							$this->y=$prop[0];
					}
				}
				return;
			case \IMAGETYPE_GIF:
				if (!$fromString)$this->image = \imagecreatefromgif($filename);
				return;
			case \IMAGETYPE_PNG:
				if (!$fromString)$this->image = \imagecreatefrompng($filename);
				return;
			case \IMAGETYPE_BMP:
			    throw new \Exception('test');
				if (\function_exists('imagecreatefrombmp'))throw new \Exception('BMP format dont supported');
				if (!$fromString)$this->image = \imagecreatefrombmp($filename);
				return;
			case 18: // IMAGETYPE_WEBP
				if (!$fromString)$this->image = \imagecreatefromwebp($filename);
				return;
				
		}
		if (!$fromString)   throw new \Exception('image not regognized');
	}

	/**
	 * Resize buffer image into prop
	 * @param integer $x width
	 * @param integer $y height
	 * @return pic
	 */
	function resize($x,$y=\null){
		if ($y===\null)$y=$x;
		if (!$this->memoryTest($x,$y))throw new \Exception('Need more memory');
		$new = \imagecreatetruecolor($x, $y);
		\imagealphablending($new, \false);
		\imagesavealpha($new, \true);
		\imagecopyresampled($new,$this->image,0,0,0,0,$x,$y,$this->x,$this->y);
		$this->x=$x;
		$this->y=$y;
		\imagedestroy($this->image);
		$this->image=$new;
		return $this;
	}

	function sepia() {
		\imagefilter($this->image, \IMG_FILTER_GRAYSCALE);
		\imagefilter($this->image, \IMG_FILTER_COLORIZE, 100, 50, 0);
		return $this;
	}

	function sketch() {
		\imagefilter($this->image, \IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}

	function smooth($level) {
		\imagefilter($this->image, \IMG_FILTER_SMOOTH, $level);
		return $this;
	}

	/**
	 * Resize pic with fix rate to max of params
	 * @param int $x width
	 * @param int $y height
	 * @return pic
	 */
	function resizeBox($x,$y=\null){
		if ($y===\null)$y=$x;
		if ($x<1 or $y<1)  throw new \Exception('wrong x or y values on resize');
		if (($x>$this->x) && ($y>$this->y))return $this;
		if (($this->x/$x)<($this->y/$y)){
			$reduce=$this->y/$y;
		}else{
			$reduce=$this->x/$x;
		}
		if ($reduce==0)return $this;
		if (!$this->memoryTest($this->x/$reduce,$this->y/$reduce))throw new \Exception('Need more memory');
		$copy = \imagecreatetruecolor($this->x/$reduce, $this->y/$reduce);
		\imagecopyresampled($copy,$this->image,0,0,0,0,$this->x/$reduce,$this->y/$reduce,$this->x,$this->y);
		$this->x=$this->x/$reduce;
		$this->y=$this->y/$reduce;
		\imagedestroy($this->image);
		$this->image=$copy;
		return $this;
	}
	function resizeMaxWidth($x){
		if ($this->x<=$x)return $this;
		return $this->resizeBox($x,99999);
	}
	function resizeMaxHeight($y){
		if ($this->y<=$y)return $this;
		return $this->resizeBox(99999,$y);
	}
	/**
	 * Change size with save dimensions for max of values
	 */
	function resizeBoxFit($x,$y=\null){
		if ($y===\null)$y=$x;
		if ($x<1 or $y<1)  throw new \Exception('wrong x or y values on resize');
		if (($x>$this->x) && ($y>$this->y))return $this;
		if ($this->x/$x<$this->y/$y){
			$reduce=$this->y/$x;
			$width=($x-$this->x/$reduce)*.5;
			$height=0;
		}else{
			$reduce=$this->x/$x;
			$width=0;
			$height=($y-$this->y/$reduce)*.5;
		}
		if ($reduce==0)return $this;
		if (!$this->memoryTest($x,$y))throw new \Exception('Need more memory');
		$copy = \imagecreatetruecolor($x, $y);
		\imagesavealpha($copy,\true);
		$transparent = \imagecolorallocatealpha($copy,255,255,255,127);
		\imagefill($copy, 0, 0, $transparent);
		\imagecopyresampled($copy,$this->image,$width,$height,0,0,$this->x/$reduce,$this->y/$reduce,$this->x,$this->y);
		$this->x=$this->x/$reduce;
		$this->y=$this->y/$reduce;
		\imagedestroy($this->image);
		$this->image=$copy;
		return $this;
	}
	/**
	 * crop picture
	 * @param int $x left position
	 * @param int $y top position
	 * @param int|null $width width
	 * @param int|null $height height
	 */
	function crop($x,$y,$width=\null,$height=\null){
		if (($x>$this->x) && ($y>$this->y))return $this;
		if ($height===\null)$height=$width?$width:$this->y-$y;
		if ($width===\null)$width=$this->x-$x;
		$copy = \imagecreatetruecolor($width, $height);
		\imagesavealpha($copy,\true);
		$transparent = \imagecolorallocatealpha($copy,255,255,255,127);
		\imagefill($copy, 0, 0, $transparent);
		\imagecopyresampled($copy,$this->image,0,0,$x,$y,$width,$height,$width,$height);
		$this->x=$width;
		$this->y=$height;
		\imagedestroy($this->image);
		$this->image=$copy;
		return $this;
	}

	function resizeFit($x,$y=\null){
		if ($y===\null)$y=$x;
		if ($x<1 or $y<1)  throw new \Exception('wrong x or y values on resize');
		if ($this->x/$x<$this->y/$y){
			$reduce=$this->x/$x;
			$height=$y*.5-$this->y/$reduce*.5;
			$width=0;
		}else{
			$reduce=$this->y/$y;
			$height=0;
			$width=$x*.5-$this->x/$reduce*.5;
		}
		if ($reduce==0)return $this;
		if (!$this->memoryTest($x,$y))throw new \Exception('Need more memory');
		$copy = \imagecreatetruecolor($x, $y);
		\imagecopyresampled($copy,$this->image,$width,$height,0,0,$this->x/$reduce,$this->y/$reduce,$this->x,$this->y);
		$this->x=$this->x/$reduce;
		$this->y=$this->y/$reduce;
		\imagedestroy($this->image);
		$this->image=$copy;
		return $this;
	}

	/**
	 * crop center frame of picture
	 * @param int $x width
	 * @param int $y height
	 */
	function cropCenter($x,$y=\null){
		if ($y===\null)$y=$x;
		if ($x<1 or $y<1)  throw new \Exception('wrong x or y values on resize');
		$copy = \imagecreatetruecolor($x, $y);
		\imagesavealpha($copy,\true);
		$transparent = \imagecolorallocatealpha($copy,0,0,0,127);
		\imagefill($copy, 0, 0, $transparent);
		$width=($x-$this->x)/2;
		$height=($y-$this->y)/2;
		\imagecopyresampled($copy,$this->image,$width,$height,0,0,$this->x,$this->y,$this->x,$this->y);
		$this->x=$x;
		$this->y=$y;
		\imagedestroy($this->image);
		$this->image=$copy;
		return $this;
	}

	/**
	 * add watermart to picture
	 * @param int $x % width position
	 * @param int $y % height position
	 * @param string $watermarkFile watermark file
	 * @param float $opacity watermart opacity
	 */
	function addWatermark($x,$y,$watermarkFile,$opacity=100){
		if (!\is_readable($watermarkFile))  throw new \Exception('watermark file not exists');
		$prop=\getimagesize($watermarkFile);
		if (!$this->memoryTest($prop[0],$prop[1]))throw new \Exception('Need more memory');
		switch($prop[2]){
			case \IMAGETYPE_JPEG:
				$watermark = \imagecreatefromjpeg($watermarkFile);
				break;
			case \IMAGETYPE_GIF:
				$watermark = \imagecreatefromgif($watermarkFile);
				break;
			case \IMAGETYPE_PNG:
				$watermark = \imagecreatefrompng($watermarkFile);
				break;
			default: throw new \Exception('watermark file is not image');
		}
		\imagealphablending($this->image, \true);
		\imagealphablending($watermark, \true);
		//imagesavealpha($this->image, true);
		//imagesavealpha($watermark, true);
		\imagecopymerge($this->image, $watermark,($this->x-$prop[0])*.01*$x, ($this->y-$prop[1])*.01*$y, 0, 0, $prop[0], $prop[1],$opacity);
		\imagedestroy($watermark);
		return $this;
	}

	function getImage(){
		return $this->image;
	}
	function getWidth(){
		return $this->x;
	}
	function getHeight(){
		return $this->y;
	}

	function blurSelective($passes = 1) {
		for ($i = 0; $i < $passes; $i++) {
			\imagefilter($this->image, \IMG_FILTER_SELECTIVE_BLUR);
		}
		return $this;
	}
	function blurGaussian($passes = 1) {
		for ($i = 0; $i < $passes; $i++) {
			\imagefilter($this->image, \IMG_FILTER_GAUSSIAN_BLUR);
		}
		return $this;
	}
	/**
	 *
	 * @param integer $level brightness level from -255 to 255
	 */
	function brightness($level) {
		\imagefilter($this->image, \IMG_FILTER_BRIGHTNESS, $level);
		return $this;
	}

	/**
	 *
	 * @param integer $level contrast level from -100 to 100
	 */
	function contrast($level) {
		\imagefilter($this->image, \IMG_FILTER_CONTRAST, $level);
		return $this;
	}

	function desaturate($percentage = 100) {
		// Determine percentage
		if( $percentage === 100 ) {
			\imagefilter($this->image, \IMG_FILTER_GRAYSCALE);
		} else {
			// Make a desaturated copy of the image
			$new = \imagecreatetruecolor($this->x, $this->y);
			\imagealphablending($new, \false);
			\imagesavealpha($new, \true);
			\imagecopy($new, $this->image, 0, 0, 0, 0, $this->x, $this->y);
			\imagefilter($new, \IMG_FILTER_GRAYSCALE);
			// Merge with specified percentage
			\imagecopymerge($this->image, $new, 0, 0, 0, 0, $this->x, $this->y, $percentage);
			\imagedestroy($new);
		}
		return $this;
	}

	function edges() {
		\imagefilter($this->image, \IMG_FILTER_EDGEDETECT);
		return $this;
	}

	function emboss() {
		\imagefilter($this->image, \IMG_FILTER_EMBOSS);
		return $this;
	}

	function invert() {
		\imagefilter($this->image, \IMG_FILTER_NEGATE);
		return $this;
	}

	function meanRemove() {
		\imagefilter($this->image, \IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}

	function pixelate($blockSize = 10) {
		\imagefilter($this->image, \IMG_FILTER_PIXELATE, $blockSize, \true);
		return $this;
	}

	/**
	 * Save buffer picture
	 * @param string $filename filename
	 * @param float $quality quality for some formats. Quality must be between 0 - 100
	 */
	function save($filename,$quality=50){
		$quality=(int)$quality;
		if (core::$debug){
			if ($quality<0 or $quality>100)debug::trace('Save image quality must be between 0 and 100. Current val is: '.$quality);
		}
		$extension=\substr($filename,\strripos($filename,'.')+1);

		switch(\strtolower($extension)){
			case 'jpg':
			case 'jpeg':
				$rs=\imagejpeg($this->image,$filename,$quality);
				break;
			case 'gif':
				$rs=\imagegif($this->image,$filename);
				break;
			case 'png':
				\imagesavealpha($this->image, \true);
				$rs=\imagepng($this->image,$filename,9-\floor($quality/11));
				break;
		}
		if (!$rs && core::$debug)debug::trace('Save image failed',error::ERROR);
		return $this;
	}

	function output($quality=50,$format='png'){
		$quality=(int)$quality;
		if (core::$debug && ($quality<0 or $quality>100))debug::trace('Save image quality must be between 0 and 100. Current val is: '.$quality);
		switch ($format){
			case 'png':
				\imagepng($this->image);
			break;
			case 'jpg':
			case 'jpeg':
				\imagejpeg($this->image);
			case 'gif':
				\imagegif($this->image);
			break;
		}
	}
	function outputBuffer($quality=50,$format='png'){
		\ob_start();
		$this->output($quality,$format);
		$image_data = \ob_get_contents();
		\ob_end_clean();
		return $image_data;
	}

	function __destruct() {
		if( $this->image !== \null && \get_resource_type($this->image) === 'gd' )\imagedestroy($this->image);
	}
}