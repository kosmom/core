<?php
namespace c;

// multiupload sample
/*
<input type="file" name="filename[]" multiple="true">

while (c\request::eachFile('filename')){
    move_upload_file(c\request::fileTmpName(),'upload_folder/file.png');
}
*/
// picture transform
/*
 c\request::fileToPic('filename')->resize(100)->save('pic/pic.png',50);
 */

/**
 * Request parameters file browser ip class
 * @author Kosmom <Kosmom.ru>
 */
class request{
	private static $result;
	private static $files=array();
	private static $counter=array();
	private static $isCMD;
	private static $manyfilesfile;
	
	static function isCmd($SERVER=null){
		if ($SERVER===null)$SERVER=$_SERVER;
		if (self::$isCMD!==null)return self::$isCMD;
		return self::$isCMD=(\PHP_SAPI==='cli' || empty($SERVER['REMOTE_ADDR']));
	}
	
	static function isAjax($SERVER=null){
		if ($SERVER===null)$SERVER=$_SERVER;
		return @$SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	}

	/**
	 * Current user IP
	 * @return string
	 */
	static function ip($SERVER=null){
		if ($SERVER===null)$SERVER=$_SERVER;
		return (isset($SERVER['HTTP_X_REAL_IP']))?$SERVER['HTTP_X_REAL_IP']:$SERVER['REMOTE_ADDR'];
	}

	static function url($SERVER=null){
		if ($SERVER===null)$SERVER=$_SERVER;
		return self::protocol($SERVER).self::domain($SERVER).$SERVER['REQUEST_URI'];
	}
	
	static function domain($SERVER=null){
		if ($SERVER===null)$SERVER=$_SERVER;
		return $SERVER['SERVER_NAME'];
	}
	
	static function protocol($SERVER=null){
		if ($SERVER===null)$SERVER=$_SERVER;
		if (self::isCmd($SERVER)) return false;
		return 'http'.(($SERVER['HTTP_X_FORWARDED_PROTO']=='https' || (!empty($SERVER['HTTPS']) && $SERVER['HTTPS']!=='off' || $SERVER['SERVER_PORT'] == 443))?'s':'').'://';
	}

	static function get($parameter,$default=null){
		return isset($_GET[$parameter])?$_GET[$parameter]:$default;
	}
	static function post($parameter,$default=null){
		return isset($_POST[$parameter])?$_POST[$parameter]:$default;
	}
	static function input($parameter,$default=null){
		if (isset($_REQUEST[$parameter]))return $_REQUEST[$parameter];
		return self::cookie($parameter, $default);
	}
	static function request($parameter,$default=null){
		return isset($_REQUEST[$parameter])?$_REQUEST[$parameter]:$default;
	}
	static function cookie($parameter,$default=null){
		return isset($_COOKIE[$parameter])?$_COOKIE[$parameter]:$default;
	}
	static function file($parameter='CoreEachFile',$default=null){
		if ($parameter=='CoreEachFile')return self::$files[self::$manyfilesfile][self::$counter[self::$manyfilesfile]];
		if (isset($_FILES[$parameter]))return $_FILES[$parameter];
		return $default;
	}
	static function fileCopy($parameter,$destination){
		@\mkdir(\dirname($destination),0777,true);
		return \move_uploaded_file(self::fileTmpName($parameter),$destination);
	}
	/*
	static function eachFileCopy($destination){
		@mkdir(dirname($destination),0777,true);
		return move_uploaded_file (self::fileTmpName(),$destination);
	}
	 */
	static function fileTmpName($parameter='CoreEachFile',$default=null){
		if ($parameter=='CoreEachFile')return self::$files[self::$manyfilesfile][self::$counter[self::$manyfilesfile]]['tmp_name'];
		return isset($_FILES[$parameter])?$_FILES[$parameter]['tmp_name']:$default;
	}
	static function fileName($parameter='CoreEachFile',$default=null){
		if ($parameter=='CoreEachFile')return self::$files[self::$manyfilesfile][self::$counter[self::$manyfilesfile]]['name'];
		return isset($_FILES[$parameter])?$_FILES[$parameter]['name']:$default;
	}
	static function fileSize($parameter='CoreEachFile',$default=null){
		if ($parameter=='CoreEachFile')return self::$files[self::$manyfilesfile][self::$counter[self::$manyfilesfile]]['size'];
		return isset($_FILES[$parameter])?$_FILES[$parameter]['size']:$default;
	}
	static function fileError($parameter='CoreEachFile',$default=null){
		if ($parameter=='CoreEachFile')return self::$files[self::$manyfilesfile][self::$counter[self::$manyfilesfile]]['error'];
		return isset($_FILES[$parameter])?$_FILES[$parameter]['error']:$default;
	}
	static function fileType($parameter='CoreEachFile',$default=null){
		if ($parameter=='CoreEachFile')return self::$files[self::$manyfilesfile][self::$counter[self::$manyfilesfile]]['type'];
		return isset($_FILES[$parameter])?$_FILES[$parameter]['type']:$default;
	}
	static function isFile($parameter){
		if (!isset($_FILES[$parameter]))return false;
		return $_FILES[$parameter]['error']!=4;
	}
	static function isValidFile($parameter){
		if (!isset($_FILES[$parameter]))return true;
		return self::fileError($parameter)===0;
	}
	static function fileToPic($parameter='CoreEachFile'){
		if (core::$debug && empty($_FILES))debug::consoleLog ('probably not set form enctype', error::WARNING);
		if (self::fileError($parameter)!==0 && self::fileError($parameter)!==4)throw new \Exception('Error on upload file '.self::fileError($parameter),1);
		if (!pics::isPic(self::fileTmpName($parameter)))throw new \Exception('Uploaded file is not a picture',2);
		return new pic(self::fileTmpName($parameter));
	}
	private static function manyFiles($file){
		self::$manyfilesfile=$file;
		self::$files[$file]=array();
		if (empty($_FILES[$file]['tmp_name'][0])){
			if (isset($_FILES[$file]['tmp_name'])){
				self::$files[$file][0]=array('name'=>$_FILES[$file]['name'][0],'type'=>$_FILES[$file]['type'][0],'tmp_name'=>$_FILES[$file]['tmp_name'][0],'error'=>$_FILES[$file]['error'][0],'size'=>$_FILES[$file]['size'][0]);
			}else{
				return self::$files[$file]=false;
			}
			return true;
		}
		foreach ($_FILES[$file]['tmp_name'] as $key=>$v){
			self::$files[$file][$key]=array('key'=>$key,'name'=>$_FILES[$file]['name'][$key],'type'=>$_FILES[$file]['type'][$key],'tmp_name'=>$v,'error'=>$_FILES[$file]['error'][$key],'size'=>$_FILES[$file]['size'][$key]);
		}
		return true;
	}
	static function eachFile($file){
		if (isset(self::$files[$file])){
			self::$counter[$file]++;
		}else{
			self::manyFiles($file);
		}
		if (!self::$files[$file])return false;
		if (self::$counter[$file]>=\count(self::$files[$file])){
			self::$counter[$file]=0;
			return false;
		}
		if (!isset(self::$counter[$file]))self::$counter[$file]=0;
		return self::$files[$file][self::$counter[$file]];
	}
	static function basicAuth($validateLoginPadd, $realm='Need auth'){
		if ($_SERVER['PHP_AUTH_USER']){
			$user=$_SERVER['PHP_AUTH_USER'];
			$pass=$_SERVER['PHP_AUTH_PW'];
			if (\is_array($validateLoginPadd)){
				if (isset($validateLoginPadd[$user]) && $validateLoginPadd[$user]==$pass)return $user;
			}elseif (\is_callable($validateLoginPadd)){
				if ($validateLoginPadd($user,$pass))return $user;
			}else{
				throw new \Exception('basicAuth parameter error');
			}
		}
		if (self::isCmd())return true;
		\header('WWW-Authenticate: Basic realm="'.$realm.'"');
		\header('HTTP/1.0 401 Unauthorized');
		die("Not authorized");
	}

	/**
	 * Detect browser names and versions of Chrome, Firefox, Internet Explorer, Opera & Safari.
	 * Returns array('name'=>Browser name (as written here ^),
	 * 				 'version'=>array(major version, minor subversion, release, build)).
	 * 'version' is array of integers.
	 * In case of no browser detected method returns array(null, array()).
	 * The code is actual on 2009-09-15.
	 *
	 * @author Leontyev Valera (feedbee@gmail.com)
	 * @copyright 2009
	 * @license BSD
	 */
	static function getBrowser($userAgent=null){
		if (!$userAgent)$userAgent=$_SERVER['HTTP_USER_AGENT'];
		if (isset(self::$result[$userAgent]))return self::$result[$userAgent];
		$name=null;
		$version=array(null,null,null,null);
		if (false!==\strpos($userAgent,'Opera/')){
			//http://www.useragentstring.com/pages/Opera/
			$name='Opera';
			if (false!==\strpos($userAgent,'Version/')){ // http://dev.opera.com/articles/view/opera-ua-string-changes/
				\preg_match('#Version/(\d{1,2})\.(\d{1,2})#i',$userAgent,$versionMatch);
				isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
				isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
			}else{
				\preg_match('#Opera/(\d{1,2})\.(\d{1,2})#i',$userAgent,$versionMatch);
				isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
				isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
			}
		}elseif (false !== \strpos($userAgent,'Opera ')){
			//http://www.useragentstring.com/pages/Opera/
			$name='Opera';
			\preg_match('#Opera (\d{1,2})\.(\d{1,2})#i', $userAgent, $versionMatch);
			isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
		}elseif (false !== \strpos($userAgent, 'Firefox/')){
			// http://www.useragentstring.com/pages/Firefox/
			$name='Firefox';
			\preg_match('#Firefox/(\d{1,2})\.(\d{1,2})(\.(\d{1,2})(\.(\d{1,2}))?)?#i',$userAgent,$versionMatch);
			isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
			isset($versionMatch[4]) && $version[2]=(int)$versionMatch[4];
			isset($versionMatch[6]) && $version[3]=(int)$versionMatch[6];
		}elseif (false !== \strpos($userAgent, 'MSIE ')){
			//http://www.useragentstring.com/pages/Internet%20Explorer/
			$name='Internet Explorer';
			\preg_match('#MSIE (\d{1,2})\.(\d{1,2})#i',$userAgent,$versionMatch);
			isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
		}elseif (false!==strpos($userAgent,'Iceweasel/')){ // Firefox in Debian
			// http://www.useragentstring.com/pages/Iceweasel/
			$name='Firefox'; //Iceweasel is identical to Firefox! no need to differt them
			\preg_match('#Iceweasel/(\d{1,2})\.(\d{1,2})(\.(\d{1,2})(\.(\d{1,2}))?)?#i',$userAgent,$versionMatch);
			isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
			isset($versionMatch[4]) && $version[2]=(int)$versionMatch[4];
			isset($versionMatch[6]) && $version[3]=(int)$versionMatch[6];
		}elseif (false!==\strpos($userAgent,'Chrome/')){
			// http://www.useragentstring.com/pages/Chrome/
			$name='Chrome';
			\preg_match('#Chrome/(\d{1,2})\.(\d{1,3})\.(\d{1,3}).(\d{1,3})#i',$userAgent,$versionMatch);
			isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
			isset($versionMatch[3]) && $version[2]=(int)$versionMatch[3];
			isset($versionMatch[4]) && $version[3]=(int)$versionMatch[4];
		}elseif (false!==\strpos($userAgent,'Safari/')){
			// http://www.useragentstring.com/pages/Safari/
			$name='Safari';
			/* Uncomment this block of code if u want to use Version/ tag
			* instead of Safari/Build tag. Old Safari browsers haven’t Version/ tag
			* and their version was marked as build number (ex. 528.16).
			if (false !== strpos($userAgent, 'Version/')) // old versions of Safari doesn't have Version tag in UserAgent
			{
			preg_match('#Version/(\d{1,2})\.(\d{1,2})(\.(\d{1,2}))?#i', $userAgent, $versionMatch);
			isset($versionMatch[1]) && $version[0] = (int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1] = (int)$versionMatch[2];
			isset($versionMatch[4]) && $version[2] = (int)$versionMatch[4];
			}
			else
			{*/
			\preg_match('#Safari/(\d{1,3})\.(\d{1,2})(\.(\d{1,2}))?#i',$userAgent,$versionMatch);
			isset($versionMatch[1]) && $version[0]=(int)$versionMatch[1];
			isset($versionMatch[2]) && $version[1]=(int)$versionMatch[2];
			isset($versionMatch[4]) && $version[2]=(int)$versionMatch[4];
			//}
		}
		return self::$result[$userAgent]=array('name'=>$name,'version'=>$version);
	}

	/**
	 * Compare browser versions.
	 *
	 * Returns int(0)  if version is aqual to $conditions,
	 * int(-1) if version is older than $conditions,
	 * int(1)  if version is newer than $conditions.
	 *
	 * Returns NULL in case of any error.
	 *
	 * @author Leontyev Valera (feedbee@gmail.com)
	 * @copyright 2009
	 * @license BSD
	 *
	 * @param array $browser -- result of self::detectBrowser() method
	 * @param $conditions -- vetsions to compre array('Opera'=>array(9, 4), 'Firefox'=>array(3, 1, 1), ...)
	 * @return int
	 */
	static function checkForBrowserVersion($browser,$conditions){
		if (!isset($browser['name']) || !isset($conditions[$browser['name']]) || !isset($browser['version']) || \count($browser['version'])<1)return null;
		$cnd=$conditions[$browser['name']]; // 0=>, 1=>, 2=>
		if (!\is_array($cnd))return null;
		$cnt=\count($cnd);
		for ($i=0;$i<$cnt;$i++){
			if ($browser['version'][$i]<$cnd[$i]){
				return -1;
			}else if ($browser['version'][$i]>$cnd[$i]){
				return 1;
			}
		}
		return 0;
	}

	/**
	 * visitor is bot
	 *
	 * @param string $USER_AGENT
	 * @return string
	 */
	static function spider($USER_AGENT=''){
	if ($USER_AGENT=='')$USER_AGENT=$_SERVER['HTTP_USER_AGENT'];
	$engines = array(
	'Aport'=>'Aport robot',
	'Google'=>'Google',
	'msnbot'=>'MSN',
	'Rambler'=>'Rambler',
	'Yahoo'=>'Yahoo',
	'AbachoBOT'=>'AbachoBOT',
	'accoona'=>'Accoona',
	'AcoiRobot'=>'AcoiRobot',
	'ASPSeek'=>'ASPSeek',
	'CrocCrawler'=>'CrocCrawler',
	'Dumbot'=>'Dumbot',
	'FAST-WebCrawler'=>'FAST-WebCrawler',
	'GeonaBot'=>'GeonaBot',
	'Gigabot'=>'Gigabot',
	'Lycos'=>'Lycos spider',
	'MSRBOT'=>'MSRBOT',
	'Scooter'=>'Altavista robot',
	'AltaVista'=>'Altavista robot',
	'WebAlta'=>'WebAlta',
	'IDBot'=>'ID-Search Bot',
	'eStyle'=>'eStyle Bot',
	'Mail.Ru'=>'Mail.Ru Bot',
	'Scrubby'=>'Scrubby robot',
	'Yandex'=>'Yandex',
	'YaDirectBot'=>'Yandex Direct');

	foreach ($engines as $key=>$engine){
		if (\strstr($USER_AGENT,$key))return $engine;
	}

	return false;
	}
}