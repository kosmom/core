<?php
namespace c;

class config{
	private static $_data=array();
	private static $_call=array();
	private $key;

	function __set($name, $value){
		self::$_data[$this->key][$name]=$value;
	}
	function __get($name){
		return self::$_data[$this->key][$name];
	}
	function __call($name,$arguments){
		if (!isset(self::$_call[$name]))self::$_call[$name]=new config($this->key.'_'.$name);
		return self::$_call[$name];
	}
	function __construct($key='app') {
		$this->key=$key;
	}
}