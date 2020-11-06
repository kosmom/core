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
	const HEADER=5;
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
	 * @return super
	 */
	static function add($text,$type=self::ERROR,$redirect=\false,$header='',$key=\null){
		if (core::$debug){
			debug::trace('Error message added to stack',error::SUCCESS,array('text'=>$text,'type'=>$type));
			if (\is_string($type))debug::trace('Error added without use const. Please, use error type as c\\error::ERROR const',error::WARNING);
		}
		if (isset(core::$data['error_callback'])){
			\call_user_func(core::$data['error_callback'],$text,$type);
		}elseif ($key===\null){
			$_SESSION['errors'][self::toType($type)][]=$text;
		}else{
			$_SESSION['errors'][self::toType($type)][$key]=$text;
		}
		if (core::$debug && \session_id() === '')debug::trace('Probably you forget start session session_start()',error::WARNING);
		if ($redirect!==\false)self::redirect($redirect,$header);
		return new super();
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_success($text,$redirect=\false,$header=''){
		return self::addSuccess($text, $redirect, $header);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_error($text,$redirect=\false,$header=''){
		return self::addError($text, $redirect, $header);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_warning($text,$redirect=\false,$header=''){
		return self::addWarning($text, $redirect, $header);
	}
	static function addSuccess($text,$redirect=\false,$header=''){
		return self::add($text,self::SUCCESS,$redirect,$header);
	}
	static function addError($text,$redirect=\false,$header=''){
		return self::add($text,self::ERROR,$redirect,$header);
	}
	static function addWarning($text,$redirect=\false,$header=''){
		return self::add($text,self::WARNING,$redirect,$header);
	}

	static function setSuccess($key, $text,$redirect=\false,$header=''){
		return self::add($text,self::SUCCESS,$redirect,$header,$key);
	}
	static function setError($key, $text,$redirect=\false,$header=''){
		return self::add($text,self::ERROR,$redirect,$header,$key);
	}
	static function setWarning($key, $text,$redirect=\false,$header=''){
		return self::add($text,self::WARNING,$redirect,$header,$key);
	}
	
	static function getError($key){
		return $_SESSION['errors'][self::ERROR][$key];
	}
	static function getSuccess($key){
		return $_SESSION['errors'][self::SUCCESS][$key];
	}
	static function getWarning($key){
		return $_SESSION['errors'][self::WARNING][$key];
	}
	static function getErrorAndClean($key){
		$rs=$_SESSION['errors'][self::ERROR][$key];
		unset($_SESSION['errors'][self::ERROR][$key]);
		return $rs;
	}
	static function getSuccessAndClean($key){
		$rs=$_SESSION['errors'][self::SUCCESS][$key];
		unset($_SESSION['errors'][self::SUCCESS][$key]);
		return $rs;
	}
	static function getWarningAndClean($key){
		$rs=$_SESSION['errors'][self::WARNING][$key];
		unset($_SESSION['errors'][self::WARNING][$key]);
		return $rs;
	}
	static function getKeyMessages($key){
		return [
			'errors'=>self::getError($key),
			'warnings'=>self::getWarning($key),
			'success'=>self::getSuccess($key),
		];
	}
	
	
	/**
	 * Buffere error count
	 * @param int $type error type
	 * @return integer
	 */
	static function count($type=self::ERROR){
			if (empty($_SESSION['errors']))return 0;
			return @\count($_SESSION['errors'][self::toType($type)]);
	}
	static function size($type=self::ERROR){
		return self::count($type);
	}
	static function countMessages(){
		return self::size()+self::size(self::WARNING)+self::size(self::SUCCESS);
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
		return responce::header($code);
	}
	/**
	 * Redirect
	 * @param string $url redirect URL
	 * @param integer $header redirect with header
	 */
	static function redirect($url=\null,$header=''){
		return responce::redirect($url,$header);
	}
	/**
	 * Log in file
	 * @param string $filename log filename
	 * @param string|integer|array $string string
	 */
	static function log($filename,$string){
		if (\is_array($string) or \is_object($string))$string=\print_r($string,\true);
		if (!\file_put_contents($filename, "\n".$string, \FILE_APPEND | \LOCK_EX))\file_put_contents($filename, "\n".$string);
		return new super();
	}
}