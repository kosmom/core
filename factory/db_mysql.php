<?php
namespace c\factory;

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
			case \c\core::UTF8:return 'utf8';
			default: return 'cp1251';
		}
	}

	function connect(){
		@$this->connect = \mysqli_connect(($this->data['persistent']?'p:':'').$this->data['host'],$this->data['login'], $this->data['password'],$this->data['name'],isset($this->data['port'])?$this->data['port']:3306);
		if (!$this->connect)throw new \Exception('MySQL connection error '.\mysqli_connect_errno());
		if (\c\core::$debug){
			\c\debug::group('Connection to '.($this->cn?$this->cn:'MySQL'),\c\error::SUCCESS);
			@\c\core::$data['stat']['db_connections']++;
			$stat=\explode('  ',\mysqli_stat($this->connect));
			$out=array();
			foreach ($stat as $item){
				$out[\substr($item,0,\strpos($item,':') )]=\substr($item,\strpos($item,':')+2 );
			}
			\c\debug::dir($out);
			\c\debug::groupEnd();
		}
		\mysqli_query($this->connect,'set names '.$this->charset_mastmach());
	}
	function disconnect(){
		\mysqli_close($this->connect);
	}
	function beginTransaction(){
		\mysqli_begin_transaction($this->connect);
	}
	function commit(){
		\mysqli_commit($this->connect);
	}
	function rollback(){
		\mysqli_rollback($this->connect);
	}
	function bind($sql,$bind=array()){
		if (\sizeof($bind)==0 || !\is_array($bind))return $sql;
		$bind2=array();
		foreach ($bind as $key=>$value){
			$bind2[':'.$key]=($value===\c\db::NULL || $value===\null?'NULL': ($value instanceof \c\db?$value:"'".\mysqli_real_escape_string($this->connect,$value)."'"));
		}
		return \strtr($sql,$bind2);
	}
	function execute($sql, $bind=array()){
		return $this->execute_assoc($sql,$bind,'e');
	}
	function execute_assoc($sql,$bind=array(),$mode='ea'){
		if (\c\core::$debug){
			@\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('MySQL query');
			if (\ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=\ltrim($sql);
			}
			\c\debug::trace('Connection: '.$this->cn,\false);
			\c\debug::trace('SQL: '.$sql,\false);
			if ($bind){
				\c\debug::dir(array('BIND:'=>$bind));
			}else{
				\c\debug::trace('BIND: None',\false);
			}
			$start=\microtime(\true);
		}
		$sql=$this->bind($sql,$bind);
		//echo $sql;
		@$result = \mysqli_query($this->connect,$sql);
		if (!$result && \mysqli_error($this->connect)=='MySQL server has gone away'){
			$this->disconnect();
			$this->connect();
			$result=\mysqli_query($this->connect,$sql);
		}
		if (\c\core::$debug){
			\c\debug::consoleLog('Query execute for '.\round((\microtime(\true)-$start)*1000,2).' ms');
			$start=\microtime(\true);
		}
		if(!$result){
			if (\c\core::$debug){
				\c\debug::trace('Query error: '.\mysqli_error($this->connect),\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('MySQL error: '.\mysqli_error($this->connect),\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return \false;
			throw new \Exception('SQL execute error: '.\mysqli_error($this->connect));
		}
		$subsql=\strtolower(\substr($sql,0,4));
		$data=array();
		if (isset($this->result_array[$subsql])){
			switch ($mode){
				case 'ea':
					if (\function_exists('mysqli_fetch_all')){
						$data=\mysqli_fetch_all($result,\MYSQLI_ASSOC);
					}else{
						$data=array();
						while ($row=\mysqli_fetch_assoc($result))$data[]=$row;
					}
					break;
				case 'e':
					if (\function_exists('mysqli_fetch_all')){
						$data=\mysqli_fetch_all($result,\MYSQLI_BOTH);
					}else{
						$data=array();
						while ($row=\mysqli_fetch_array($result))$data[]=$row;
					}
					break;
				case 'ea1':
					$data=\mysqli_fetch_assoc($result);
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
				\c\debug::trace('Affected '.\mysqli_affected_rows($this->connect).' rows', \false);
			}
			// explain
			if (!@\c\core::$data['db_not_explain'] && \substr($sql,0,5)!='show '){
				\c\debug::group('Explain select');
				$this->affected_rows=\mysqli_affected_rows($this->connect);
				$this->insert_id=\mysqli_insert_id($this->connect);
				if (!\is_bool($result)){
					$this->num_rows=@\mysqli_num_rows($result);
					\mysqli_free_result($result);
				}
				\c\debug::table($this->explain($sql));
				\c\debug::groupEnd();
			}
			\c\debug::groupEnd();
		}elseif (!\is_bool($result)){
			@\mysqli_free_result($result);
		}
		return $data;
	}
	function ea1($sql,$bind=array()){
		return $this->execute_assoc($sql,$bind,'ea1');
	}
	function db_limit($sql, $from=0, $count=0){
		$count=\intval($count);
		return $sql.' LIMIT '.\intval($from).($count?', '.$count:'');
	}
	function getLenResult(){
		if (\c\core::$debug && !@\c\core::$data['db_not_explain'])return $this->num_rows;
		if ($this->m_result)return \mysqli_num_rows($this->m_result);
		return 0;
	}
	function insertId(){
		if (\c\core::$debug && !@\c\core::$data['db_not_explain'])return $this->insert_id;
		return \mysqli_insert_id($this->connect);
	}
	function rows(){
		if (\c\core::$debug && !@\c\core::$data['db_not_explain'])return $this->affected_rows;
		return \mysqli_affected_rows($this->connect);
	}
	function explain($sql,$bind=array()){
		$sql='explain '.$this->bind($sql,$bind);
		$result = \mysqli_query($this->connect,$sql,\MYSQLI_USE_RESULT);
		if(!$result)return \false;
		if (\function_exists('mysqli_fetch_all')){
			$data=\mysqli_fetch_all($result,\MYSQLI_ASSOC);
		}else{
			$data=array();
			while ($row=\mysqli_fetch_assoc($result))$data[]=$row;
		}
		\mysqli_free_result($result);
		return $data;
	}
	function query($sql,$bind){
		$sql=$this->bind($sql,$bind);
		return \mysqli_query($this->connect,$sql,\MYSQLI_USE_RESULT);
	}
	function fa($result){
		$row=\mysqli_fetch_assoc($result);
		if (empty($row))\mysqli_free_result($result);
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