<?php
namespace c\factory;

class db_oracle {
	private $execute_mode=OCI_COMMIT_ON_SUCCESS;
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
	private $connect;
	var $m_result=false;

	function __construct($data,$connection_name=''){
		$this->data=$data;
		$this->cn=$connection_name;
	}

	function wrapper($object){
		return '"'.$object.'"';
	}
	
	private function charset_mastmach(){
		if (isset($this->data['charset']))return $this->data['charset'];
		switch (strtoupper(\c\core::$charset)){
			case \c\core::UTF8:return 'UTF8';
			default: return 'CL8MSWIN1251';
		}
	}
	function beginTransaction(){
		$this->execute_mode=OCI_NO_AUTO_COMMIT;
		if (\c\core::$debug)\c\debug::trace('Transaction begin',\c\error::SUCCESS);
	}

	function commit(){
		oci_commit($this -> connect);
		$this->execute_mode=OCI_COMMIT_ON_SUCCESS;
		if (\c\core::$debug)\c\debug::trace('Transaction commit',\c\error::SUCCESS);
	}
	function rollback(){
		oci_rollback($this -> connect);
		$this->execute_mode=OCI_COMMIT_ON_SUCCESS;
		if (\c\core::$debug)\c\debug::trace('Transaction rollback',\c\error::SUCCESS);
	}

	function connect(){
		if ($this->data['persistent']){
			$this->connect = oci_pconnect($this->data['login'], $this->data['password'], $this->data['host'],$this->charset_mastmach());
		}else{
			$this->connect = oci_connect($this->data['login'], $this->data['password'], $this->data['host'],$this->charset_mastmach());
		}
		if (!$this -> connect){
			$error=oci_error();
			if (\c\core::$debug)\c\debug::trace('Oracle connection error: '.$error['message'],\c\error::ERROR);
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception("Error connection to oracle: .".$error['message']);
		}
		if (\c\core::$debug){
			\c\debug::trace('Connection to '.($this->cn?$this->cn:'Oracle'),\c\error::SUCCESS);
			@\c\core::$data['stat']['db_connections']++;
		}
		$sql="ALTER SESSION SET NLS_TERRITORY='CIS' nls_date_format='yyyy-mm-dd hh24:mi:ss'";
		$stmt = oci_parse($this -> connect, $sql);
		oci_execute($stmt,$this->execute_mode);
		oci_free_statement($stmt);
		return true;
	}

	function execute($sql, $bind = array(), $mode='e'){
		if (\c\core::$debug){
			@\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('Oracle query');
			if (ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=ltrim($sql);
			}
			\c\debug::trace('Connection: '.$this->cn,false);
			\c\debug::trace('SQL: '.$sql,false);
			if ($bind){
				\c\debug::dir(array('BIND:'=>$bind));
			}else{
				\c\debug::trace('BIND: None',false);
			}
			$start=microtime(true);
		}
		$stmt = oci_parse($this -> connect, $sql);
		if(!$stmt){
			$error=oci_error($this -> connect);
			if (\c\core::$debug){
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL parsing error');
		}
		if(is_array($bind)) {
			foreach($bind as $key => &$value){
				oci_bind_by_name($stmt,':'.$key, $value,-1);
			}
		}
		$result=oci_execute($stmt,$this->execute_mode);
		if (\c\core::$debug){
			\c\debug::consoleLog('Query get '.round((microtime(true)-$start)*1000,2).' ms');
			$start=microtime(true);
		}
		if ($error=oci_error($stmt)){
			if (\c\core::$debug){
				\c\debug::consoleLog('Query error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::consoleLog('Oracle error: '.$error['code'].' - '.$error['message'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL execute error');
		}
		if (!$result){
			if (\c\core::$debug)\c\debug::trace('No results found',\c\error::WARNING);
			\c\debug::groupEnd();
			oci_free_statement($stmt);
			return false;
		}

		$subsql=strtolower(substr($sql,0,5));
		$isResult=$subsql == 'selec' || $subsql=='with ';
		if($isResult){
			$_data = array();
			switch ($mode){
				case 'ea':
						oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
	//while ($_row = oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
						break;
				case 'e':
						oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_BOTH+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
						//while ($_row = oci_fetch_array($stmt,OCI_BOTH+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
						break;
				case 'ea1':
						$_data=oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
						break;
			}
			oci_free_statement($stmt);
			if (\c\core::$debug)\c\debug::trace('Result fetch get '.round((microtime(true)-$start)*1000,2).' ms');
		}else {
		$_data = true;
		if (\c\core::$debug)\c\debug::trace('Affected '.$this->rows().' rows',false);
		}
		if (\c\core::$debug){
		if (!$isResult && $mode!='e')\c\debug::trace('ea function used without result. Better use e function',\c\error::WARNING);
			if ($isResult && $mode=='e')\c\debug::trace('e function used with result. Better use ea function',\c\error::WARNING);
			if ($isResult){
				if ($_data){
					if ($mode=='ea1'){
						\c\debug::group('Query result');
						\c\debug::dir($_data);
					}else{
						\c\debug::group('Query result. Count: '.sizeof($_data),\c\error::INFO,sizeof($_data)>10);
						\c\debug::table(array_slice($_data,0,30));
						if (sizeof($_data)>30)\c\debug::trace('Too large data was sliced',\c\error::INFO);
					}
					\c\debug::groupEnd();
				}else{
					\c\debug::trace('No results found',\c\error::WARNING);
				}
			}else{
				\c\debug::trace('Affected '.$this->rows().' rows',false);
			}
			// explain
			\c\debug::group('Explain select');
			\c\debug::table($this->explain($sql));
			\c\debug::groupEnd();

			\c\debug::groupEnd();
		}
		return $_data;
	}
	private function slashes($bind){
		$bind= str_replace("'", "''", $bind);
		return $bind;
	}
	function massExecute($sql,$begin_sql,$repeat_sql,$end_sql, $binds = array()){
		if (\c\core::$debug){
			@\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('Oracle query');
			if (ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=ltrim($sql);
			}
			\c\debug::trace('SQL: '.$sql,false);
			\c\debug::trace('Connection: '.$this->cn,false);
			if ($binds){
				\c\debug::dir(array('BIND:'=>$binds));
			}else{
				\c\debug::trace('BIND: None',false);
			}
			$start=microtime(true);
		}
		$total_sql=array();
		foreach ($binds as $bind){
			$msql=$sql;
			foreach($bind as $key => $value){
				$msql= str_replace(':'.$key, "'".$this->slashes($value)."'", $msql);
			}
			$total_sql[]=$msql;
		}
		$sql=$begin_sql. implode($repeat_sql,$total_sql).$end_sql;
		\c\debug::trace('SQL:'.$sql,false);
		$stmt = oci_parse($this -> connect, $sql);
		if(!$stmt){
			$error=oci_error($this -> connect);
			if (\c\core::$debug){
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL parsing error');
		}
		$result=oci_execute($stmt,$this->execute_mode);
		if (\c\core::$debug){
			\c\debug::consoleLog('Query get '.round((microtime(true)-$start)*1000,2).' ms');
			$start=microtime(true);
		}
		if ($error=oci_error($stmt)){
			if (\c\core::$debug){
				\c\debug::consoleLog('Oracle error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::consoleLog('Oracle error: '.$error['code'].' - '.$error['message'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL execute error');
		}
		\c\debug::groupEnd();
		oci_free_statement($stmt);
		return false;
	}
	
	function execute_ref($sql, &$bind, $assoc=false){
		if (\c\core::$debug){
			\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('Oracle query');
			if (ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=ltrim($sql);
			}
			\c\debug::trace('Connection: '.$this->cn,false);
			\c\debug::trace('SQL: '.$sql,false);
			if ($bind){
				\c\debug::dir(array('BIND:'=>$bind));
			}else{
				\c\debug::trace('BIND: None',false);
			}
			$start=microtime(true);
		}

		$stmt = oci_parse($this -> connect, $sql);
		if(!$stmt){
			$error=oci_error($this -> connect);
			if (\c\core::$debug){
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL parsing error');
		}
		if(is_array($bind) && count($bind)) {
			foreach($bind as $key => &$value) {
					oci_bind_by_name($stmt,  ":".$key, $value, -1);
			}
		}
		$result=oci_execute($stmt);
		if (!$result){
			$error=oci_error($this -> connect);
			if (\c\core::$debug){
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL execute error');
		}
		
		$subsql=strtolower(substr($sql,0,5));
		$isResult=$subsql == 'selec' || $subsql=='with ';
		if($isResult){
			$_data = array();
			if ($assoc){
				oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
				//while ($_row = oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
			}else{
				oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_BOTH+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
				//while ($_row = oci_fetch_array($stmt,OCI_BOTH+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
			}
		}else{
			$_data = true;
		}
		if (\c\core::$debug){
			if ($isResult){
				if ($_data){
					\c\debug::group('Query result. Count: '.sizeof($_data),\c\error::INFO,sizeof($_data)>10);
					\c\debug::table(array_slice($_data,0,30));
					if (sizeof($_data)>30)\c\debug::trace('Too large data was sliced',\c\error::INFO);
					\c\debug::groupEnd();
				}else{
					\c\debug::trace('No results found',\c\error::WARNING);
				}
			}else{
				\c\debug::trace('Affected '.$this->rows().' rows',false);
			}
			\c\debug::groupEnd();
		}
//		oci_free_statement($stmt);
		return $_data;
	}

	function execute_assoc($sql, $bind = array()){
		return $this->execute($sql, $bind, 'ea');
	}
	function ea1($sql, $bind = array()){
		return $this->execute_assoc_1($sql, $bind);
	}
	
	function db_limit($sql, $from=0, $count=0){
		//if (!$from)return 'select * from ('.$sql.') WHERE rownum < '.((int)$count+1);
		$from++;
		return 'select * from (SELECT rownum rnum, aa.* FROM ('.$sql.') aa) WHERE rnum >= '.(int)$from.' AND rnum < '.(intval($from + $count));
	}

	function rows(){
		if ($this -> m_result) return oci_num_rows($this -> m_result);
		return 0;
	}

	function insertId($seq=false){
		if(!$seq) return 0;
		$sql = 'SELECT '.$seq.'.currval last FROM dual';
		$rs = $this->execute($sql,$this->execute_mode);
		return (isset($rs[0]['LAST'])?$rs[0]['LAST']:0);
	}
	function explain($sql){
		$stmt = oci_parse($this -> connect,"delete from plan_table where statement_id='core_sql'");
		if(!$stmt)return false;
		oci_execute($stmt,$this->execute_mode);

		$stmt = oci_parse($this -> connect,"explain plan set statement_id='core_sql' for ".$sql);
		if(!$stmt)return false;
		@oci_execute($stmt,$this->execute_mode);

		$stmt = oci_parse($this -> connect, "select lpad(' ',depth)||operation operation,options,case when object_owner is null then '' else object_owner||'.' end||object_name object,filter_predicates||access_predicates predicates,cost from PLAN_TABLE where statement_id='core_sql' order by plan_id desc,ID");
		$result=oci_execute($stmt,$this->execute_mode);
		$data=array();
		oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_ASSOC+OCI_RETURN_NULLS);
		oci_free_statement($stmt);
		return $_data;
	}
function execute_assoc_1($sql, $bind = array()){
		return $this->execute($sql, $bind,'ea1');
	}
	function query($sql,$bind){
		if (\c\core::$debug){
			\c\core::$data['stat']['db_queryes']++;
			\c\debug::group('Oracle query');
			if (ltrim($sql)!=$sql){
				\c\debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',\c\error::ERROR);
				$sql=ltrim($sql);
			}
			\c\debug::trace('SQL:'.$sql,false);
			if ($bind){
				\c\debug::dir(array('BIND:'=>$bind));
			}else{
				\c\debug::trace('BIND: None',false);
			}
			$start=microtime(true);
		}
		$stmt = oci_parse($this -> connect, $sql);
		if(!$stmt){
			$error=oci_error($this -> connect);
			if (\c\core::$debug){
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
				\c\debug::groupEnd();
				\c\debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],\c\error::ERROR);
			}
			if (empty(\c\core::$data['db_exception']))return false;
			throw new \Exception('SQL parsing error');
		}
		if(is_array($bind)) {
			foreach($bind as $key => &$value){
				oci_bind_by_name($stmt,':'.$key, $value,-1);
			}
		}
		$result=oci_execute($stmt,$this->execute_mode);
		if (\c\core::$debug){
			\c\debug::consoleLog('Query get '.round((microtime(true)-$start)*1000,2).' ms');
			\c\debug::groupEnd();
		}
		if (!$result){
			oci_free_statement($stmt);
			return false;
		}
		return $stmt;
	}
	function fa($stmt){
		$row=oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
		if (empty($row))oci_free_statement($stmt);
		return $row;
	}

	function date_from_db($value,$format){
		$format=strtr($format,self::$date_formats);
		return 'TO_CHAR('.$value.",'".$format."')";
	}

	function date_to_db($value,$format=null){
		if ($value===null)return 'SYSDATE';
		if ($format)return 'TO_DATE('.$value.",'".strtr($format,$this::$date_formats)."')";
		if (!is_numeric($value))$value=strtotime ($value);
		return "TO_DATE('".date('Y-m-d H:i:s',$value)."','YYYY-MM-DD HH24:MI:SS')";
	}
}