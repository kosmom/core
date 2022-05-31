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
}
