<?php
namespace c;

/**
 * Cache compress class
 * @author Kosmom <Kosmom.ru>
 */
class cache{
	private static $cacheTimeout=0;
	private static $cachePath='cache';
	private static $replaces=array();
	static $cachefolder='cache';
	
	static function refresh_page($link){
		$cachefile=self::$cachefolder.'/'.md5($link).'.gz';
		if (file_exists($cachefile)) unlink($cachefile);
		if (isset($_SESSION)){
			$temp=$_SESSION;
			unset($_SESSION);
		}
		$text=file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/'.$link);
		if (!$text)return false;
		file_put_contents($cachefile,gzencode(self::compressHtml($text),9));
		if (isset($temp))$_SESSION=$temp;
		return true;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function compress_html($compress){
		return self::compressHtml($compress);
	}
	static function compressHtml($compress){
		self::$replaces=array();
		$compress=self::decodeHtml($compress);
		$compress=preg_replace('/\s+/',' ',$compress);
		if (empty(self::$replaces))return $compress;
		return self::encodeHtml($compress);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function decode_html($text){
		return self::decodeHtml($text);
	}
	static function decodeHtml($text){
		return preg_replace_callback('/<pre[>\s].*<\/pre>|<img.*>|<script>.*<\/script>/sU','self::decodes',$text);
	}
	private static function decodes($matches){
		array_push(self::$replaces,$matches[0]);
		return '{{{replace}}}';
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function encode_html($text){
		return self::encodeHtml($text);
	}
	static function encodeHtml($text){
		return preg_replace_callback('/{{{replace}}}/','self::encodes', $text);
	}
	private static function encodes($matches){
		return array_shift(self::$replaces);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function clear_page($link){
		return self::clearPage($link);
	}
	static function clearPage($link){
		$cachefile=self::$cachefolder.'/'.md5($link).'.gz';
		if (file_exists($cachefile)) unlink($cachefile);
		return true;
	}
	static function delete($key){
		return self::clear($key);
	}
	static function clear($key=null){
		if ($key===null){
			if (!$handle=opendir(self::$cachefolder))return false;
			while(false !== ($file=readdir($handle))){
				if ($file == '.' or $file == '..' or (substr($file,-6) != '.cache' && substr($file,-3) != '.gz') or is_dir($file)) continue;
				unlink(self::$cachefolder.'/'.$file);
			}
			closedir($handle);
			return true;
		}
		if (strpos($key,'*')===false)return unlink(self::$cachefolder.'/'.$key.'.cache');
		foreach (glob(self::$cachefolder.'/'.$key.'.cache') as $file)unlink($file);
	}
	static function check($files){
		$compare=array();
		foreach($files as $key=>$item){
			if ($key=='index')$key='';
			$compare[md5($key).'.gz']=1;
		}
		$files=0;
		if (!$handle=opendir(self::$cachefolder))return false;
		while(false !== ($file=readdir($handle))){
			if ($file == '.' or $file == '..' or substr($file,-3) != '.gz' or is_dir($file) or !$compare[$file])return false;
			$files++;
		}
		closedir($handle);
		return sizeof($compare)==$files;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function is_cleared(){
		return self::isCleared();
	}
	static function isCleared(){
		if (!$handle=opendir(self::$cachefolder))return ;
		while(false !== ($file=readdir($handle))){
			if ($file == '.' or $file == '..' or substr($file,-3) != '.gz' or is_dir($file)) continue;
			return false;
		}
		closedir($handle);
		return true;
	}

	/**
	 * Get or set timeout parameter
	 * @param null|int $timeout
	 * @return int
	 */
	static function timeout($timeout=null){
		if ($timeout===null)return self::$cacheTimeout;
		return self::$cacheTimeout=(int)$timeout;
	}

	/**
	 * Get or set path parameter
	 * @param null|string $path
	 * @return string
	 */
	static function path($path=null){
		if ($path===null)return self::$cachePath;
		return self::$cachePath=(string)$path;
	}

	/**
	 * Get key from cache
	 * @param string $key
	 * @param mixed $callback If callback type - callback with set key. Else - default value
	 * @param null|int $timeout
	 * @return mixed
	 */
	static function get($key,$callback=null,$timeout=null) {
		if ($timeout===null)$timeout=isset(core::$data['cacheTimeout'])?core::$data['cacheTimeout']:self::$cacheTimeout;
		$path=isset(core::$data['cachePath'])?core::$data['cachePath']:self::$cachePath;
		$fullpath=$path.'/'.$key.'.cache';
		$mtime=@filemtime($fullpath);
		if ($mtime===false or ($timeout>0 && time() - $mtime > $timeout)){
			if (!is_callable($callback))return $callback;
			$rs=$callback($key);
			filedata::savedata($fullpath, $rs);
			$mtime=filemtime($fullpath);
			clearstatcache(false,$fullpath);
			return $rs;
		}
		return filedata::loaddata($fullpath);
	}
	static function getMultiple($keys,$default=null,$timeout=null){
		$out=array();
		foreach ($keys as $key){
			$out[$key]=self::get($key,$default,$timeout);
		}
		return $out;
	}
	static function deleteMultiple($keys){
		foreach ($keys as $key){
			self::delete($key);
		}
	}
	static function setMultiple($values){
		foreach ($values as $key=>$value){
			self::set($key, $value);
		}
	}
	static function set($key, $value) {
		$path=isset(core::$data['cachePath'])?core::$data['cachePath']:self::$cachePath;
		$fullpath=$path.'/'.$key.'.cache';
		filedata::savedata($fullpath, $value);
	}
	static function has($key,$timeout=null){
		if ($timeout===null)$timeout=isset(core::$data['cacheTimeout'])?core::$data['cacheTimeout']:self::$cacheTimeout;
		$path=isset(core::$data['cachePath'])?core::$data['cachePath']:self::$cachePath;
		$fullpath=$path.'/'.$key.'.cache';
		$mtime=@filemtime($fullpath);
		return !($mtime===false or ($timeout>0 && time() - $mtime > $timeout));
	}
}