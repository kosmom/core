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
	//static $time_laps=0;
	//static $lasp_counter=10;

	private static function getDifference(){
		if (!isset($_SERVER['REQUEST_TIME_FLOAT']))$_SERVER['REQUEST_TIME_FLOAT']=microtime(true);
		$from_start=microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'];
		$str=substr($from_start,0,5).' ('.substr($from_start-self::$lastTimer,0,5).')';
		self::$lastTimer=$from_start;
		return $str;
	}

	private static function readFileVar($file,$string){
		if (!($f = fopen($file, "r"))) throw new \Exception('Read file error');
		$s=0;
		while (!feof($f)) {
			$line = fgets($f);
			$s++;
			if ($s==$string)break;
		}
		$line=trim(substr($line,0,strpos($line,'=')));
		fclose($f);
		if (substr($line,0,1)!='$')return false;
		return $line;
	}

	static function alert($value='',$variable='',$file='',$line=''){
		if (is_array($value))$value=str_replace("\n",'\\n',print_r($value,true));
		if ($variable!='')$variable.=' = \n';
		if ($file!='')$file='['.$file.']';
		if ($line!='')$line='['.$line.']';
		echo "<script>alert('".str_replace("\r",'',str_replace('\\','\\\\',$file.$line).$variable.strtr(array("'"=>'\\\'',"\n"=>'\\n'),$value))."')</script>";
	}
	static function timer(){
		$mtime = microtime(true);
		$timout=$mtime-self::$timer;
		self::$timer=$mtime;
		return $timout;
	}
	static function count($message){
			if (core::$ajax && !core::$partition)return ajax::consoleCount($message);
			mvc::addScript('console.count(\''.input::jsPrepare($message).'\')'); //,self::$time_laps+=self::$lasp_counter
	}
	static function dir($array){
			if (empty($array))return false;
			if (core::$ajax && !core::$partition)return ajax::consoleDir($array);
			mvc::addScript('console.dir('.input::jsVarArray($array).')'); //self::$time_laps+=self::$lasp_counter
	}
	static function table($array){
		if (empty($array))return false;
		if (core::$ajax && !core::$partition)return ajax::consoleTable($array);
		$br=browser::get();

		if ($br['name']=='Internet Explorer')return self::dir($array);
		mvc::addScript('console.table('.input::jsVarArray($array).')'); //,self::$time_laps+=100
	}
	static function group($header,$type=error::INFO,$collapsed=true,$increaseCounter=true){
		if (@core::$data['label']){
			mvc::addScript('console.log(\'%c'.input::jsPrepare(core::$data['label']).'\',\'color: #f66;\')'); //,self::$time_laps+=self::$lasp_counter
			core::$data['label']='';
		}
		if (!self::$groupCounter){
			$need=self::getBacktrace();
			$filename=substr($need['file'],self::$bugtrace_file_from);
			if ($need['file'])$var=self::readFileVar($need['file'],$need['line']);
			$string=self::getDifference().' '.($var?$var.' = ':'').$filename.':'.$need['line'].' - '.$need['class'].$need['type'].$need['function'].' - '.$header;
		}else{
			$string=$header;
		}
		if ($increaseCounter)self::$groupCounter++;
		if (core::$ajax && !core::$partition)return ajax::consoleGroup($string,$type,$collapsed);
		if (self::$bugtrace_report=='console'){
			switch ($type){
				case error::INFO:
					mvc::addScript('console.group'.($collapsed?'Collapsed':'').'(\''.input::jsPrepare($string).'\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				case error::SUCCESS:
					mvc::addScript('console.group'.($collapsed?'Collapsed':'').'(\'%c'.input::jsPrepare($string).'\',\'color: green;\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				case error::ERROR:
					mvc::addScript('console.group'.($collapsed?'Collapsed':'').'(\'%c'.input::jsPrepare($string).'\',\'color: red;\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				case error::WARNING:
					mvc::addScript('console.group'.($collapsed?'Collapsed':'').'(\'%c'.input::jsPrepare($string).'\',\'color: orange;\')'); //,self::$time_laps+=self::$lasp_counter
					break;
			}
		}
	}
	static function groupEnd($decreaseCounter=true){
		if ($decreaseCounter)self::$groupCounter--;
		if (core::$ajax && !core::$partition)return ajax::console_groupEnd();
		if (self::$bugtrace_report=='console')mvc::addScript('console.groupEnd()'); //,++self::$time_laps
	}

	private static function getBacktrace(){
		if (!self::$bugtrace_file_from)self::$bugtrace_file_from=strlen(dirname($_SERVER['SCRIPT_FILENAME']));
		$out=debug_backtrace(false);
		array_shift($out);
		$debug=array_shift($out);
		foreach ($out as $item){
			if (empty($item['class']))break;
			if (substr($item['class'],0,2)!='c\\')break;
			$need=$item;
		}
		if (empty($need))return $debug;
		return $need;
	}
	static function trace($message='',$type=error::INFO, $args=null){
		if (self::$groupCounter){
			$string=$message;
		}else{
			$need=self::getBacktrace();
			$filename=substr($need['file'],self::$bugtrace_file_from);
			$string=self::getDifference().' '.$filename.':'.$need['line'].' - '.$need['class'].$need['type'].$need['function'].' - '.$message;
		}
		if (self::$bugtrace_report=='console'){
			self::consoleLog($string,$type);
			if ($args){
				if (is_array($args) or is_object($args)){
					self::dir($args);
				}else{
					mvc::addScript('console.log(\''.$args.'\'))'); //,self::$time_laps+=self::$lasp_counter
				}
			}
		}
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
	static function consoleLog($message,$type=null){
		if (core::$ajax && !core::$partition)return ajax::consoleLog($message,$type);

			switch ($type){
				case error::SUCCESS:
					mvc::addScript('console.log(\'%c'.input::jsPrepare($message).'\',\'color: green;\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				case error::INFO:
					mvc::addScript('console.info(\''.input::jsPrepare($message).'\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				case error::ERROR:
					mvc::addScript('console.warn(\'%c'.input::jsPrepare($message).'\',\'color: red;\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				case error::WARNING:
					mvc::addScript('console.warn(\''.input::jsPrepare($message).'\')'); //,self::$time_laps+=self::$lasp_counter
					break;
				default:
					mvc::addScript('console.log(\''.input::jsPrepare($message).'\')'); //,self::$time_laps+=self::$lasp_counter
		}
	}
	static function stat(){
		$files=get_included_files();
		if (core::$charset!=core::WINDOWS1251 && core::$charset!=core::UTF8)self::consoleLog('Charset not mistmach as exist CONST. Use c\\core::UTF8 or c\\core::WINDOWS1251 consts',error::WARNING);
		self::group('Script stat:');
		// error analyses
		self::trace('Time from MVC init: '.round(self::timer()*1000,2).' ms', error::INFO);
		self::trace('Memory: Usage: '.round(memory_get_usage()/1000).' Kb. Peak: '.round(memory_get_peak_usage()/1000).' Kb', error::INFO);
		if (isset(core::$data['stat']))self::dir(core::$data['stat']);
		self::group('Included_files: '.sizeof($files));
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
			error::addWarning("<pre>".print_r($var,true)."</pre>");
			if ($isDie)ajax::render();
			return;
		}
		echo "<pre>",var_dump($var),"</pre>";
		if($isDie)die;
	}

	static function dump($var,$exit=false){
		self::z($var,$exit);
	}
}