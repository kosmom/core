<?php
namespace c;

/**
 * Debug timer console dump class
 * @author Kosmom <Kosmom.ru>
 */
class debug{
	private static $groupCounter=0;
	static $bugtrace_file_from=0;
	static $bugtrace_report='console';
	private static $timer=0;
	private static $lastTimer=0;
	static $debugBuffer = array();

	private static function getDifference(){
		if (!isset($_SERVER['REQUEST_TIME_FLOAT']))$_SERVER['REQUEST_TIME_FLOAT']=microtime(true);
		$from_start=\microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'];
		$str=\substr($from_start,0,5).' ('.\substr($from_start-self::$lastTimer,0,5).')';
		self::$lastTimer=$from_start;
		return $str;
	}

	private static function readFileVar($file,$string){
		if (!($f=\fopen($file,"r")))throw new \Exception('Read file error');
		$s=0;
		while (!\feof($f)){
			$line=\fgets($f);
			$s++;
			if ($s==$string)break;
		}
		$line=\trim(\substr($line,0,\strpos($line,'=')));
		\fclose($f);
		if (\substr($line,0,1)!='$')return false;
		return $line;
	}

	static function alert($value='',$variable='',$file='',$line=''){
		if (\is_array($value))$value=\str_replace("\n",'\\n',\print_r($value,true));
		if ($variable!='')$variable.=' = \n';
		if ($file!='')$file='['.$file.']';
		if ($line!='')$line='['.$line.']';
		echo "<script>alert('".\str_replace("\r",'',\str_replace('\\','\\\\',$file.$line).$variable.\strtr(array("'"=>'\\\'',"\n"=>'\\n'),$value))."')</script>";
	}
	static function timer(){
		$mtime = \microtime(true);
		if (!self::$timer && $_SERVER['REQUEST_TIME_FLOAT'])self::$timer=$_SERVER['REQUEST_TIME_FLOAT'];
		$timout=$mtime-self::$timer;
		self::$timer=$mtime;
		return $timout;
	}
	static function count($message){
		self::$debugBuffer[]=array('count',$message);
	}
	static function dir($array){
		self::$debugBuffer[]=array('dir',$array);
	}
	static function table($array){
		if (empty($array))return false;
		self::$debugBuffer[]=array('table',$array);
	}
	static function group($header,$type=error::INFO,$collapsed=true,$increaseCounter=true){
		if (@core::$data['label']){
			self::consoleLog(core::$data['label'],error::HEADER);
			core::$data['label']='';
		}
		if (!self::$groupCounter){
			$need = self::getBacktrace();
			$filename=\substr($need['file'],self::$bugtrace_file_from);
			if ($need['file'])$var=self::readFileVar($need['file'],$need['line']);
			$string=self::getDifference().' '.($var?$var.' = ':'').$filename.':'.$need['line'].' - '.$need['class'].$need['type'].$need['function'].' - '.$header;
		}else{
			$string=$header;
		}
		if ($increaseCounter)self::$groupCounter++;
		self::$debugBuffer[]=array('group',$string,$type,$collapsed);
	}
	static function groupEnd($decreaseCounter=true){
		if ($decreaseCounter)self::$groupCounter--;
		self::$debugBuffer[]=array('groupEnd');
	}
	
	private static function getBacktrace(){
		if (!self::$bugtrace_file_from)self::$bugtrace_file_from=\strlen(mvc::$base__DIR__)+1;
		$out=debug_backtrace(false);
		\array_shift($out);
		$debug=\array_shift($out);
		foreach ($out as $item){
			if (empty($item['class']))break;
			if (\substr($item['class'],0,2)!='c\\')break;
			$need=$item;
		}
		if (empty($need))return $debug;
		return $need;
	}
	static function trace($message='',$type=error::INFO,$args=null){
		if (self::$groupCounter){
			$string=$message;
		}else{
			$need=self::getBacktrace();
			$filename=\substr($need['file'],self::$bugtrace_file_from);
			$string=self::getDifference().' '.$filename.':'.$need['line'].' - '.$need['class'].$need['type'].$need['function'].' - '.$message;
		}
		if (self::$bugtrace_report=='console'){
			self::consoleLog($string,$type);
			if ($args){
				if (\is_array($args) || \is_object($args)){
					self::dir($args);
				}else{
					self::consoleLog($args,null,false);
				}
			}
		}
	}
	
	static function debugOutput(){
		$out=array();
		if (core::$ajax && !core::$partition){
			// format ajax actions
			foreach (self::$debugBuffer as $arg){
				switch ($arg[0]){
					case 'log':
						$out[]=array('_type'=>'console_log','data'=>$arg[1],'text_type'=>$arg[2]);
						break;
					case 'count':
						$out[]=array('_type'=>'console_count','data'=>$arg[1]);
						break;
					case 'dir':
						$out[]=array('_type'=>'console_dir','data'=>$arg[1]);
						break;
					case 'table':
						$out[]=array('_type'=>'console_table','data'=>$arg[1]);
						break;
					case 'groupEnd':
						$out[]=array('_type'=>'console_groupEnd');
						break;
					case 'group':
						$out[]=array('_type'=>'console_group','data'=>$arg[1],'text_type'=>$arg[2],'collapsed'=>$arg[3]);
						break;
				}
			}
			return $out;
		}
		foreach (self::$debugBuffer as $arg){
			switch ($arg[0]){
				case 'log':
					$message=$arg[1];
					$type=$arg[2];
					$wrapped=$arg[3];
					$mes=$wrapped?input::jsPrepare($message):$message;
					switch ($type){
						case error::SUCCESS:
							$out[]='console.log(\'%c'.$mes.'\',\'color: green;\')';
							break;
						case error::INFO:
							$out[]='console.info(\''.$mes.'\')';
							break;
						case error::ERROR:
							$out[]='console.warn(\'%c'.$mes.'\',\'color: red;\')';
							break;
						case error::HEADER:
							$out[]='console.log(\'%c'.$mes.'\',\'color: #f66;\')';
							break;
						case error::WARNING:
							$out[]='console.warn(\''.$mes.'\')';
							break;
						default:
							$out[]='console.log(\''.$mes.'\')';
					}
					break;
				case 'groupEnd':
					$out[]='console.groupEnd()';
					break;
				case 'dir':
					$out[]='console.dir('.input::jsVarArray($arg[1]).')';
					break;
				case 'table':
					$out[]='console.table('.input::jsVarArray($arg[1]).')';
					break;
				case 'count':
					$out[]='console.count(\''.input::jsPrepare($arg[1]).'\')';
					break;
				case 'group':
					$message=$arg[1];
					$type=$arg[2];
					$collapsed=$arg[3];
					$col=$collapsed?'Collapsed':'';
					switch ($type){
						case error::INFO:
							$out[]='console.group'.$col.'(\''.input::jsPrepare($message).'\')';
							break;
						case error::SUCCESS:
							$out[]='console.group'.$col.'(\'%c'.input::jsPrepare($message).'\',\'color: green;\')';
							break;
						case error::ERROR:
							$out[]='console.group'.$col.'(\'%c'.input::jsPrepare($message).'\',\'color: red;\')';
							break;
						case error::WARNING:
							$out[]='console.group'.$col.'(\'%c'.input::jsPrepare($message).'\',\'color: orange;\')';
							break;
					}
			}
		}
		return $out;
	}

	
	/**
	 * @deprecated since version 3.4
	 */
	static function console_log($message,$type=null){
		return self::consoleLog($message,$type);
	}
	/**
	 * Draw console.log debug info
	 * @param string $message console message
	 * @param int $type error::TYPE type of message
	 */
	static function consoleLog($message,$type=null,$wrapped=true){
		self::$debugBuffer[]=array('log',$message,$type,$wrapped);
	}
	static function stat(){
		$files=\get_included_files();
		if (core::$charset!=core::WINDOWS1251 && core::$charset!=core::UTF8)self::consoleLog('Charset not mistmach as exist CONST. Use c\\core::UTF8 or c\\core::WINDOWS1251 consts',error::WARNING);
		self::group('Script stat:');
		// error analyses
		self::trace('Time from MVC init: '.\round(self::timer()*1000,2).' ms', error::INFO);
		self::trace('Memory: Usage: '.\round(\memory_get_usage()/1000).' Kb. Peak: '.\round(\memory_get_peak_usage()/1000).' Kb', error::INFO);
		if (isset(core::$data['stat']))self::dir(core::$data['stat']);
		self::group('Included_files: '.\count($files));
		self::dir($files);
		self::groupEnd();
		self::groupEnd();
	}
	/**
	 * Zaeb. Echo variable with pre tags and exit
	 * @param mixed $var
	 * @param boolean $isDie
	 */
	static function z($var,$isDie=true){
		if (core::$ajax && !core::$partition){
			if ($isDie)ajax::clearAnswers();
			error::addWarning("<pre>".\print_r($var,true)."</pre>");
			if ($isDie)ajax::render();
			return;
		}
		echo "<pre>",\var_dump($var),"</pre>";
		if($isDie)die;
	}

	static function dump($var,$exit=false){
		self::z($var,$exit);
	}
}