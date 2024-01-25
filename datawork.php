<?php
namespace c;

/**
 * Work with array data
 * @author Kosmom <Kosmom.ru>
*/
class datawork{
	const FORMS='{{c\forms}}';
	const KEY='{{!KEY!}}';
	static $header=array();
	static $numstring=\false; // first string after header - is number string 1 2 3 4 5...
	private static $branch=array();
	/**
	* Convert array to c\datawork::header struct
	*/
	static function tag($dataWithHeader,$headers=\null){
		if (empty(self::$header) && empty($headers)) throw new \Exception('Need set c\\datawork::$header array first');
		if (empty($headers))$headers=self::$header;
		// 1st string - header
		$data_header=\array_shift($dataWithHeader);
		$link_header=array();
		// for each column find match
		foreach($data_header as $num=> $name){
			$name=trim($name);
			if (!isset($headers[$name]))continue;
			$link_header[$num]=$headers[$name];
		}
		if (empty($link_header)) throw new \Exception('Headers not match');
		if (self::$numstring){
			$data_header=\array_shift($dataWithHeader);
			foreach ($data_header as $key=>$item){
				if (($key+1)!=$item && !empty($item))  throw new \Exception('Error in number string');
			}
		}

		$out=array();
		foreach($dataWithHeader as $item){
			$row=array();
			foreach($link_header as $num=> $name)$row[$name]=$item[$num];
			$out[]=$row;
		}
		return $out;
	}

	/**
	 * same group function
		 * @deprecated since version 3.4
	 */
	static function key($array,$key,$val=\false){
		return self::group($array,$key,$val);
	}

	/**
	 * Prepare array element as array
	 * @param array $format
	 * @param string|array $key
	 * @param string|array $item
	 */
	private static function arrayGroup($format,$key,$item){
		// sample: c\datawork::group($rs,array('k1','k2'),array('k1'=>'k1',k2'=>c\datawork::KEY,'k3'=>function($row){ return $row['k1']});
		$out=array();
		foreach ($format as $key=>$val){
			if (\is_object($val)){
				$out[$key]=$val($item,$key);
			}elseif (\is_array($val)){
				$out[$key]=self::arrayGroup($val,$key,$item);
			}elseif ($val===self::KEY){
				$out[$key]=$key;
			}else{
				$out[$key]=$item[$val];
			}
		}
		return $out;
	}

	static function flatten($array,$concat='.', $prefix = '') {
		$result = array();
		foreach($array as $key=>$value) {
			if(\is_array($value)) {
				$result+= self::flatten($value,$concat, $prefix . $key . $concat);
			}else {
				$result[$prefix.$key] = $value;
			}
		}
		return $result;
	}

	static function unflatten($array,$concat='.'){
		$buffer=array();
		foreach ($array as $key=>$value){
			$keys=  \explode($concat, $key);
			switch (\count($keys)){
				case 5:
					$buffer[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$keys[4]]=$value;
				break;
				case 4: 
					$buffer[$keys[0]][$keys[1]][$keys[2]][$keys[3]]=$value;
				break;
				case 3:
					$buffer[$keys[0]][$keys[1]][$keys[2]]=$value;
				break;
				case 2:
					$buffer[$keys[0]][$keys[1]]=$value;
				break;
				case 1:
					$buffer[$keys[0]]=$value;
				break;
			}
		}
		return $buffer;
	}


	/**
	 * Transform array to $key var as key.
	 * @param array|\SplFixedArray $array soruce array
	 * @param string|array $key key element of $array. if type is array - group result for all parametars. Up to 4 params array
	 * @param string|boolean|callable $val if not exist will be return $array[$key]=>$item, if exists will be return $array[$key]=>$array[$val]
	 * @return array
	 */
	static function group($array,$key,$val=\false){
		if ($array instanceof \SplFixedArray)$array=$array->toArray();

		if (!\is_array($array) && !$array instanceof collection && !$array instanceof collection_object){
			if (core::$debug){
				debug::group('Datawork key group operation');
				debug::trace('Array is not array',error::WARNING);
				debug::groupEnd();
				debug::trace('Datawork key group operation - array is not array',error::WARNING);
			}
			return \false;
		}
		if (!\is_array($key))$key=array($key);
		$k0=$key[0];
		if (\sizeof($key)>2){
			foreach ($key as $item){
				if (\is_string($item)) continue;
				if (\is_bool($item)) continue;
				return self::key2($array,$key,$val);
			}
		}
		if (\PHP_VERSION_ID>=50500 && $k0=='[]' && !\is_object($val) && !\is_array($val) && $val!==\true && $val!==\false && \is_array($array)){
			return \array_column($array,$val);
		}
		if (\PHP_VERSION_ID>=50500 && empty($key[1]) && $val!=self::KEY && $key[0]!=self::KEY && !\is_object($val) && !\is_array($val) && !\is_object($k0) && !\is_array($k0) && $val!==\true && $val!==\false && \is_array($array)){
			return \array_column($array,$val,$k0);
		}
		$out=array();
		if ($array instanceof collection_object){
			if ($k0=='[]'){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$i)$out[]=$tkey;
				}else{
					foreach ($array as $item)$out[]=$item->$val;
				}
			}elseif ($k0===self::KEY){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$tkey]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $tkey=>$item)$out[$tkey]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $tkey=>$item)$out[$tkey]=$item;
				}elseif ($val===\true){
					foreach ($array as $tkey=>$item)$out[$tkey]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$tkey]=$tkey;
				}else{
					foreach ($array as $tkey=>$item)$out[$tkey]=$item->$val;
				}
			}elseif (\is_object($k0)){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$k0($item)]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$k0($item)]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$k0($item)]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$k0($item)]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$k0($item)]=$tkey;
				}else{
					foreach ($array as $item)$out[$k0($item)]=$item->$val;
				}
			}else{
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$item->$k0]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$item->$k0]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$item->$k0]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$item->$k0]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$item->$k0]=$tkey;
				}else{
					foreach ($array as $item)$out[$item->$k0]=$item->$val;
				}
			}
		}elseif (!isset($key[1])){
			if ($k0=='[]'){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$i)$out[]=$tkey;
				}else{
					foreach ($array as $item)$out[]=$item[$val];
				}
			}elseif ($k0===self::KEY){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$tkey]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $tkey=>$item)$out[$tkey]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $tkey=>$item)$out[$tkey]=$item;
				}elseif ($val===\true){
					foreach ($array as $tkey=>$item)$out[$tkey]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$tkey]=$tkey;
				}else{
					foreach ($array as $tkey=>$item)$out[$tkey]=$item[$val];
				}
			}elseif (\is_object($k0)){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$k0($item)]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$k0($item)]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$k0($item)]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$k0($item)]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$k0($item)]=$tkey;
				}else{
					foreach ($array as $item)$out[$k0($item)]=$item[$val];
				}
			}else{
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$item[$k0]]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$item[$k0]]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$item[$k0]]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$item[$k0]]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$item[$k0]]=$tkey;
				}else{
					foreach ($array as $item)$out[$item[$k0]]=$item[$val];
				}
			}
		}elseif (!isset($key[2])){
			$k1=$key[1];
			if ($k1=='[]'){

				if (\is_object($k0)){
					if (\is_array($val)){
						foreach ($array as $tkey=>$item)$out[$k0($item)][]=self::arrayGroup($val,$tkey,$item);
					}elseif (\is_object($val)){
						foreach ($array as $item)$out[$k0($item)][]=$val($item);
					}elseif ($val===\false){
						foreach ($array as $item)$out[$k0($item)][]=$item;
					}elseif ($val===\true){
						foreach ($array as $item)$out[$k0($item)][]=\true;
					}elseif ($val===self::KEY){
						foreach ($array as $tkey=>$item)$out[$k0($item)][]=$tkey;
					}else{
						foreach ($array as $item)$out[$k0($item)][]=$item[$val];
					}
				}elseif ($k0===self::KEY){
					if (\is_array($val)){
						foreach ($array as $tkey=>$item)$out[$tkey][]=self::arrayGroup($val,$tkey,$item);
					}elseif (\is_object($val)){
						foreach ($array as $tkey=>$item)$out[$tkey][]=$val($item);
					}elseif ($val===\false){
						foreach ($array as $tkey=>$item)$out[$tkey][]=$item;
					}elseif ($val===\true){
						foreach ($array as $tkey=>$item)$out[$tkey][]=\true;
					}elseif ($val===self::KEY){
						foreach ($array as $tkey=>$item)$out[$tkey][]=$tkey;
					}else{
						foreach ($array as $tkey=>$item)$out[$tkey][]=$item[$val];
					}
				}else{
					if (\is_array($val)){
						foreach ($array as $tkey=>$item)$out[$item[$k0]][]=self::arrayGroup($val,$tkey,$item);
					}elseif (\is_object($val)){
						foreach ($array as $item)$out[$item[$k0]][]=$val($item);
					}elseif ($val===\false){
						foreach ($array as $item)$out[$item[$k0]][]=$item;
					}elseif ($val===\true){
						foreach ($array as $item)$out[$item[$k0]][]=\true;
					}elseif ($val===self::KEY){
						foreach ($array as $tkey=>$item)$out[$item[$k0]][]=$tkey;
					}else{
						foreach ($array as $item)$out[$item[$k0]][]=$item[$val];
					}
				}

			}else{

				if (\is_object($k0)){
					if (\is_object($k1)){
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$k1($item)]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $item)$out[$k0($item)][$k1($item)]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $item)$out[$k0($item)][$k1($item)]=$item;
						}elseif ($val===\true){
							foreach ($array as $item)$out[$k0($item)][$k1($item)]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$k1($item)]=$tkey;
						}else{
							foreach ($array as $item)$out[$k0($item)][$k1($item)]=$item[$val];
						}
					}elseif ($k1==self::KEY){
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$tkey]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$tkey]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$tkey]=$item;
						}elseif ($val===\true){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$tkey]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$tkey]=$tkey;
						}else{
							foreach ($array as $tkey=>$item)$out[$k0($item)][$tkey]=$item[$val];
						}
					}else{
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$item[$k1]]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $item)$out[$k0($item)][$item[$k1]]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $item)$out[$k0($item)][$item[$k1]]=$item;
						}elseif ($val===\true){
							foreach ($array as $item)$out[$k0($item)][$item[$k1]]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$k0($item)][$item[$k1]]=$tkey;
						}else{
							foreach ($array as $item)$out[$k0($item)][$item[$k1]]=$item[$val];
						}
					}
				}elseif ($k0===self::KEY){
					if (\is_object($k1)){
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$tkey][$k1($item)]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $tkey=>$item)$out[$tkey][$k1($item)]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $tkey=>$item)$out[$tkey][$k1($item)]=$item;
						}elseif ($val===\true){
							foreach ($array as $tkey=>$item)$out[$tkey][$k1($item)]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$tkey][$k1($item)]=$tkey;
						}else{
							foreach ($array as $tkey=>$item)$out[$tkey][$k1($item)]=$item[$val];
						}
					}elseif ($k1==self::KEY){
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$tkey][$tkey]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $tkey=>$item)$out[$tkey][$tkey]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $tkey=>$item)$out[$tkey][$tkey]=$item;
						}elseif ($val===\true){
							foreach ($array as $tkey=>$item)$out[$tkey][$tkey]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$tkey][$tkey]=$tkey;
						}else{
							foreach ($array as $tkey=>$item)$out[$tkey][$tkey]=$item[$val];
						}
					}else{
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$tkey][$item[$k1]]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $tkey=>$item)$out[$tkey][$item[$k1]]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $tkey=>$item)$out[$tkey][$item[$k1]]=$item;
						}elseif ($val===\true){
							foreach ($array as $tkey=>$item)$out[$tkey][$item[$k1]]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$tkey][$item[$k1]]=$tkey;
						}else{
							foreach ($array as $tkey=>$item)$out[$tkey][$item[$k1]]=$item[$val];
						}
					}

				}else{
					if (\is_object($k1)){
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$k1($item)]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $item)$out[$item[$k0]][$k1($item)]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $item)$out[$item[$k0]][$k1($item)]=$item;
						}elseif ($val===\true){
							foreach ($array as $item)$out[$item[$k0]][$k1($item)]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$k1($item)]=$tkey;
						}else{
							foreach ($array as $item)$out[$item[$k0]][$k1($item)]=$item[$val];
						}
					}elseif ($k1==self::KEY){
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$tkey]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$tkey]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$tkey]=$item;
						}elseif ($val===\true){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$tkey]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$tkey]=$tkey;
						}else{
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$tkey]=$item[$val];
						}
					}else{
						if (\is_array($val)){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$k1]]=self::arrayGroup($val,$tkey,$item);
						}elseif (\is_object($val)){
							foreach ($array as $item)$out[$item[$k0]][$item[$k1]]=$val($item);
						}elseif ($val===\false){
							foreach ($array as $item)$out[$item[$k0]][$item[$k1]]=$item;
						}elseif ($val===\true){
							foreach ($array as $item)$out[$item[$k0]][$item[$k1]]=\true;
						}elseif ($val===self::KEY){
							foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$k1]]=$tkey;
						}else{
							foreach ($array as $item)$out[$item[$k0]][$item[$k1]]=$item[$val];
						}
					}
				}
			}

		}elseif (!isset($key[3])){
			if ($key[2]=='[]'){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][]=$tkey;
				}else{
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][]=$item[$val];
				}
			}else{
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]]=$tkey;
				}else{
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]]=$item[$val];

				}
			}
		}elseif (!isset($key[4])){
			if ($key[3]=='[]'){
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][]=$tkey;
				}else{
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][]=$item[$val];
				}
			}else{
				if (\is_array($val)){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][$item[$key[3]]]=self::arrayGroup($val,$tkey,$item);
				}elseif (\is_object($val)){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][$item[$key[3]]]=$val($item);
				}elseif ($val===\false){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][$item[$key[3]]]=$item;
				}elseif ($val===\true){
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][$item[$key[3]]]=\true;
				}elseif ($val===self::KEY){
					foreach ($array as $tkey=>$item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][$item[$key[3]]]=$tkey;
				}else{
					foreach ($array as $item)$out[$item[$k0]][$item[$key[1]]][$item[$key[2]]][$item[$key[3]]]=$item[$val];
				}
			}
		}
		return $out;
	}


	private static function key2($array,$key,$val=\false){
	// 2 times longest
		$out=array();
		foreach ($array as $k=>$item){
			$keys=array();
			foreach ($key as $keysitem){
				$keys[]=\is_object($keysitem)?$keysitem($item):$item[$keysitem];
			}

			if (!isset($key[1])){
				$out[$keys[0]]=self::result($item,$val,$k);
			}elseif (!isset($key[2])){
				$out[$keys[0]][$keys[1]]=self::result($item,$val,$k);
			}elseif (!isset($key[3])){
				$out[$keys[0]][$keys[1]][$keys[2]]=self::result($item,$val,$k);
			}else{
				$out[$keys[0]][$keys[1]][$keys[2]][$keys[3]]=self::result($item,$val,$k);
			}
		}
		return $out;
	}


	private static function result($item,$val,$key){
		if (\is_object($val))return $val($item);
		if ($val===\false)return $item;
		if ($val===\true)return \true;
		if ($val===self::KEY)return $key;
		if (\is_array($val))return self::arrayGroup($val,$key,$item);
		return $item[$val];
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function describe_to_table_header($describeArray){
		return self::describeToTableHeader($describeArray);
	}
	static function describeToTableHeader($describeArray){
		$out=array();
		foreach($describeArray['data'] as $key=>$value){
			$out[$key]['name']=isset($value['comment'])?$value['comment']:$key;
			if (!\in_array($value['type'],array('CLOB','BLOB')))$out[$key]['sort']=\true;
		}
		return $out;
	}

	/**
 	 * @deprecated since version 3.4
	 */
	static function describe_to_form($describeArray){
		return self::describeToForm($describeArray);
	}
	/**
	 * Transform describe array to form array
	 * @param array $describeArray array with format describe_table function
	 * @return array|boolean
	 */
	static function describeToForm($describeArray){
		if (empty($describeArray['data']))return \false;
		if (!\is_array($describeArray['data']))return \false;
		$out=array();
		foreach ($describeArray['data'] as $name=>$item){
			$out[$name]=array();
			$out[$name]['label']=(isset($item['comment']))?$item['comment']:$name;
			if (isset($item['default']))$out[$name]['value']=$item['default'];
			// type detection
			switch ($item['type']){
				case 'varchar':
				case 'char':
				case 'tinytext':
				case 'text':
				case 'mediumtext':
				case 'longtext':
				case 'VARCHAR2':
					$out[$name]['type']='text';
					break;
				case 'float':
				case 'double':
				case 'NUMBER':
					$out[$name]['type']='float';
					break;
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
				case 'decimal':
					$out[$name]['type']='number';
					break;
				case 'bit':
					$out[$name]['type']='boolean';
					break;
				case 'date':
				case 'time':
				case 'year':
				case 'datetime':
					$out[$name]['type']=$item['type'];
					break;
				case 'DATE':
				case 'timestamp':
					$out[$name]['type']='datetime';
					break;
			}
			// validators detection
			switch ($item['type']){
			case 'char':
				$out[$name]['validate'][]=array('type'=>'minlength','value'=>$item['typerange'],'text'=>input::VALIDATE_AUTO_TEXT);
				// for char add min filter
			case 'varchar':
				$out[$name]['validate'][]=array('type'=>'maxlength','value'=>$item['typerange'],'text'=>input::VALIDATE_AUTO_TEXT);
				break;
			case 'tinytext':
				$out[$name]['validate'][]=array('type'=>'maxlength','value'=>255,'text'=>input::VALIDATE_AUTO_TEXT);
				break;
			case 'text':
				$out[$name]['validate'][]=array('type'=>'maxlength','value'=>65535,'text'=>input::VALIDATE_AUTO_TEXT);
				break;
			case 'mediumtext':
				$out[$name]['validate'][]=array('type'=>'maxlength','value'=>16777215,'text'=>input::VALIDATE_AUTO_TEXT);
				break;
			case 'longtext':
				$out[$name]['validate'][]=array('type'=>'maxlength','value'=>4294967295,'text'=>input::VALIDATE_AUTO_TEXT);
				break;
			case 'tinyint':
				if ($item['unsigned']){
					$out[$name]['validate'][]=array('type'=>'between','min'=>0,'max'=>256,'text'=>input::VALIDATE_AUTO_TEXT);
				}else{
					$out[$name]['validate'][]=array('type'=>'between','min'=>-128,'max'=>127,'text'=>input::VALIDATE_AUTO_TEXT);
				}
				break;
			case 'smallint':
				if ($item['unsigned']){
					$out[$name]['validate'][]=array('type'=>'between','min'=>0,'max'=>65535,'text'=>input::VALIDATE_AUTO_TEXT);
				}else{
					$out[$name]['validate'][]=array('type'=>'between','min'=>-32768,'max'=>32767,'text'=>input::VALIDATE_AUTO_TEXT);
				}
				break;

			}
			if ($item['notnull'] && !$item['autoincrement'])$out[$name]['validate'][]=array('type'=>'required','text'=>input::VALIDATE_AUTO_TEXT);
		}
		return $out;
	}

	/**
	 * Set array tree from item-parent fields of array
	 * @param array $array input array
	 * @param string $keyField name of item field
	 * @param string $parentField name of parent field
	 * @param string $childrenName name of chain subelement
	 * @param string $mainbranch key of main branch
	 * @param boolean $includeMainBranch include main branch
	 * @return array
	 */
	static function tree($array,$keyField,$parentField,$childrenName='children',$mainbranch=\null,$includeMainBranch=\false){
		foreach ($array as $item){
			self::$branch[$item[$parentField]][]=$item;
			if ($includeMainBranch && $item[$keyField]==$mainbranch)$start=$item;
		}
		if (\is_null($mainbranch))$mainbranch=$item[$parentField];
		$return=self::fillBranch(self::$branch[$mainbranch],$keyField,$childrenName);
		if ($includeMainBranch){
			$start[$childrenName]=$return;
			$return=array($start);
		}
		self::$branch=array();
		return $return;
	}
	private static function fillBranch($item,$keyField,$childrenName){
		foreach ($item as &$i){
			if (isset(self::$branch[$i[$keyField]])){
				$i[$childrenName]=self::fillBranch(self::$branch[$i[$keyField]],$keyField,$childrenName);
			}
		}
		return $item;
	}

	/**
	 * Flatten array with subtree
	 * @param array $array tree array
	 * @param string $children children branch
	 * @param int $level level of subbranch
	 * @return array
	 */
	static function flatTree($array,$children='children',$level=0){
		$out=array();
		foreach ($array as $item){
			$newitem=$item;
			if (isset($newitem[$children]))unset($newitem[$children]);
			$newitem['level']=$level;
			$newitem['has_subtree']=isset($item[$children]);
			$out[]=$newitem;
			if (isset($item[$children])){
				$rs=self::flatTree($item[$children],$children,$level+1);
				$out=\array_merge($out, $rs);
			}
		}
		return $out;
	}

	/**
	 * Show difference between an arrays
	 * @param array $before
	 * @param array $after
	 * @param array $listfields list of fields
	 */
	static function difference($before,$after,$listfields=\false){
		$difference=array();
		if ($listfields==self::FORMS)$listfields=forms::getFormDescription();
		foreach ($after as $key=>$value){
			if (!isset($before[$key]))  continue; // not compare vars without match
			if ($value==$before[$key])  continue; // no difference
			$new=$value;
			$old=$before[$key];
			$field=$fieldText=$key;
			$values=array();
			if (isset($listfields[$key])){
				if (\is_array($listfields[$key]) && isset($listfields[$key]['name'])){
					$fieldText=$listfields[$key]['name'];
					$values=$listfields[$key]['values'];
				}else{
					$fieldText=$listfields[$key];
				}
			}
			$difference[$key]=array(
				'old'=>$old,
				'old_text'=>isset($values[$old])?$values[$old]:$old,
				'new'=>$new,
				'new_text'=>isset($values[$new])?$values[$new]:$new,
				'field'=>$field,
				'field_text'=>$fieldText
			);
			$difference[$key]['text']=translate::t('"{field_text}" changed from "{old_text}" to "{new_text}"',$difference[$key]);
		}
		return $difference;
	}

	static function stable_uasort(&$array, $cmpFunction) {
		$index = 0;
		foreach ($array as &$item) {
			$item = array($index++, $item);
		}
		$result = \uasort($array, function($a, $b) use($cmpFunction) {
			$result = \call_user_func($cmpFunction, $a[1], $b[1]);
			return $result == 0 ? $a[0] - $b[0] : $result;
		});
		foreach ($array as &$item) {
			$item = $item[1];
		}
		return $result;
	}
	static function positionCompare($a,$b){
		if ((float)@$a['position'] == (float)@$b['position'])return 0;
		return ((float)@$a['position'] < (float)@$b['position']) ? -1 : 1;
	}
	/**
	 * Transform array with only vals to array with keys==vals
	 * @param mixed ...$vals_array
	 * @return array
	 */
	static function valsToKeyVals(){
		$args=\func_get_args();
		if (\is_array($args[0])){
			$vals_array=$args[0];
		}else{
			$vals_array=$args;
		}
		$out=array();
		foreach ($vals_array as $value)$out[$value]=$value;
		return $out;
	}
	static function keysToKeyVals($keys_array){
		$out=array();
		foreach ($keys_array as $key=>$v)$out[$key]=$key;
		return $out;
	}
}