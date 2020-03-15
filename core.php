<?php
namespace c;

/**
 * Core main class
 * @author Kosmom <Kosmom.ru>
 * @link: https://core.kosmom.ru
 */
class core {
	const WINDOWS1251='WINDOWS-1251';
	const UTF8='UTF-8';
	const VERSION='3.5';
	const DATA_DB_ARRAY=0;
	const DATA_DB_COLLECTON=1;
	const DATA_DB_COLLECTON_OBJECT=2;
	const DATA_DB_SPL_FIXED_ARRAY=3;

	/**
	 * Project charset
	 * @var string
	 */
	static $charset=self::UTF8;

	/**
	 * Project enviroment
	 * @var string
	 */
	static $env='';

	/**
	 * User language
	 * @var string
	 */
	static $lang;

	/**
	 * Debuging project
	 * @var boolean
	 */
	static $debug=false;

	/**
	 * Ajax render style
	 * @var boolean
	 */
	static $ajax=false;

	/**
	 * Partition Page render style
	 * @var boolean
	 */
	static $partition=false;

	/**
	 * Version of application
	 * @var string
	 */
	static $version;

	/**
	 * Data srorage
	 *
	 * @var array
	 * @param db
	 * @param mail
	 * @param roles
	 * @param db_autobind
	 * @param db_exception
	 * @param controller_exception_page
	 * @param table_header_render_callback
	 * @param render
	 */
	static $data=array('db_autobind'=>array(),'render'=>'bootstrap3','db_describe_cache'=>array());
	static $watch = array();

	static function watch($key,&$var){
		self::$watch[$key]=&$var;
	}

	static function load($class){
			if (substr($class,0,2)=='c\\'){
			$f= str_replace('\\','/',substr($class,2)).'.php';
			if (isset(self::$data['include_dir']) && file_exists(self::$data['include_dir'].'/'.$f)){
				include self::$data['include_dir'].'/'.$f;
				return true;
			}
			if (isset(self::$data['include_class_dir']) && file_exists(self::$data['include_class_dir'].'/'.$f)){
				include self::$data['include_class_dir'].'/'.$f;
				return true;
			}
			if (file_exists(__DIR__.'/'.$f)){
				include __DIR__.'/'.$f;
				return true;
			}
			if (file_exists(__DIR__.'/model/'.$f)){
				include __DIR__.'/model/'.$f;
				return true;
			}
		}else{
			$f=$class.'.php';
			if (file_exists(__DIR__.'/models/'.$f)){
				include __DIR__.'/models/'.$f;
				return true;
			}
			if (isset(self::$data['include_dir']) && file_exists(self::$data['include_dir'].'/'.$f)){
			include self::$data['include_dir'].'/'.$f;
			return true;
			}
		}
		return false;
	}
}
spl_autoload_register('c\\core::load');

class super{
	function filter(&$var,$filters=''){
		return input::filter($var,$filters);
	}
	function addSuccess($text,$redirect){
		return error::addSuccess($text,$redirect);
	}
	function addError($text,$redirect){
		return error::addError($text,$redirect);
	}
	function addWarning($text,$redirect){
		return error::addWarning($text,$redirect);
	}
	/**
	 * Set response header
	 * @param int $code
	 * @return super|false
	 */
	function header($code){
		return error::header($code);
	}
	function redirect($url=null,$__DIR__=null){
		if ($__DIR__===null){
			error::redirect($url);
		}else{
			mvc::redirect($url,$__DIR__);
		}
	}
	function redirectToRoute($route_name,$params=array()){
		mvc::redirectToRoute($route_name,$params);
	}
	function log($filename,$string){
		return error::log($filename,$string);
	}
	function addCss($css){
		return mvc::addCss($css);
	}
	function addJs($js,$isComponent=false){
		return mvc::addJs($js,$isComponent);
	}
	function addComponent($component){
		return mvc::addComponent($component);
	}
	function addScript($js){
		return mvc::addScript($js);
	}
	function addJsVarAsArray($var,$value=null){
		return mvc::addJsVarAsArray($var,$value);
	}
	function thrownException($message,$code=0){
		throw new \Exception($message,$code);
	}
}

function translate($text,$vars=array()){
	return translate::t($text,$vars);
}
function t($text,$vars=array()){
	return translate::t($text,$vars);
}
function curl($URL,$post=null,$cookieFile=null,$options=array()){
	return curl::getContent($URL, $post, $cookieFile, $options);
}
function config(){
	return new config('app');
}