<?php
namespace c;

/**
 * Work with local files
 * @author Kosmom <Kosmom.ru>
 */
class filedata{
	static $mime_types = array(
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'php' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',
		'flv' => 'video/x-flv',

		// images
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// archives
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'exe' => 'application/x-msdownload',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',

		// audio/video
		'mp3' => 'audio/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',

		// adobe
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',

		// ms office
		'doc' => 'application/msword',
		'docx' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.ms-powerpoint',

		// open office
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	);
	/**
	 * Get stored data from file
	 * @param string $filename
	 * @return array
	 */
	static function loaddata($filename){
		return @include $filename;
	}

	/**
	 * Store $data to file
	 * @param string $filename
	 * @param array $data
	 */
	static function savedata($filename,$data){
		file_put_contents($filename,'<?php return '.self::varExportMin($data).';');
	}

	private static function varExportMin($var) {
		if (is_array($var)) {
			$toImplode = array();
			foreach ($var as $key => $value) {
				$toImplode[] = var_export($key, true).'=>'.self::varExportMin($value);
			}
			return 'array('.implode(',', $toImplode).')';
		} else {
			return var_export($var, 1);
		}
	}
	/**
	 * Recursive remove directory
	 * @param string $dir
	 * @return bool
	 */
	static function rmdir($dir){
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir($dir.'/'.$file)) ? self::rmdir($dir.'/'.$file) : unlink($dir.'/'.$file);
		}
		return rmdir($dir);
	}
	/**
	 * Same as rmdir
	 * @param string $dir
	 * @return bool
	 */
	static function remove($dir){
		return self::rmdir($dir);
	}
	/**
	 * Empty directory
	 * @param string $dir
	 */
	static function empt($dir){
		foreach (array_diff(scandir($dir), array('.','..')) as $file) {
			(is_dir($dir.'/'.$file)) ? self::rmdir($dir.'/'.$file) : unlink($dir.'/'.$file);
		}
	}
	static function clean($pattern,$mTimeAgo=2592000){
		foreach (glob($pattern) as $file){
			if (filemtime($file)<time()-$mTimeAgo)unlink ($file);
		}
	}
	
	/**
	 * Get full list in folder with mask
	 * @param string $src_dir
	 * @param string $mask preg match mask of filename
	 * @return array|boolean
	 */
	static function filelist($src_dir,$mask='',$callback=null){
		if (!is_dir($src_dir))return false;
		$dir = opendir($src_dir);
		$out=array();
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file == '.' ) || ( $file == '..' ))continue;
			if ( is_dir($src_dir . DIRECTORY_SEPARATOR . $file) ) {
				$out+=self::filelist($src_dir . DIRECTORY_SEPARATOR . $file,$mask,$callback);
			} elseif (!$mask || preg_match($mask, $file)){
				$out[$src_dir . DIRECTORY_SEPARATOR . $file]=is_callable($callback)?$callback($src_dir . DIRECTORY_SEPARATOR . $file,$file,$src_dir):true;
			}
		}
		closedir($dir);
		return $out;
	}
	
	/**
	 * Recursivety copy directory
	 * @param string $src source directory
	 * @param string $dst destitation directory
	 */
	static function copy($src,$dst){
		if (!is_dir($src))return false;
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file == '.' ) || ( $file == '..' ))continue;
			if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
				self::copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
			} else {
				copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
			}
		}
		closedir($dir);
	}

	/**
	 * Get extension of filename
	 * @param string $filename
	 * @return string
	 */
	static function extension($filename){
		return pathinfo($filename, PATHINFO_EXTENSION);
	}
	static function readfile($path,$filename,$attachment=true,$type=null){
		if (!file_exists($path))throw new \Exception('file not exists');
		if ($type===null)header('Content-Type: '.($type?(self::$mime_types[$type]?self::$mime_types[$type]:$type): mime_content_type($path)));
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: '.($attachment?'attachment;':'').' filename="'.$filename.'"');
		readfile($path);
		die();
	}
	/**
	 * Get mime type by content or extension
	 * @param string $filename
	 * @return string|boolean
	 */
	static function mime_content_type($filename){
		if ($type=@mime_content_type($filename))return $type;
		if ($type=self::$mime_types[self::extension($filename)])return $type;
		return false;
	}
}