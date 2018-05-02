<?php
namespace c;

/**
 * Ajax class infrastructure. For use, you must include core.js javascript file in html file with ajax action
 * @author Kosmom <Kosmom.ru>
 */
class ajax {

	private static $answer = array();

	/**
	 * If empty answer - then error
	 * @var boolean
	 */
	static $answer_needed = true;

	static function init(){
		core::$ajax=true;
		if (@$_POST['_partition_content']){
			core::$partition=true;
		}else{
			set_error_handler('\\c\\ajax::errorHandler');
		}
		// if charset not utf-8 - convert
		if (core::$charset!=core::UTF8)$_POST=input::iconv($_POST);
		if (core::$debug)debug::group(date('H:i:s').' Core Ajax request'.(core::$partition?' (Partition mode)':''), error::WARNING,false,false);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function error_handler($errno, $errstr, $errfile, $errline){
		return self::errorHandler($errno,$errstr,$errfile,$errline);
	}
	static function errorHandler($errno, $errstr, $errfile, $errline){

	if (!(error_reporting() & $errno))return;
	switch ($errno) {
	case E_USER_ERROR:
		self::consoleGroup('FATAL ERROR'.$errstr, error::ERROR);
		self::consoleLog('Error in '.$errline.' string of '.$errfile);
		self::consoleGroupEnd();
		if (core::$ajax)ajax::render();
		break;

	case E_USER_WARNING:
		self::consoleGroup('WARNING ERROR'.$errstr, error::WARNING);
		self::consoleLog('Error in '.$errline.' string of '.$errfile);
		self::consoleGroupEnd();
		break;


	default:
		self::consoleGroup('ERROR'.$errstr);
		self::consoleLog('Error in '.$errline.' string of '.$errfile);
		self::consoleGroupEnd();
		break;
	}
	return false;
	}

	static function getAct(){
		return $_POST['_act'];
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function clear_answers(){
		self::$answer=array();
	}
	static function clearAnswers(){
		self::$answer=array();
	}

	/**
	 * Add custom user script type feedback. 'type' key must be required and isset in javascript feedback function
	 * @param array $data
	 */
	static function add($data) {
		self::$answer[] = $data;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function add_action($type,$data=null) {
		self::addAction($type,$data);
	}
	static function addAction($type,$data=null) {
		if (core::$debug)debug::trace('Add ajax action '.$type,error::INFO,$data);
		if (core::$ajax){ // && !core::$partition
			if (!is_array($data))$data=array('_val'=>$data);
			$data['_type']=$type;
			self::$answer[] = $data;
		}else{
			mvc::addScript('core_feedbacks["'.$type.'"](JSON.parse(\''.input::jsonEncode($data).'\'));');
		}
	}

	static function redirect($location=null) {
		if ($location===null)$location=$_SERVER['REQUEST_URI'];
		if (substr($location,0,4)!='http')$location=($_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['HTTP_HOST'].$location;
		self::$answer[] = array('_type' => 'redirect', 'redirect' => $location);
		self::render(true);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function add_js_var($name,$value) {
		self::addJsVar($name, $value);
	}
	static function addJsVar($name,$value) {
		self::$answer[] = array('_type' => 'var', 'name' => $name,'value'=>$value);
	}
	static function consoleLog($data,$type=error::INFO) {
		self::$answer[] = array('_type' => 'console_log', 'data' => $data,'text_type'=>$type);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function console_log($data,$type=error::INFO) {
		self::consoleLog($data,$type);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function console_count($data) {
		self::consoleCount($data);
	}
	static function consoleCount($data) {
		self::$answer[] = array('_type' => 'console_count', 'data' => $data);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function console_dir($data) {
		self::consoleDir($data);
	}
	static function consoleDir($data) {
		self::$answer[] = array('_type' => 'console_dir', 'data' => $data);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function console_table($data) {
		self::consoleTable($data);
	}
	static function consoleTable($data) {
		self::$answer[] = array('_type' => 'console_table', 'data' => $data);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function console_group($header,$type=error::INFO,$collapsed=true) {
		self::consoleGroup($header, $type, $collapsed);
	}
	static function consoleGroup($header,$type=error::INFO,$collapsed=true) {
		self::$answer[] = array('_type' => 'console_group', 'data' => $header,'text_type'=>$type,'collapsed'=>$collapsed);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function console_groupEnd() {
		self::consoleGroupEnd();
	}
	static function consoleGroupEnd() {
		self::$answer[] = array('_type' => 'console_groupEnd');
	}


	static private function renderPrepare($redirect,$answer_needed){
		// if redirect - not need show errors. Error stack must be added with messages
		if (!$redirect){
			if (error::count()) {
				foreach (error::errors() as $item)self::$answer[] = array('_type' => 'message', 'message' => $item, 'type' => error::ERROR);
			}
			if (error::count(error::WARNING)) {
				foreach (error::warnings() as $item)self::$answer[] = array('_type' => 'message', 'message' => $item, 'type' => error::WARNING);
			}
			if (error::count(error::SUCCESS)) {
				foreach (error::success() as $item)self::$answer[] = array('_type' => 'message', 'message' => $item, 'type' => error::SUCCESS);
			}
			// todo: think about need answer when was not ajax actions
			if ($answer_needed===null)$answer_needed=self::$answer_needed;
			if ($answer_needed && empty(self::$answer))self::$answer[] = array('_type' => 'message', 'message' => 'Action not supported', 'type' => error::ERROR);
		}
		if (core::$debug)self::consoleLog('Core MVC debug finished',error::INFO);
		if (core::$debug)self::consoleGroupEnd();
		
	}
	static function renderOutLoad(){
		return '<script>core_ajax_process('.self::renderOut(false,false).')</script>';
	}
	static function renderOut($redirect=false,$answer_needed=null){
		self::renderPrepare ($redirect,$answer_needed);
		return input::jsonEncode(self::$answer);
	}
	static function render($redirect = false,$answer_needed=null) {
		self::renderPrepare ($redirect,$answer_needed);
		header_remove('Content-Length');
		if (@core::$data['ajax_gzip']){
			header('content-encoding: gzip');
			header('Vary: Accept-Encoding');
			die(gzencode(input::jsonEncode(self::$answer),9));
		}
		die(input::jsonEncode(self::$answer));
	}
}
