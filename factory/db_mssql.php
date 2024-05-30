<?php
namespace c\factory;

class db_mssql{
	var $data;
	var $cn;
	private $connect;
	var $m_result=\false;
	var $error_resume=\true;
	private $result_array=array('sele'=>\true,'show'=>\true);
	function __construct($data,$connection_name=''){
		$this->data=$data;
		$this->cn=$connection_name;
	}
	function wrapper($object){
		return $object;
	}
	function connect(){
		$this->connect=$this->data['persistent']?\mssql_pconnect($this->data['host'],$this->data['login'],$this->data['password']):\mssql_connect($this->data['host'],$this->data['login'],$this->data['password']);
		if (!$this->connect){
			if (\c\core::$debug){
				\c\debug::trace('Mssql connection error',\c\error::ERROR);
				return \false;
			}
			throw new \Exception('Error connection to mssql');
		}

		if (!$this->connect)throw new \Exception('Mssql Connection Error');
		if (\c\core::$debug){
			\c\debug::group('Connection to '.($this->cn?$this->cn:'MsSql'),\c\error::SUCCESS);
			\c\core::$data['stat']['db_connections']++;
			\c\debug::groupEnd();
		}
		return \true;
	}
	function disconnect(){
		\mssql_close($this->connect);
	}
	function bind($sql,$bind=array()){
		if (\sizeof($bind)==0 || !\is_array($bind))return $sql;
		$bind2=array();
		foreach ($bind as $key=>$value){
			$bind2[':'.$key]=($value===\null?'NULL':($value===''?"''": ($value instanceof \c\db?$value:"'".\strtr($value,["'"=>"''","\\"=>"\\\\"])."'")));
		}
		return \strtr($sql,$bind2);
	}
	function execute($sql, $bind=array(), $mode='e'){
		if (\c\core::$debug){
			\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('MsSQL query');
			if (\ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=\ltrim($sql);
			}
			\c\debug::trace('SQL:'.$sql,\false);
			if ($bind){
				\c\debug::trace('BIND:',\false);
				\c\debug::dir($bind);
			}else{
				\c\debug::trace('BIND: None',\false);
			}
			$start=\microtime(\true);
		}
		$sql=$this->bind($sql,$bind);
		if (\c\core::$debug)\c\debug::consoleLog('Full query: '.$sql);
		$result=\mssql_query($sql,$this->connect);
		if (\c\core::$debug){
			\c\debug::consoleLog('Query get '.\round((\microtime(\true)-$start)*1000,2).' ms');
			$start=\microtime(\true);
		}
		if(!$result){
			if (\c\core::$debug){
				\c\debug::trace('Mssql Query error: '.\mssql_get_last_message(),\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('MsSQL Query error: '.\mssql_get_last_message(),\c\error::ERROR);
				return \false;
			}elseif ($this->error_resume){
				return 'MsSql execute error: '.\mssql_get_last_message();
			}else{
				throw new \Exception("MsSql execute error: ".\mssql_get_last_message(),\E_USER_ERROR);
			}
		}
		$data = array();
		$subsql=\strtolower(\substr($sql,0,4));
		if (isset($this->result_array[$subsql])){
			switch ($mode){
				case 'ea':
					while ($row=\mssql_fetch_assoc($result))$data[]=$row;
					break;
				case 'e':
					while ($row=\mssql_fetch_array($result))$data[]=$row;
					break;
				case 'ea1':
					$data=\mssql_fetch_assoc($result);
					break;
			}
			\mssql_free_result($result);
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
						if (sizeof($data)>30)\c\debug::trace('Too large data was sliced',\c\error::INFO);
					}
					\c\debug::groupEnd();
				}else{
					\c\debug::trace('No results found',\c\error::WARNING);
				}
			}else{
				\c\debug::trace('Affected '.$this->rows().' rows',\false);
			}
// explain
//			debug::group('Explain select');
//			debug::table($this->explain($sql));
//			debug::groupEnd();

			\c\debug::groupEnd();
		}
		return $data;
	}
	function execute_assoc($sql,$bind){
		return $this->execute($sql,$bind,'ea');
	}
	function ea1($sql,$bind=array()){
		return $this->execute($sql,$bind,'ea1');
	}

	function getLenResult(){
		if ($this->m_result)return \mssql_num_rows($this->m_result);
		return 0;
	}

	function insertId(){
		return \false;
	}

	function rows(){
		return \mssql_rows_affected($this->connect);
	}

	function db_limit($sql,$from=0,$count=0,$order_fild='1',$order_dir='DESC'){
		if ($order_dir == "DESC"){
			$o1="ASC";
			$o2="DESC";
		}else{
			$o1="DESC";
			$o2="ASC";
		}
		if ($from != 0) return "SELECT * FROM (SELECT TOP ".$count." * FROM (SELECT TOP ".($from + $count)." * FROM (".$sql.") DBLIMIT1 ORDER BY ".$order_fild." ".$o2.") DBLIMIT2 ORDER BY ".$order_fild." ".$o1.") DBLIM";
		else return "SELECT TOP ".($from + $count)." * FROM (".$sql.") DBLIMIT2 ";
	}
	function explain(){
		return \false;
	}
}