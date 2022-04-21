<?php
namespace c;

/**
 * Collection class for ORM
 * @author Kosmom <Kosmom.ru>
 */
class collection implements \ArrayAccess, \Countable, \Iterator, \Serializable{
	protected $_c; // container
	protected $_p=0; // position

	function __construct($array=\null){
		if (\is_array($array))$this->_c= \SplFixedArray::fromArray($array);
	}

	function offsetExists($offset){
		return isset($this->_c[$offset]);
	}

	function offsetGet($offset){
		return $this->offsetExists($offset)?$this->_c[$offset]:\null;
	}

	function offsetSet($offset, $value){
		if (\is_null($offset)){
			$this->_c[]=$value;
		}else{
			$this->_c[$offset]=$value;
		}
	}

	function pluck($column){
		return datawork::group($this->toArray(), '[]',$column);
	}
	function map($callback){
		return \array_map($callback, $this->toArray());
	}
	function implode($callback, $glue=', '){
		return \implode($glue, $this->map($callback));
	}
	
	function offsetUnset($offset){
		unset($this->_c[$offset]);
	}

	function rewind(){
		$this->_p=0;
	}
	
	function exists(){
		return $this->count()>0;
	}

	/**
	 * @return array
	 */
	function current(){
		return $this->_c[$this->_p];
	}

	function key(){
		return $this->_p;
	}

	function next(){
		++$this->_p;
	}

	function valid(){
		return isset($this->_c[$this->_p]);
	}

	function count(){
		return \count($this->_c);
	}

	function serialize(){
		return \serialize($this->_c);
	}

	function json_encode(){
		return input::jsonEncode($this->_c);
	}
	function jsonEncode(){
		return input::jsonEncode($this->_c);
	}

	function unserialize($data){
		$this->_c=\unserialize($data);
	}

	function __invoke(array $data=\null){
		if (\is_null($data))return $this->toArray();
		$this->_c=$data;
	}

	function toArray(){
		return $this->_c->toArray();
	}

	function group($key, $val=\false){
		return datawork::group($this->toArray(), $key, $val);
	}

	function tree($keyField, $parentField, $childrenName='children', $mainbranch=\null){
		return datawork::tree($this->toArray(), $keyField, $parentField, $childrenName, $mainbranch);
	}

	function first(){
		foreach ($this->_c as $item)return $item;
	}

	function table($header=array()){
		return new table($this->toArray(),$header);
	}

	function dd(){
		\var_dump($this->toArray());
		die();
	}

	function last(){
		return \end($this->_c);
	}

	function toObject(){
		return new collection_object($this->toArray());
	}

	function __toString(){
		return input::json_encode($this->_c);
	}
	function where($field,$prop,$value=\null){
		if ($prop=='='){
			return $this->out(array_filter($this->toArray(),function($row) use ($field,$value){
				return ($row[$field]==$value);
			}));
		}
		//add other props
	}
	function out($array){
		return new collection($array);
	}

	function chunk($size){
		$chunks=array();
		foreach (\array_chunk($this->toArray(),$size) as $chunk){
			$chunks[]=new static($chunk);
		}
		return $chunks;
	}
}