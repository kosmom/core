<?php
namespace c;

/**
 * Error logs buffer class
 * @author Kosmom <Kosmom.ru>
 */
class error{
	const ERROR=1;
	const WARNING=2;
	const SUCCESS=3;
	const INFO=4;
	private static function toType($error){
		switch ($error){
			case 'error':return self::ERROR;
			case 'warning':return self::WARNING;
			case 'success':return self::SUCCESS;
		}
		return $error;
	}
	/**
	 * Add error to buffer
	 * @param string $text error text
	 * @param int $type error type
	 * @param string|boolean $redirect redirect after error
	 * @param int $header redirect type after error
	 */
	static function add($text,$type=self::ERROR,$redirect=false,$header=''){
		if (core::$debug){
			debug::trace('Error message added to stack',error::SUCCESS,array('text'=>$text,'type'=>$type));
			if (is_string($type))debug::trace('Error added without use const. Please, use error type as c\\error::ERROR const',error::WARNING);
		}
		if (isset(core::$data['error_callback'])){
			call_user_func(core::$data['error_callback'],$text,$type);
		}else{
			$_SESSION['errors'][self::toType($type)][]=$text;
		}
		if (core::$debug && session_id() === '')debug::trace('Probably you forget start session session_start()',error::WARNING);
		if ($redirect!==false)self::redirect($redirect,$header);
		return new super();
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_success($text,$redirect=false,$header=''){
		return self::addSuccess($text, $redirect, $header);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_error($text,$redirect=false,$header=''){
		return self::addError($text, $redirect, $header);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_warning($text,$redirect=false,$header=''){
		return self::addWarning($text, $redirect, $header);
	}
	static function addSuccess($text,$redirect=false,$header=''){
		self::add($text,self::SUCCESS,$redirect,$header);
		return new super();
	}
	static function addError($text,$redirect=false,$header=''){
		self::add($text,self::ERROR,$redirect,$header);
		return new super();
	}
	static function addWarning($text,$redirect=false,$header=''){
		self::add($text,self::WARNING,$redirect,$header);
		return new super();
	}

	/**
	 * Buffere error count
	 * @param int $type error type
	 * @return integer
	 */
	static function count($type=self::ERROR){
			if (empty($_SESSION['errors']))return 0;
			return @count($_SESSION['errors'][self::toType($type)]);
	}
	static function size($type=self::ERROR){
		return self::count($type);
	}

	static function thrownException($message,$code=0){
		throw new \Exception($message,$code);
	}
	/**
	 * Release error buffer
	 * @return array
	 */
	static function errors(){
		$temp=$_SESSION['errors'][self::ERROR];
		unset($_SESSION['errors'][self::ERROR]);
		return $temp;
	}
	/**
	 * Release warning buffer
	 * @return array
	 */
	static function warnings(){
		$temp=$_SESSION['errors'][self::WARNING];
		unset($_SESSION['errors'][self::WARNING]);
		return $temp;
	}
	/**
	 * Release success buffer
	 * @return array
	 */
	static function success(){
		$temp=$_SESSION['errors'][self::SUCCESS];
		unset($_SESSION['errors'][self::SUCCESS]);
		return $temp;
	}
	/**
	 * Add page header
	 * @param integer $code
	 * @return super|false
	 */
	static function header($code){
		if (headers_sent())return false;
		switch ($code){
			case 404:
				header('HTTP/1.1 404 Not Found');break;
			case 301:
				header('HTTP/1.1 301 Moved Permanently');break;
			default:
			if (core::$debug)debug::trace('Redirect header mistmatch in dictionary',error::WARNING,$code);
		}
		return new super();
	}
	/**
	 * Redirect
	 * @param string $url redirect URL
	 * @param integer $header redirect with header
	 */
	static function redirect($url=null,$header=''){
		if (core::$ajax)ajax::redirect($url);
		if (core::$debug && empty(core::$data['miss_debug_redirect'])){
			debug::trace('Redirect debug mode',self::INFO,array('url'=>$url,'header'=>$header));
			if (headers_sent())debug::trace('Redirect. Headers already sended',self::WARNING);
			die('<a href="'.iconv('utf-8','windows-1251',$url).'"><h1>'.translate::t('Core Debug mode: Click to continue').'</h1></a><style>h1{position:relative;top:50%;}a{text-align:center;}a,body,html{font-family:arial;width:100%;position:absolute;top:0px;height:100%;padding:0;border:0;margin:0;outline:none;cursor:pointer;}</style>'.mvc::drawJs(true));
		}
		if (!headers_sent()){
			if ($header!='')self::header($header);
			if ($url=='')$url=$_SERVER['REQUEST_URI'];
			header('Location: '.$url);
		}
		$url=$url==''?'location.href':"'".$url."'";
		die('<script>location.replace('.$url.');</script><noscript><meta http-equiv="Refresh" content="0;URL='.$url.'"></noscript>');
	}
	/**
	 * Log in file
	 * @param string $filename log filename
	 * @param string $string string
	 */
	static function log($filename,$string){
		if (!file_put_contents($filename, "\n".$string, FILE_APPEND | LOCK_EX))file_put_contents($filename, "\n".$string);
		return new super();
	}
}