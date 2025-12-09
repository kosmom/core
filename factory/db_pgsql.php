<?php
namespace c\factory;

class db_pgsql {
	private static $date_formats=array(
		'd'=>'DD',
		'm'=>'MM',
		'y'=>'YY',
		'Y'=>'YYYY',
		'H'=>'HH24',
		'i'=>'MI',
		's'=>'SS'
		);
	var $data;
	var $cn;
	private $insert_id;
	private $num_rows;
	private $affected_rows;
	var $connect;
	var $m_result=false; // have last query result
	var $error_resume=false;
	private $result_array=array('sele'=>true,'desc'=>true,'show'=>true);

	function __construct($data,$connectionName=''){
		$this->data=$data;
		$this->cn=$connectionName;
	}
	function wrapper($object){
		return '`'.$object.'`';
	}
	private function charset_mach(){
		if (isset($this->data['charset']))return $this->data['charset'];
		switch (\strtoupper(\c\core::$charset)){
			case \c\core::UTF8:return 'UTF8';
			default: return 'cp1251';
		}
	}

	function connect(){
		$con='host='.$this->data['host'].(isset($this->data['port'])?' port='.$this->data['port']:'').' dbname='.$this->data['name'].' user='. $this->data['login'].' password='. $this->data['password']." options='--client_encoding=".$this->charset_mach()."'";
		$this->connect=$this->data['persistent']?\pg_pconnect($con):\pg_connect($con);
		if (!$this->connect)throw new \Exception('PgSQL connection error '.\pg_last_error());
		if (\c\core::$debug){
			\c\debug::trace('Connection to '.($this->cn?$this->cn:'PgSQL'),\c\error::SUCCESS);
			@\c\core::$data['stat']['db_connections']++;
		}
	}
	function disconnect(){
		\pg_close($this->connect);
	}
	function beginTransaction(){
		$this->execute('begin');
	}
	function commit(){
		$this->execute('commit');
	}
	function rollback(){
		$this->execute('rollback');
	}
	function bind($sql,$bind=array()){
		if (\count($bind)==0 || !\is_array($bind))return $sql;
		$bind2=array();
		foreach ($bind as $key=>$value){
			$bind2[':'.$key]=($value===\c\db::NULL || $value===null?'NULL': ($value instanceof \c\db?$value:"'".\pg_escape_string($this->connect,$value)."'"));
		}
		return \strtr($sql,$bind2);
	}
	function execute($sql, $bind=array()){
		return $this->execute_assoc($sql,$bind,'e');
	}
	function execute_assoc($sql,$bind=array(),$mode='ea'){
		if (\c\core::$debug){
			@\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('PgSQL query');
			if (\ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=\ltrim($sql);
			}
			\c\debug::trace('Connection: '.$this->cn,false);
			\c\debug::trace('SQL: '.$sql,false);
			if ($bind){
				\c\debug::dir(array('BIND:'=>$bind));
			}else{
				\c\debug::trace('BIND: None',false);
			}
			$start=\microtime(true);
		}
		$sql=$this->bind($sql,$bind);
		@$result=pg_query($this->connect,$sql);
		if (\c\core::$debug){
			\c\debug::consoleLog('Query execute for '.\round((\microtime(true)-$start)*1000,2).' ms');
			$start=\microtime(true);
		}
		if(!$result){
			if (\c\core::$debug){
				\c\debug::trace('Query error: '.\pg_errormessage($this->connect),\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('PgSQL error: '.\pg_errormessage($this->connect),\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL execute error');
		}
		$subsql=\strtolower(\substr($sql,0,4));
		$data=array();
		if (isset($this->result_array[$subsql])){
			switch ($mode){
				case 'ea':
					$data=\pg_fetch_all($result);
					break;
				case 'ea1':
					$data=\pg_fetch_assoc($result,0);
					break;
			}
			if (\c\core::$debug)\c\debug::trace('Result fetch get '.\round((\microtime(true)-$start)*1000,2).' ms');
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
						\c\debug::group('Query result. Count: '.\count($data),\c\error::INFO,\count($data)>10);
						\c\debug::table(\array_slice($data,0,30));
						if (\count($data)>30)\c\debug::trace('Too large data was sliced',\c\error::INFO);
					}
					\c\debug::groupEnd();
				}else{
					\c\debug::trace('No results found',\c\error::WARNING);
				}
			}else{
				\c\debug::trace('Affected '.$this->rows().' rows',false);
			}
			// explain
			if (!@\c\core::$data['db_not_explain']){
				\c\debug::group('Explain select');
				$this->affected_rows=\pg_affected_rows($result);
				$this->num_rows=\pg_num_rows($result);
				$this->insert_id=\pg_last_oid($result);
				\pg_free_result($result);
				\c\debug::table($this->explain($sql));
				\c\debug::groupEnd();
			}
			\c\debug::groupEnd();
		}else{
			\pg_free_result($result);
		}
		return $data;
	}
	function ea1($sql,$bind=array()){
		return $this->execute_assoc($sql,$bind,'ea1');
	}
	function db_limit($sql,$from=0,$count=0){
		return $sql." LIMIT ".(int)$count." OFFSET ".(int)$from;
	}
	function getLenResult(){
		if (\c\core::$debug && !@\c\core::$data['db_not_explain'])return $this->num_rows;
		if ($this->m_result)return \pg_num_rows($this->m_result);
		return 0;
	}
	function insertId($seq=null){
		if ($seq){
			$sql='SELECT last_value FROM "'.$seq.'"';
			$rs=$this->ea1($sql);
			return (isset($rs['last_value'])?$rs['last_value']:0);
		}
		if (\c\core::$debug && !@\c\core::$data['db_not_explain'])return $this->insert_id;
		return \pg_last_oid($this->m_result);
	}
	function rows(){
		if (\c\core::$debug && !@\c\core::$data['db_not_explain'])return $this->affected_rows;
		return \pg_affected_rows($this->result);
	}
	function explain($sql,$bind=array()){
		$sql='explain '.$this->bind($sql,$bind);
		$result=\pg_query($this->connect,$sql);
		if(!$result)return false;
		$data=\pg_fetch_all($result);
		\pg_free_result($result);
		return $data;
	}
	function query($sql,$bind){
		$sql=$this->bind($sql,$bind);
		return \pg_query($this->connect,$sql);
	}
	function fa($result){
		$row=\pg_fetch_assoc($result);
		if (empty($row))\pg_free_result($result);
		return $row;
	}
	function date_from_db($value,$format){
		$format=\strtr($format,self::$date_formats);
		return "TO_CHAR(".$value.",'".$format."')";
	}
	function date_to_db($value,$format=null){
		if ($value===null)return 'now()';
		if ($format)return "to_timestamp(".$value.",'".\strtr($format,self::$date_formats)."')";
		if (!\is_numeric($value))$value=\strtotime($value);
		return "to_timestamp('".\date('Y-m-d H:i:s',$value)."','YYYY-MM-DD HH24:MI:SS')";
	}
}