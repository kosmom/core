<?php
namespace c;
class db_mysql {
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
	private $insert_id;
	private $num_rows;
	private $affected_rows;
	var $m_connect;
	var $m_result=false; // have last query result
	var $error_resume=false;
	private $result_array=array('sele'=>true,'desc'=>true,'show'=>true);

	function __construct($data,$connection_name=''){
		$this->data=$data;
		$this->cn=$connection_name;
	}

	private function charset_mastmach(){
		switch (strtoupper(core::$charset)){
			case core::UTF8:return 'utf8';
			default: return 'cp1251';
		}
	}

	function connect(){
		@$this->m_connect = mysqli_connect('p:'.$this->data['host'], $this->data['login'], $this->data['password'],$this->data['name']);
		if (!$this->m_connect){trigger_error('MySQL connection error.'.mysqli_connect_errno(), E_USER_ERROR);exit;}
		if (core::$debug){
			debug::group('Connection to '.($this->cn?$this->cn:'MySQL'),error::SUCCESS);
			@core::$data['stat']['db_connections']++;
			$stat=explode('  ',mysqli_stat($this->m_connect));
			$out=array();
			foreach ($stat as $item){
				$out[substr($item,0,strpos($item,':') )]=substr($item,strpos($item,':')+2 );
			}
			debug::dir($out);
			debug::groupEnd();
		}
		mysqli_query($this->m_connect,'set names '.$this->charset_mastmach());
	}
	function disconnect(){
		mysqli_close($this->m_connect);
	}
	function beginTransaction(){
		mysqli_begin_transaction($this->m_connect);
	}
	function commit(){
		mysqli_commit($this->m_connect);
	}
	function rollback(){
		mysqli_rollback($this->m_connect);
	}
	function bind($sql,$bind=array()){
		if (sizeof($bind)==0 or !is_array($bind))return $sql;
		$bind2=array();
		foreach ($bind as $key=>$value){
			$bind2[':'.$key]=($value==='' || $value===db::NULL || $value===NULL?'NULL':"'".mysqli_real_escape_string($this->m_connect,$value)."'");
		}
		return strtr($sql,$bind2);
	}
	function execute($sql, $bind=array()){
		return $this->execute_assoc($sql,$bind,'e');
	}
	function execute_assoc($sql,$bind=array(),$mode='ea'){
		if (core::$debug){
			@core::$data['stat']['db_queryes']++;
			debug::group('MySQL query');
			if (ltrim($sql)!=$sql){
				debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',error::ERROR);
				$sql=ltrim($sql);
			}
			debug::trace('SQL:'.$sql,false);
			if ($bind){
				debug::dir(array('BIND:'=>$bind));
			}else{
				debug::trace('BIND: None',false);
			}
			$start=microtime(true);
		}
		$sql=$this->bind($sql,$bind);
		//echo $sql;
		@$_result = mysqli_query($this->m_connect,$sql,MYSQLI_USE_RESULT);
		if (!$_result){
			if (mysqli_error($this->m_connect)=='MySQL server has gone away'){
				$this->disconnect();
				$this->connect();
				$_result = mysqli_query($this->m_connect,$sql,MYSQLI_USE_RESULT);
			}
		}
		if (core::$debug){
			debug::consoleLog('Query execute for '.round((microtime(true)-$start)*1000,2).' ms');
			$start=microtime(true);
		}
		if(!$_result){
			if (core::$debug){
				debug::trace('Query error: '.mysqli_error($this->m_connect),error::ERROR);
				debug::groupEnd();
				debug::trace('MySQL error: '.mysqli_error($this->m_connect),error::ERROR);
				return false;
			}elseif ($this->error_resume){
				return mysqli_error($this->m_connect);
			}else{
				throw new \Exception('DataBase error. Ошибка при работе с базой данных. В скором времени она будет решена. Просим прощения за временные неудобства'.mysqli_error($this->m_connect));
			}
		}
		$subsql=strtolower(substr($sql,0,4));
		$_data = array();
		if (isset($this->result_array[$subsql])){
			switch ($mode){
				case 'ea':
					if (function_exists('mysqli_fetch_all')){
						$_data=\mysqli_fetch_all($_result,MYSQLI_ASSOC);
					}else{
						$data=array();
						while ($_row = mysqli_fetch_array ($_result,MYSQLI_ASSOC))$_data[] = $_row;
					}
					break;
				case 'e':
					if (function_exists('mysqli_fetch_all')){
						$_data=\mysqli_fetch_all($_result,MYSQLI_BOTH);
					}else{
						$data=array();
						while ($_row = mysqli_fetch_array ($_result))$_data[] = $_row;
					}
					break;
				case 'ea1':
					$_data=mysqli_fetch_assoc($_result);
					break;
			}
			if (core::$debug)debug::trace('Result fetch get '.round((microtime(true)-$start)*1000,2).' ms');
		}
		if (core::$debug){
			if (!isset($this->result_array[$subsql]) && $mode!='e')debug::trace('ea function used without result. Better use e function',error::WARNING);
			if (isset($this->result_array[$subsql]) && $mode=='e')debug::trace('e function used with result. Better use ea function',error::WARNING);
			if (isset($this->result_array[$subsql])){
				if ($_data){
					if ($mode=='ea1'){
						debug::group('Query result');
						debug::dir($_data);
					}else{
						debug::group('Query result. Count: '.sizeof($_data),error::INFO,sizeof($_data)>10);
						debug::table(array_slice($_data,0,30));
						if (sizeof($_data)>30)debug::trace('Too large data was sliced',error::INFO);
					}
					debug::groupEnd();
				}else{
					debug::trace('No results found',error::WARNING);
				}
			}else{
				debug::trace('Affected '.$this->rows().' rows',false);
			}
			// explain
			debug::group('Explain select');
			$this->affected_rows=mysqli_affected_rows($this->m_connect);
			$this->num_rows=@mysqli_num_rows($_result);
			$this->insert_id=mysqli_insert_id($this->m_connect);
			@mysqli_free_result($_result);
			debug::table($this->explain($sql));
			debug::groupEnd();
			debug::groupEnd();
		}else{
			@mysqli_free_result($_result);
		}
		return $_data;
	}
	function ea1($sql,$bind=array()){
		return $this->execute_assoc($sql,$bind,'ea1');
	}
	function db_limit($sql, $from=0, $count=0){
		return $sql.' LIMIT '.intval($from).', '.intval($count);
	}
	function getLenResult(){
		if (core::$debug)return $this->num_rows;
		if ($this->m_result)return mysqli_num_rows($this -> m_result);
		return 0;
	}
	function insertId(){
		if (core::$debug)return $this->insert_id;
		return mysqli_insert_id( $this -> m_connect);
	}
	function rows(){
		if (core::$debug)return $this->affected_rows;
		return mysqli_affected_rows( $this -> m_connect);
	}
	function explain($sql,$bind=array()){
		$sql='explain '.$this->bind($sql,$bind);
		$_result = mysqli_query($this->m_connect,$sql,MYSQLI_USE_RESULT);
		if(!$_result)return false;
		if (function_exists('mysqli_fetch_all')){
			$_data=mysqli_fetch_all($_result,MYSQLI_ASSOC);
		}else{
			$data=array();
			while ($_row = mysqli_fetch_array ($_result,MYSQLI_ASSOC))$_data[] = $_row;
		}
		mysqli_free_result($_result);
		return $_data;
	}
	function query($sql,$bind){
		$sql=$this->bind($sql,$bind);
		return mysqli_query($this->m_connect,$sql,MYSQLI_USE_RESULT);
	}
	function fa($_result){
		$row=mysqli_fetch_assoc($_result);
		if (empty($row))mysqli_free_result($_result);
		return $row;
	}
	function date_from_db($value,$format){
		$format=strtr($format,$this::$date_formats);
		return "date_format(".$value.",'".$format."')";
	}
	function date_to_db($value,$format=null){
		if ($value===null)return 'now()';
		if ($format)return "STR_TO_DATE(".$value.",'".strtr($format,$this::$date_formats)."')";
		if (!is_numeric($value))$value=strtotime ($value);
		return "STR_TO_DATE('".date('Y-m-d H:i:s',$value)."','%Y-%m-%d %H:%i:%s')";
	}
}