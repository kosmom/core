<?php
namespace c;
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
	var $m_connect;
	var $m_result=false;
	private $return_onerror=true;

	function __construct($data,$connection_name=''){
		$this->data=$data;
		$this->cn=$connection_name;
	}

	private function charset_mastmach(){
		switch (strtoupper(core::$charset)){
			case core::UTF8:return 'UTF8';
			default: return 'CL8MSWIN1251';
		}
	}
	function beginTransaction(){
		$this->execute_mode=OCI_NO_AUTO_COMMIT;
		if (core::$debug)debug::trace('Transaction begin',error::SUCCESS);
	}

	function commit(){
		oci_commit($this -> m_connect);
		$this->execute_mode=OCI_COMMIT_ON_SUCCESS;
		if (core::$debug)debug::trace('Transaction commit',error::SUCCESS);
	}
	function rollback(){
		oci_rollback($this -> m_connect);
		$this->execute_mode=OCI_COMMIT_ON_SUCCESS;
		if (core::$debug)debug::trace('Transaction rollback',error::SUCCESS);
	}

	function connect(){
		$this->m_connect = oci_pconnect($this->data['login'], $this->data['password'], $this->data['host'],$this->charset_mastmach());
		if (!$this -> m_connect){
			$error=oci_error();
			if (core::$debug){
				debug::trace('Oracle connection error: '.$error['message'],error::ERROR);
				return false;
			}
			if ($this->return_onerror)return false;
			trigger_error("Error connection to oracle: .".$error['message'], E_USER_ERROR);exit;
		}
		if (core::$debug){
			debug::trace('Connection to '.($this->cn?$this->cn:'Oracle'),error::SUCCESS);
			@core::$data['stat']['db_connections']++;
		}
		$sql="ALTER SESSION SET NLS_TERRITORY='CIS' nls_date_format='yyyy-mm-dd hh24:mi:ss'";
		$stmt = oci_parse($this -> m_connect, $sql);
		oci_execute($stmt,$this->execute_mode);
		oci_free_statement($stmt);
		return true;
	}

	function execute($sql, $bind = array(), $mode='e'){
		if (core::$debug){
			@core::$data['stat']['db_queryes']++;
			debug::group('Oracle query');
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
		$stmt = oci_parse($this -> m_connect, $sql);
		if(!$stmt){
			$error=oci_error($this -> m_connect);
			if (core::$debug){
				debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				debug::groupEnd();
				debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				return false;
			}
			print_r($error);
			die('Class: DbOracle -> Function: Execute() -> Error: Parse Error<br><br><b>SQL</b>: '.$sql);
			exit;
		}
		if(is_array($bind)) {
			foreach($bind as $key => &$value){
				oci_bind_by_name($stmt,':'.$key, $value,-1);
			}
		}
		$result=oci_execute($stmt,$this->execute_mode);
		if (core::$debug){
			debug::consoleLog('Query get '.round((microtime(true)-$start)*1000,2).' ms');
			$start=microtime(true);
		}
		if ($error=oci_error($stmt)){
			if (core::$debug){
				debug::consoleLog('Query error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				debug::groupEnd();
				debug::consoleLog('Oracle error: '.$error['code'].' - '.$error['message'],error::ERROR);
				return false;
			}elseif ($this->return_onerror){
				return false;
			}else{
				echo 'Class: DbOracle -> Function: Connect() -> Error: Parse Error<br><br><b>SQL</b>: '.$sql;
			}
		}
		if (!$result){
			if (core::$debug)debug::trace('No results found',error::WARNING);
			debug::groupEnd();
			oci_free_statement($stmt);
			return false;
		}

		$subsql=strtolower(substr($sql,0,5));
		if($subsql != 'selec' && $subsql!='with '){
			$_data = true;
			if (core::$debug)debug::trace('Affected '.$this->rows().' rows',false);
		}else {
			$_data = array();
			switch ($mode){
				case 'ea':
					oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
//                    while ($_row = oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
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
			if (core::$debug)debug::trace('Result fetch get '.round((microtime(true)-$start)*1000,2).' ms');
		}
		if (core::$debug){
		if ($subsql != 'selec' && $mode!='e')debug::trace('ea function used without result. Better use e function',error::WARNING);
			if ($subsql == 'selec' && $mode=='e')debug::trace('e function used with result. Better use ea function',error::WARNING);
			if ($subsql == 'selec'){
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
			debug::table($this->explain($sql));
			debug::groupEnd();

			debug::groupEnd();
		}
		return $_data;
	}
	private function slashes($bind){
		$bind= str_replace("'", "''", $bind);
		return $bind;
	}
	function massExecute($sql,$begin_sql,$repeat_sql,$end_sql, $binds = array()){
		if (core::$debug){
			@core::$data['stat']['db_queryes']++;
			debug::group('Oracle query');
			if (ltrim($sql)!=$sql){
				debug::trace('clear whitespaces at begin of query for correct work. Autocorrect in debug mode',error::ERROR);
				$sql=ltrim($sql);
			}
			debug::trace('SQL:'.$sql,false);
			if ($binds){
				debug::dir(array('BIND:'=>$binds));
			}else{
				debug::trace('BIND: None',false);
			}
			$start=microtime(true);
		}
		$total_sql=array();
		foreach ($binds as $bind){
			$msql=$sql;
			foreach($bind as $key => $value){
				//oci_bind_by_name($stmt,':'.$key, $value,-1);
				$msql= str_replace(':'.$key, "'".self::slashes($value)."'", $msql);
			}
			$total_sql[]=$msql;
		}
		$sql=$begin_sql. implode($repeat_sql,$total_sql).$end_sql;
		debug::trace('SQL:'.$sql,false);
		$stmt = oci_parse($this -> m_connect, $sql);
		if(!$stmt){
			$error=oci_error($this -> m_connect);
			if (core::$debug){
				debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				debug::groupEnd();
				debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				return false;
			}
			print_r($error);
			die('Class: DbOracle -> Function: Execute() -> Error: Parse Error<br><br><b>SQL</b>: '.$sql);
			exit;
		}
		$result=oci_execute($stmt,$this->execute_mode);
		if (core::$debug){
			debug::consoleLog('Query get '.round((microtime(true)-$start)*1000,2).' ms');
			$start=microtime(true);
		}
		if ($error=oci_error($stmt)){
			if (core::$debug){
				debug::consoleLog('Query error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				debug::groupEnd();
				debug::consoleLog('Oracle error: '.$error['code'].' - '.$error['message'],error::ERROR);
				return false;
			}elseif ($this->return_onerror){
				return false;
			}else{
				echo 'Class: DbOracle -> Function: Connect() -> Error: Parse Error<br><br><b>SQL</b>: '.$sql;
			}
		}
		debug::groupEnd();
		oci_free_statement($stmt);
		return false;
	}
	
	function execute_ref($sql, &$bind, $assoc=false){
		if (core::$debug){
			core::$data['stat']['db_queryes']++;
			debug::group('Oracle query');
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

		if($stmt = oci_parse($this -> m_connect, $sql)){
			if(is_array($bind) && count($bind)) {
					foreach($bind as $key => &$value) {
						oci_bind_by_name($stmt,  ":".$key, $value, -1);
					}
			}
			$result=oci_execute($stmt);
			if (!$result){
				if ($this->return_onerror){
					if (core::$debug)debug::groupEnd();
					return false;
				}
				$error=oci_error();
				echo "Class: DbOracle -> Function: Connect() -> Error: Parse Error<br><br><b>SQL</b>: ".$sql;
			}
		}else{
			$error=oci_error($this -> m_connect);
			if (core::$debug){
				debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				debug::groupEnd();
				debug::trace('Oracle parse error: '.$error['code'].' - '.$error['message'].' on '.$error['offset'],error::ERROR);
				return false;
			}
			print_r($error);
			die('Class: DbOracle -> Function: Execute() -> Error: Parse Error<br><br><b>SQL</b>: '.$sql);
			exit;
		}
		$subsql=strtolower(substr($sql,0,5));
		if($subsql != 'selec' && $subsql!='with '){
			$_data = true;
		}else{
			$_data = array();
			if ($assoc){
				oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
				//while ($_row = oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
			}else{
				oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_BOTH+OCI_RETURN_LOBS+OCI_RETURN_NULLS);
				//while ($_row = oci_fetch_array($stmt,OCI_BOTH+OCI_RETURN_LOBS+OCI_RETURN_NULLS))$_data[] = $_row;
			}
		}
		if (core::$debug){
			if ($subsql == 'selec'){
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
			debug::groupEnd();
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
	function execute_assoc_1($sql, $bind = array()){
		return $this->execute($sql, $bind,'ea1');
	}
	function execQuery($sql, $bind = array()){
		if($this -> m_result = oci_parse($this -> m_connect, $sql)) {
			if(count($bind)) {
				foreach($bind as $key => $value) {
					oci_bind_by_name($this -> m_result,  ':'.$key, $bind[$key]);
				}
			}
			oci_execute($this -> m_result,$this->execute_mode);
		}else{
			print_r(oci_error());
			die('Class: DbOracle -> Function: Execute() -> Error: Parse Error<br><br><b>SQL</b>: '.$sql);
			exit;
		}

		return true;
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
		$stmt = oci_parse($this -> m_connect,"delete from plan_table where statement_id='core_sql'");
		if(!$stmt)return false;
		oci_execute($stmt,$this->execute_mode);

		$stmt = oci_parse($this -> m_connect,"explain plan set statement_id='core_sql' for ".$sql);
		if(!$stmt)return false;
		@oci_execute($stmt,$this->execute_mode);

		$stmt = oci_parse($this -> m_connect, "select lpad(' ',depth)||operation operation,options,case when object_owner is null then '' else object_owner||'.' end||object_name object,filter_predicates||access_predicates predicates,cost from PLAN_TABLE where statement_id='core_sql' order by plan_id desc,ID");
		$result=oci_execute($stmt,$this->execute_mode);
		$data=array();
		oci_fetch_all($stmt,$_data,0,-1,OCI_FETCHSTATEMENT_BY_ROW+OCI_ASSOC+OCI_RETURN_NULLS);
		oci_free_statement($stmt);
		return $_data;
	}

	function query($sql,$bind){
		if (core::$debug){
			core::$data['stat']['db_queryes']++;
			debug::group('Oracle query');
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
		$stmt = oci_parse($this -> m_connect, $sql);
		if(is_array($bind)) {
			foreach($bind as $key => &$value){
				oci_bind_by_name($stmt,':'.$key, $value,-1);
			}
		}
		$result=oci_execute($stmt,$this->execute_mode);
		if (core::$debug){
			debug::consoleLog('Query get '.round((microtime(true)-$start)*1000,2).' ms');
			debug::groupEnd();
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
		$format=strtr($format,$this::$date_formats);
		return 'TO_CHAR('.$value.",'".$format."')";
	}

	function date_to_db($value,$format=null){
		if ($value===null)return 'SYSDATE';
		if ($format)return 'TO_DATE('.$value.",'".strtr($format,$this::$date_formats)."')";
		if (!is_numeric($value))$value=strtotime ($value);
		return "TO_DATE('".date('Y-m-d H:i:s',$value)."','YYYY-MM-DD HH24:MI:SS')";
	}
}