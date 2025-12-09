<?php
namespace c;

/**
 * Command line interface class
 * @author Kosmom <Kosmom.ru>
 */
class cli{
	const SYMBOL_UP='▲';
	const SYMBOL_RIGHT='►';
	const SYMBOL_LEFT='◄';
	const SYMBOL_DOWN='▼';
	const SYMBOL_HEART='❤';
	const SYMBOL_MARKER='•';
	const SYMBOL_FILL='█';
	const SYMBOL_FILL75='▓';
	const SYMBOL_FILL50='▒';
	const SYMBOL_FILL25='░';
	const SYMBOL_FILL_LOWER='▄';
	const SYMBOL_FILL_UPPER='▀';
	const SYMBOL_CHECK='✔';
	const EOL="\n";
	const COLOR_RESET="\033[0m";
	const COLOR_BLACK="\033[0;30m";
	const COLOR_DARK_GRAY="\033[1;30m";
	const COLOR_BLUE="\033[0;34m";
	const COLOR_LIGHT_BLUE="\033[1;34m";
	const COLOR_GREEN="\033[0;32m";
	const COLOR_LIGHT_GREEN="\033[1;32m";
	const COLOR_CYAN="\033[0;36m";
	const COLOR_LIGHT_CYAN="\033[1;36m";
	const COLOR_RED="\033[0;31m";
	const COLOR_LIGHT_RED="\033[1;31m";
	const COLOR_PURPLE="\033[0;35m";
	const COLOR_LIGHT_PURPLE="\033[1;35m";
	const COLOR_BROWN="\033[0;33m";
	const COLOR_YELLOW="\033[1;33m";
	const COLOR_LIGHT_GRAY="\033[0;37m";
	const COLOR_WHITE="\033[1;37m";
	const BACKGROUND_COLOR_BLACK="\033[40m";
	const BACKGROUND_COLOR_RED="\033[41m";
	const BACKGROUND_COLOR_GREEN="\033[42m";
	const BACKGROUND_COLOR_YELLOW="\033[43m";
	const BACKGROUND_COLOR_BLUE="\033[44m";
	const BACKGROUND_COLOR_MAGENTA="\033[45m";
	const BACKGROUND_COLOR_CYAN="\033[46m";
	const BACKGROUND_COLOR_LIGHT_GRAY="\033[47m";
	const CURSOR_HIDE="\033[?25l";
	const CURSOR_SHOW="\033[?25h\033[?0c";
	const ERASE_END_OF_LINE="\033[K";
	const ERASE_START_OF_LINE="\033[1K";
	const ERASE_LINE="\033[2K";
	const ERASE_DOWN="\033[J";
	const ERASE_UP="\033[1J";
	const CLS="\033[2J";
	const ERASE="\033[2J";
	const CURSOR_SAVE="\0337";
	const CURSOR_UNSAVE="\033[u";
	const CURSOR_RESTORE="\0338";
	const CURSOR_SAVEPOS="\033[s";
	static function cursorUp($count=1){
		return "\033[".$count."A";
	}
	static function cursorWown($count=1){
		return "\033[".$count."B";
	}
	static function cursorForward($count=1){
		return "\033[".$count."C";
	}
	static function cursorBackward($count=1){
		return "\033[".$count."D";
	}
	static function cursorMove($row=0,$col=0){ 
		return "\033[".$row.";".$col."f";
	}
	static function prompt(){
		$fp=\fopen("php://stdin","r");
		$in=\fgets($fp,4094);
		\fclose($fp);
		return \trim($in);
	}
	static function argv(){
		global $argv;
		return \array_slice($argv,2);
	}
}