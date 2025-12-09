<?php
namespace c;
class arrayaccess implements \ArrayAccess{
	function __construct($array=null){
		foreach ($array as $key=>$item){
			$this->$key=$item;
		}
	}
	function offsetExists($offset){
		return isset($this->$offset);
	}
	function offsetGet($o){
		return property_exists($this,$o)?$this->$o:null;
	}
	function offsetSet($o, $v){
		$this->$o=$v;
	}
	function __get($o) {
		return property_exists($this,$o)?$this->$o:null;
	}
	function offsetUnset($o){
		unset($this->$o);
	}
}