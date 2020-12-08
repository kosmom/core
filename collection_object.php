<?php
namespace c;

/**
 * Collection object ORM class
 * @author Kosmom <Kosmom.ru>
 */
class collection_object extends collection{
	var $generator;
	var $pk_field;
	var $connection;
	var $is_full;
	function __construct($array,$generator=\null,$pk_field=\null,$is_full=\false){
		parent::__construct($array);
		$this->generator=$generator;
		$this->pk_field=$pk_field;
		$this->is_full=$is_full;
	}
	function current(){
		return (new $this->generator($this->_c[$this->_p],$this));
	}
	function out($array){
		return new collection_object($array);
	}
	function offsetGet($offset){
		return $this->offsetExists($offset)?(new $this->generator($this->_c[$offset],$this)):\null;
	}
	function first(){
		foreach ($this->_c as $item){
			return (new $this->generator($this->_c[0],$this));
		}
	}
	function pluck($column){
		$out=array();
		foreach ($this->_c as $key=>$item){
			$out[$key]=model::getData($this->generator, $item, $column);
		}
		return $out;
	}
	function table($header=array()){
		$attrs=array();
		/*
			foreach ($this->fieldAttributes as $key=>$attr){
			$attrs[$key]=$attr['field_prop'];
			if (isset($attr['field_prop']['label']))$attrs[$key]['name']=$attr['field_prop']['label'];
		}
		 */
		return new table($this->_c->toArray(),$attrs);
	}
	function save(){
		foreach ($this->_c as $key){
			$a=new $this->generator($key);
			$a->save();
		}
	}
	function toArray(){
		$out=array();
		foreach ($this->_c as $key=>$item){
			$out[$key]= model::getData($this->generator, $item);
		}
		return $out;
	}
	function keys(){
		return $this->_c->toArray();
	}
}