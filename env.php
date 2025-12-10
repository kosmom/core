<?php
namespace c;

/**
 * Enviroment class
 * @author Kosmom <Kosmom.ru>
 */
class env{
	static function isWindowsOS(){
		return \substr(\PHP_OS,0,3)==='WIN';
	}
	
	static function getCpuLoad(){
		if (self::isWindowsOS()){
			@\exec("wmic cpu get loadpercentage /all",$out);
			foreach ($out as $line){
				if (\is_numeric($line))return (int)$line;
			}
			return false;
		}else{
			return \sys_getloadavg()[0]*100/self::getCpuCoresNumber();
		}
	}
	
	static function getCpuCoresNumber(){
		if (self::isWindowsOS()){
			return (int)\shell_exec('echo %NUMBER_OF_PROCESSORS%');
		}
		return (int)\shell_exec('nproc');
	}
	
	static function getAvailableSystemMemory(){
		if (self::isWindowsOS()){
			@\exec("wmic OS get FreePhysicalMemory",$output);
			return (int)$output[1]*1024;
		}else{
			\exec('free -b',$output);
			$m=\explode(' ',$output[1]);
			return (int)\end($m);
		}
	}
}
