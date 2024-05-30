<?php
namespace c\factory;

class db_sqlsrv {
	private static $date_formats=array(
		'd'=>'%d',
		'm'=>'%m',
		'y'=>'%y',
		'Y'=>'%Y',
		'H'=>'%H',
		'i'=>'%i',
		's'=>'%s'
	);
	var $data;
	var $cn;
	private $stmt;
	private $connect;
	var $m_result=\false; // have last query result
	var $error_resume=\false;
	private $result_array=array('sele'=>\true,'desc'=>\true,'show'=>\true);

	function __construct($data,$connectionName=''){
		$this->data=$data;
		$this->cn=$connectionName;
	}
	function wrapper($object){
		return '`'.$object.'`';
	}
	private function charset_mastmach(){
		if (isset($this->data['charset']))return $this->data['charset'];
		switch (\strtoupper(\c\core::$charset)){
			case \c\core::UTF8:return 'UTF-8';
			default: return 'windows-1251';
		}
	}

	function connect(){
		$options=array('ReturnDatesAsStrings'=>true,'TrustServerCertificate'=>true,'Database'=>$this->data['name'],"UID"=>$this->data['login'],"PWD"=>$this->data['password']);
		if ($this->data['charset'])$options['CharacterSet']=$this->charset_mastmach();
		@$this->connect=\sqlsrv_connect($this->data['host'],$options);
		if (!$this->connect)throw new \Exception('Sqlsrv connection error '.\print_r(\sqlsrv_errors(),true));
		if (\c\core::$debug){
			\c\debug::trace('Connection to '.($this->cn?$this->cn:'SQLSrv'),\c\error::SUCCESS);
			@\c\core::$data['stat']['db_connections']++;
		}
	}
	function disconnect(){
		\sqlsrv_close($this->connect);
	}
	function beginTransaction(){
		\sqlsrv_begin_transaction($this->connect);
	}
	function commit(){
		\sqlsrv_commit($this->connect);
	}
	function rollback(){
		\sqlsrv_rollback($this->connect);
	}
	function bind($sql,$bind=array()){
		if (\sizeof($bind)==0 || !\is_array($bind))return $sql;
		$bind2=array();
		foreach ($bind as $key=>$value){
			$bind2[':'.$key]=($value===\c\db::NULL || $value===\null?'NULL': ($value instanceof \c\db?$value:"'".\strtr($value,["'"=>"''","\\"=>"\\\\"])."'"));
		}
		return \strtr($sql,$bind2);
	}
	function execute($sql, $bind=array()){
		return $this->execute_assoc($sql,$bind,'e');
	}
	function execute_assoc($sql,$bind=array(),$mode='ea'){
		if (\c\core::$debug){
			@\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('SQLSrv query');
			if (\ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=\ltrim($sql);
			}
			\c\debug::trace('Connection: '.$this->connect,\false);
			\c\debug::trace('SQL: '.$sql,\false);
			if ($bind){
				\c\debug::dir(array('BIND:'=>$bind));
			}else{
				\c\debug::trace('BIND: None',\false);
			}
			$start=\microtime(\true);
		}
		$sql=$this->bind($sql,$bind);
		$this->stmt=@$stmt=\sqlsrv_query($this->connect,$sql);
		
		if (\c\core::$debug){
			\c\debug::consoleLog('Query execute for '.\round((\microtime(\true)-$start)*1000,2).' ms');
			$start=\microtime(\true);
		}
		if(!$stmt){
			if (\c\core::$debug){
				\c\debug::trace('Query error: '.\sqlsrv_errors(),\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('SqlSrv error: '.\sqlsrv_errors(),\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return \false;
			throw new \Exception('SQL execute error: '.\sqlsrv_errors());
		}
		$subsql=\strtolower(\substr($sql,0,4));
		$data=array();
		if (isset($this->result_array[$subsql])){
			switch ($mode){
				case 'ea':
					while ($row=\sqlsrv_fetch_array($stmt,\SQLSRV_FETCH_ASSOC))$data[]=$row;
					break;
				case 'e':
					while ($row=\sqlsrv_fetch_array($stmt))$data[]=$row;
					break;
				case 'ea1':
					$data=\sqlsrv_fetch_array($stmt,\SQLSRV_FETCH_ASSOC);
					break;
			}
			if (\c\core::$debug)\c\debug::trace('Result fetch get '.\round((\microtime(\true)-$start)*1000,2).' ms');
		}
		if (\c\core::$debug){
			if (!isset($this->result_array[$subsql]) && $mode!='e')\c\debug::trace('ea function used without result. Better use e function',\c\error::WARNING);
			if (isset($this->result_array[$subsql]) && $mode=='e')\c\debug::trace('e function used with result. Better use ea function',\c\error::WARNING);
			if (isset($this->result_array[$subsql])){
				if ($data){
					if ($mode=='ea1'){
						\c\debug::group('Query result');
						\c\debug::dir($data);
					}else{
						\c\debug::group('Query result. Count: '.\sizeof($data),\c\error::INFO,\sizeof($data)>10);
						\c\debug::table(\array_slice($data,0,30));
						if (\sizeof($data)>30)\c\debug::trace('Too large data was sliced',\c\error::INFO);
					}
					\c\debug::groupEnd();
				}else{
					\c\debug::trace('No results found',\c\error::WARNING);
				}
			}else{
				\c\debug::trace('Affected '.$this->rows().' rows',\false);
			}
		}else{
			\sqlsrv_free_stmt($stmt);
		}
		return $data;
	}
	function ea1($sql,$bind=array()){
		return $this->execute_assoc($sql,$bind,'ea1');
	}
	function db_limit($sql,$from=0,$count=0,$order_fild='1',$order_dir='DESC'){
		if ($order_dir=="DESC"){
			$o1="ASC";
			$o2="DESC";
		}else{
			$o1="DESC";
			$o2="ASC";
		}
		if ($from != 0) return "SELECT * FROM (SELECT TOP ".$count." * FROM (SELECT TOP ".($from + $count)." * FROM (".$sql.") DBLIMIT1 ORDER BY ".$order_fild." ".$o2.") DBLIMIT2 ORDER BY ".$order_fild." ".$o1.") DBLIM";
		else return "SELECT TOP ".($from + $count)." * FROM (".$sql.") DBLIMIT2 ";
	}
	function getLenResult(){
		return \sqlsrv_num_rows($this->stmt);
	}
	function insertId(){
		return \false;
	}
	function rows(){
		return \sqlsrv_rows_affected($this->stmt);
	}
	function explain(){
		return \false;
	}
	function query($sql,$bind){
		$sql=$this->bind($sql,$bind);
		return $this->stmt=\sqlsrv_query($this->connect,$sql);
	}
	function fa($stmt){
		$row=\sqlsrv_fetch_array($stmt,\SQLSRV_FETCH_ASSOC);
		if (empty($row))\sqlsrv_free_stmt($stmt);
		return $row;
	}
	function date_from_db($value,$format){
		$format=\strtr($format,self::$date_formats);
		return "date_format(".$value.",'".$format."')";
	}
	function date_to_db($value,$format=\null){
		if ($value===\null)return 'now()';
		if ($format)return "STR_TO_DATE(".$value.",'".\strtr($format,self::$date_formats)."')";
		if (!\is_numeric($value))$value=\strtotime($value);
		return "STR_TO_DATE('".\date('Y-m-d H:i:s',$value)."','%Y-%m-%d %H:%i:%s')";
	}
}