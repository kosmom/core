<?php
namespace c;

/**
 * Work with database
 * @access private Use DB class
 * @author Kosmom <Kosmom.ru>
 */
class dbwork{
	static $data=array();
	private static $storyData=array();
	private static $describeStorage=array();
	const SYSDATE='{SYSDATE}';
	const NOW='{SYSDATE}';
	const NEXTVAL='{NEXTVAL}';
	const INFINITY='{INFINITY}';
	const NULL='{NULL}';

	/**
	 * Prepare where part for diap text or number field
	 * @param string $string input string
	 * @param string $field field in database
	 * @param array $bind source bind array
	 * @param string $bindPrefix
	 * @param string $blockDelimeter
	 * @param tring $diapDilimeter
	 * @return string where array part
	 * @deprecated since version 3.4
	 */
	static function filter_diap($string,$field,&$bind,$bindPrefix=null,$blockDelimeter=',',$diapDilimeter='-'){
		return self::filterDiap($string,$field,$bind,$bindPrefix,$blockDelimeter,$diapDilimeter);
	}
	/**
	 * Prepare where part for diap text or number field
	 * @param string $string input string
	 * @param string $field field in database
	 * @param array $bind source bind array
	 * @param string $bindPrefix
	 * @param string $blockDelimeter
	 * @param tring $diapDilimeter
	 * @return string where array part
	 */
	static function filterDiap($string,$field,&$bind,$bindPrefix=null,$blockDelimeter=',',$diapDilimeter='-'){
		if ($bindPrefix===null)$bindPrefix=$field.'_';
		$size=sizeof($bind);
		$blocks=explode($blockDelimeter,$string);
		// array between diap and array values
		$subwhere=array();
		$arrayPoint=array();

		foreach($blocks as $key=>$item){
			if ($item==='')continue;
			$diap=explode($diapDilimeter,trim($item));
	//			if (!isset($diap[1])){
	//				$array_point=$diap[0];
	//				continue;
	//			}
			$min=min($diap);
			$max=max($diap);
			$bind[$bindPrefix.($size+$key)]=$min;
			if ($min===$max){
				$arrayPoint[]=':'.$bindPrefix.($size+$key);
				continue;
			}
			$subwhere[]=$field.' BETWEEN :'.$bindPrefix.($size+$key).' AND :'.$bindPrefix.($size+$key).'_max';
			$bind[$bindPrefix.($size+$key).'_max']=$max;
		}
		switch (sizeof($arrayPoint)){
			case 0:
				break;
			case 1:
				$subwhere[]=$field.' = '.$arrayPoint[0];
				break;
			default:
				$subwhere[]=$field.' IN ('.implode(' , ',$arrayPoint).')';
		}
		return implode(' OR ',$subwhere);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function describe_table($tablename,$db=''){
		return self::describeTable($tablename,'',$db);
	}
	/**
	 * Get struct of table
	 * @param string $tablename Table name
	 * @param string $db Database
	 * @return array [datatable struct,primary key,unique keys]
	 */
	static function describeTable($tablename,$schema='',$db=''){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=db::autodb($db);
		$defaultScheme=$schema?$schema:$db;
		if (core::$debug){
			debug::group('DBWork describeTable start');
			debug::consoleLog('Table '.($schema?$schema.'.':'').$tablename);
		}
		if (isset(self::$describeStorage[$db][$defaultScheme][$tablename])){
			if (core::$debug){
				debug::consoleLog('table was described early');
				debug::groupEnd();
			}
			return self::$describeStorage[$db][$defaultScheme][$tablename];
		}
		$primaryKey=array();
		$uniqueKey=array();
		switch(db::type($db)){
			case 'mysql':
				/************************ MYSQL *************************/
				$tablename=strtolower($tablename);
				if ($schema){
					$sql='show create table `'.$schema.'`.`'.$tablename.'`';
				}else{
					$sql='show create table `'.$tablename.'`';
				}
				$rs=db::ea1($sql,'',$db);
				$createtable=$rs['Create Table'];
				$createtable=explode("\n",$createtable);
				if (empty($createtable)) return false;
				foreach($createtable as $string){
					$string=trim($string," \t\n\r\0,");
					if (substr($string,0,13) == 'CREATE TABLE ') continue;
					if (substr($string,0,9) == ') ENGINE ') break;
					if (substr($string,0,13) == 'PRIMARY KEY ('){
						// primary key finder
						$string=substr($string,13,-1);
						$key=explode(',',$string);
						foreach($key as $item){
							$primaryKey[]=input::findFirstPrepare($item);
						}
						continue;
					}
					if (substr($string,0,11) == 'UNIQUE KEY '){
						// primary key finder
						$string=substr($string,11,-1);
						$name=input::findFirstPrepare($string);
						$string=trim($string,' (');
						$key=explode(',',$string);
						foreach($key as $item){
							$uniqueKey[$name][]=input::findFirstPrepare($item);
						}
						continue;
					}
	// parsing to words
					if (substr($string,0,1) == '`'){
						$column=input::findFirstPrepare($string);
						$string=trim($string);
	//							echo $column.' - ';
						if (in_array(substr($string,0,4),array('set(','enum'))) continue; // not working with set and enum (at present)
						$type=substr($string,0,strpos($string,' '));
						$string=substr($string,strlen($type));
						$string=trim($string);
	//							echo $type.' - ';
						$typerangepos=strpos($type,'(');
						if ($typerangepos){
							$type=trim($type,')');
							$data[$column]['typerange']=substr($type,$typerangepos + 1);
							$type=substr($type,0,$typerangepos);
						}
						$data[$column]['type']=$type;

						if (substr($string,0,9) == 'unsigned '){
							$data[$column]['unsigned']=true;
							$string=substr($string,9);
						}
						if (substr($string,0,9) == 'zerofill '){
							$data[$column]['zerofill']=true;
							$string=substr($string,9);
						}
						if (substr($string,0,9) == 'NOT NULL '){
							$data[$column]['notnull']=true;
							$string=substr($string,9);
						}
						if (substr($string,0,14) == 'AUTO_INCREMENT'){
							$data[$column]['autoincrement']=true;
							$string=substr($string,15);
						}

						if (substr($string,0,8) == 'DEFAULT '){
							$string=substr($string,8);
							if (substr($string,0,1) == "'"){
								$data[$column]['default']=input::findFirstPrepare($string);
								$string=trim($string);
							}elseif (substr($string,0,5) == 'NULL '){
								$data[$column]['default']=false;
								$string=substr($string,5);
							}elseif (substr($string,0,18) == 'CURRENT_TIMESTAMP '){
								$data[$column]['default']='CURRENT_TIMESTAMP';
								$string=substr($string,18);
							}
						}
	//echo $default.' - ';
						if (substr($string,0,8) == 'COMMENT '){
							$string=substr($string,8);
							$data[$column]['comment']=input::findFirstPrepare($string);
						}
					}
				}

				break;
			case 'oci8':
			case 'oracle':
				/************************* ORACLE ************************ */
				$tablename=strtoupper($tablename);
				if ($schema){
					$schema=strtoupper($schema);
					$sql="select CONSTRAINT_NAME from ALL_CONS_COLUMNS WHERE owner=:schema and table_name=:tablename and position is not null";
					$pkConstraint=db::ea11($sql,array('tablename'=>$tablename,'schema'=>$schema));
					$sql="select atc.column_name,atc.data_type,data_length,ucc.comments,atc.nullable,atc.data_default from all_tab_columns atc inner join ALL_COL_COMMENTS ucc on atc.table_name=ucc.table_name and atc.column_name=ucc.column_name where atc.table_name = :tablename and atc.OWNER=:schema order by COLUMN_ID";
					$rs=db::ea($sql,array('tablename'=>$tablename,'schema'=>$schema));
					$sql="select ai.INDEX_NAME,COLUMN_NAME from ALL_INDEXES ai left join ALL_IND_COLUMNS aic on AI.INDEX_NAME=AIC.INDEX_NAME WHERE owner=:schema and ai.table_name=:tablename and UNIQUENESS='UNIQUE'";
					$uniqueKey=datawork::group(db::ea($sql,array('tablename'=>$tablename,'schema'=>$schema)),array('INDEX_NAME','[]'),'COLUMN_NAME');
				}else{
					$sql="select CONSTRAINT_NAME from USER_CONS_COLUMNS WHERE table_name=:tablename and position is not null";
					$pkConstraint=db::ea11($sql,array('tablename'=>$tablename));
					$sql="select atc.column_name,atc.data_type,data_length,ucc.comments,atc.nullable,atc.data_default from user_tab_columns atc inner join USER_COL_COMMENTS ucc on atc.table_name=ucc.table_name and atc.column_name=ucc.column_name where atc.table_name = :tablename order by COLUMN_ID";
					$rs=db::ea($sql,array('tablename'=>$tablename));
					$sql="select ai.INDEX_NAME,COLUMN_NAME from USER_INDEXES ai left join USER_IND_COLUMNS aic on AI.INDEX_NAME=AIC.INDEX_NAME WHERE ai.table_name=:tablename and UNIQUENESS='UNIQUE'";
					$uniqueKey=datawork::group(db::ea($sql,array('tablename'=>$tablename)),array('INDEX_NAME','[]'),'COLUMN_NAME');
				}
				if ($pkConstraint){
					$primaryKey=$uniqueKey[$pkConstraint];
					unset($uniqueKey[$pkConstraint]);
				}

				foreach($rs as $item){
					$column=$item['COLUMN_NAME'];
					$data[$column]['type']=$item['DATA_TYPE'];
					$data[$column]['typerange']=$item['DATA_LENGTH'];
					if ($item['COMMENTS']) $data[$column]['comment']=$item['COMMENTS'];
					if ($item['NULLABLE'] == 'N') $data[$column]['notnull']=true;
										if (@$item['DATA_DEFAULT']) $data[$column]['default']=$item['DATA_DEFAULT'];
				}
				break;
			default:
				throw new \Exception('no database type found');
		}
		if (core::$debug){
			debug::dir(array('result'=>array('data'=>$data,'primary_key'=>$primaryKey,'unique_key'=>$uniqueKey)));
			debug::groupEnd();
		}
		return self::$describeStorage[$db][$defaultScheme][$tablename]=array('data'=>$data,'primary_key'=>$primaryKey,'unique_key'=>$uniqueKey);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function set_data($tablename,$array_in='',$sequence='',$db=''){
		return self::setData($tablename,$array_in,$sequence,$db);
	}
	/**
	 * Insert or update $array_in data into $tablename with use sequence $sequence
	 * @param string $tablename
	 * @param array $array_in input array. Keys - fields name. Vals - values. Magic values is {SYSDATE} - current timestamp and {+} - add or concat value with current. For example '{+}100'
	 * @param string $sequence
	 * @param string $db
	 * @return boolean or inserted ID
	 */
	static function setData($tablename,$arrayIn='',$sequence=true,$db='',&$errors=true,$schema=''){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=db::autodb($db);
		$dbType=db::type($db);
		$strcolname=array();
		$bind=array();
		$strvalues=array();

		$arrays=array();
		foreach($arrayIn as $key=> $value){
			if (is_bool($value)){
				$arrays[strtoupper($key)]=(int)$value;
			}elseif ($value instanceof \DateTimeInterface){
				$arrays[strtoupper($key)]=$value->format('d.m.Y H:i:s');
			}elseif ($value instanceof model){
				$arrays[strtoupper($key)]=(string)$value;
			}else{
				$arrays[strtoupper($key)]=$value;
			}
		}
		if (core::$debug){
			debug::group('DBWork set_data start');
			debug::consoleLog('Set data to '.($schema?$schema.'.':'').$tablename);
			debug::dir($arrays);
		}
		$notIsset=false;
		$pkVals=array();
		$sqlPkVals=array();
		$outErrors=array();
	// get description
		$desc=self::describeTable($tablename,$schema,$db);
	// find operation type
		$opType=1; // insert
		foreach ($desc['primary_key'] as $field){
			$upperField=strtoupper($field);
			if (isset($arrays[$upperField])){ // if key value exists
				$pkVals[$upperField]=$arrays[$upperField];
				if (in_array($desc['data'][$field]['type'],array('date','datetime','DATE'))){
					$sqlPkVals[]=db::dateFromDb($field,'Y-m-d H:i:s',$db).'=:'.$upperField;
				}else{
					$sqlPkVals[]=$field.'=:'.$upperField;
				}
			}else{
				if ($notIsset)throw new \Exception('DBWORK: Primary key for '.$field.' is required');
				$notIsset=$field; // first not found key - is autoincrement
				switch ($desc['data'][$field]['type']){
					case 'NUMBER':
					case 'INTEGER':
					case 'FLOAT':
					case 'int':
					case 'float':
					case 'smallint':
					case 'bigint':
					case 'mediumint':
					case 'decimal':
					case 'real':
					case 'tinyint':
					case 'double':
						break;
					default:
					throw new \Exception('DBWORK: Primary key for '.$field.' is required');
				}
			}
		}
		if ($notIsset){
			$strcolname[]=$notIsset;
			if ($sequence === true or $sequence ===''){
				// try find next val of numberic vals
				// if sequence not set - find max value of key + 1
				$sql='SELECT MAX('.$notIsset.') FROM '.($schema?$schema.'.':'').$tablename.($sqlPkVals?' WHERE '.implode(' AND ',$sqlPkVals):'');
				$rs=(int)db::ea11($sql,$pkVals,$db);
				$bind[$notIsset]=$pkVals[$notIsset]=$rs+1;
				$strvalues[]=':'.$notIsset;
				$sqlPkVals[]=$notIsset.' = :'.$notIsset;
			}else{
				$strvalues[]=$sequence.'.nextval';
			}
		}elseif ($desc['primary_key']){
			// check on exists row
			$sql='SELECT COUNT(*) FROM '.($schema?$schema.'.':'').$tablename.' WHERE '.implode(' AND ',$sqlPkVals);
			if (db::ea11($sql,$pkVals,$db))$opType=2; //update
		}
		// if has inuque keys - validate // todo
	//        if ($desc['unique_key']){
	//            if ($opType==1){
	//            }
	//        }

		// check of each field value
		foreach ($desc['data'] as $field=>$fieldVal){
			if ($notIsset==$field) continue;
			$upperField=strtoupper($field);
			$value=@$arrays[$upperField];
			if ($value===db::NEXTVAL){
				$sql='SELECT MAX('.$field.') FROM '.($schema?$schema.'.':'').$tablename;
				$rs=(int)db::ea11($sql,'',$db);
				$arrays[$upperField]=$value=$rs+1;
			}
			$fieldName=(empty($fieldVal['comment']))?$field:$fieldVal['comment'];
			if (empty($fieldVal['notnull']) && is_null($value) && key_exists($upperField,$arrays)){
				$strcolname[]=$field;
				$strvalues[]='NULL';
			}elseif (isset($arrays[$upperField])){
				switch ($fieldVal['type']){
					case 'VARCHAR2':
					case 'CHAR':
					case 'CLOB':
					case 'LONG':
					case 'varchar':
					case 'char':
					case 'text':
					case 'mediumtext':
					if (!empty($fieldVal['notnull']) && $opType==1 &&!isset($fieldVal['default']) && $value===''){
						$outErrors[]=translate::t('Not set required field {field}',array('field'=>$fieldName));
						continue;
					}
					if (!$fieldVal['typerange']){
						if ($fieldVal['type']=='tinytext')$fieldVal['typerange']=256;
						if ($fieldVal['type']=='text')$fieldVal['typerange']=65535;
						if ($fieldVal['type']=='mediumtext')$fieldVal['typerange']=16777215;
						if ($fieldVal['type']=='longtext')$fieldVal['typerange']=4294967295;
					}
					if ((int)$fieldVal['typerange'] < mb_strlen($value,core::$charset) && $fieldVal['type'] != 'CLOB' && $fieldVal['type'] != 'LONG'){
						$outErrors[]=translate::t('Field {field} have max length <b>{max_length}</b> charecters. You length is <b>{my_length}</b> charecters',array('field'=>$fieldName,'max_length'=>$fieldVal['typerange'],'my_length'=>mb_strlen($value)));
						continue;
					}
					$strcolname[]=$field;
					if (strlen($value) > 3 && substr($value,0,3) == '{+}'){
						$strvalues[]='concat('.$field.',:'.$upperField.')';
						$bind[$upperField]=substr($value,3);
					}else{
						$strvalues[]=':'.$upperField;
						$bind[$upperField]=$value;
					}

					break;
					case 'INTEGER':
					case 'int':
					case 'smallint':
					case 'tinyint':
					case 'bigint':
					case 'mediumint':
					if ($value != intval($value)){
						$outErrors[]=translate::t('Field {field} have number format. You try set <b>{value}</b> value',array('field'=>$fieldName,'value'=>input::htmlspecialchars($value)));
						break;
					}
					$strcolname[]=$field;
					if (strlen($value) > 3 && substr($value,0,3) == '{+}'){
						$strvalues[]=$field.'+:'.$upperField;
						$bind[$upperField]=substr($value,3);
					}else{
						$strvalues[]=':'.$upperField;
						$bind[$upperField]=$value;
					}
					break;
					case 'NUMBER':
					case 'FLOAT':
					case 'decimal':
					case 'real':
					case 'float':
					case 'double':
					if ($dbType=='mysql'){
						$arrays[$upperField]=$value=str_replace(',','.',$value);
						$is_float=preg_match('/^-?\d*[\.]?\d+$/',$value);
					}else{ //oracle
						$arrays[$upperField]=$value=str_replace('.',',',$value);
						$is_float=preg_match('/^-?\d*[\,]?\d+$/',$value);
					}
					if ($is_float or $value == ''){
						$strcolname[]=$field;
						$strvalues[]=':'.$upperField;
						$bind[$upperField]=$value;
					}else{
						$outErrors[]=translate::t('Field {field} must be numberic. You set - {value}',array('field'=>$fieldName,'value'=>$value));
					}
					break;

					case 'time': //mysql only
					if ($value == db::NOW){
						$strvalues[]='now()';
					}elseif (empty($fieldVal['notnull']) && empty($value)){
						$strvalues[]='NULL';
					}else{
						$strvalues[]=':'.$upperField;
						$bind[$upperField]=$value;
					}
					$strcolname[]=$upperField;
					break;

					case 'DATE':
					case 'date':
					case 'datetime':
						switch (trim($value)){
							case db::NOW:
								$strcolname[]=$upperField;
								if ($dbType=='mysql'){
									$strvalues[]='now()';
								}else{ //oracle
									$strvalues[]='SYSDATE';
								}
							break;
							case db::INFINITY:
								$strvalues[]=db::dateToDb(':core_infinity','d.m.Y',$db);
								$strcolname[]=$upperField;
								$bind['core_infinity']="01.01.9999";
								break;
							case '':
							case 'NULL':
							case self::NULL:
								if ($fieldVal['notnull']){
									$outErrors[]=translate::t('Not set required field {field}',array('field'=>$fieldName));
									break;
								}
								$strcolname[]=$upperField;
								$strvalues[]='NULL';
							break;
							default: //default date format
								$dat=(is_numeric($value))?$value:strtotime($value);
							if ($dat!==false){
								$strcolname[]=$upperField;
								$strvalues[]=db::dateToDb(':'.$upperField,'d.m.Y H:i:s');
								$bind[$upperField]=date('d.m.Y H:i:s',$dat);
							}else{
								$outErrors[]=translate::t('Field {field} must be set date as "DD.MM.YYYY HH:MI:SS". You set - {value}',array('field'=>$fieldName,'value'=>$value));
							}
						}

					break;
					default:
					if (core::$debug){
						debug::consoleLog('Type '.$fieldVal['type'].' don\'t recognized',error::WARNING);
						debug::dir($fieldVal);
					}
					
				}
			}elseif (!empty($fieldVal['notnull']) && !isset($fieldVal['default']) && $opType == 1 && $notIsset!==$field){
				$outErrors[]=translate::t('Not set required field {field}',array('field'=>$fieldName));
			}
		}

		// query form
		if (core::$debug) debug::consoleLog('State: '.($opType==1?'insert':'update'));
		if ($outErrors){
			if ($errors===true){
				//add errors to error
				foreach ($outErrors as $error){
					error::addError($error);
				}
			}else{
				$errors=$outErrors;
			}
			if (core::$debug){
				debug::consoleLog('Have '.sizeof($outErrors).' errors. Break',error::ERROR);
				debug::dir($outErrors);
				debug::groupEnd();
			}
			return false;
		}
		if ($dbType=='mysql'){
			if ($opType == 1){ // insert
				$sql='INSERT INTO '.($schema?'`'.$schema.'`.':'').'`'.$tablename.'` (`'.implode('`,`',$strcolname).'`) VALUES ('.implode(',',$strvalues).')';
			}elseif ($opType == 2){ //update
				$im=array();
				foreach($strcolname as $key=> $values){
					$im[]=$strcolname[$key].'='.$strvalues[$key];
				}
				$sql='UPDATE '.($schema?'`'.$schema.'`.':'').'`'.$tablename.'` SET '.implode(',',$im).' WHERE '.implode(' AND ',$sqlPkVals);
			}
			db::e($sql,$bind,$db);
			if ($opType == 1){ // insert
				if (isset($inserted)){
					$return=$inserted;
				}elseif (isset($bind[$notIsset])){
					$return=(int)$bind[$notIsset];
				}else{
					$return=db::lastId($db);
				}
			}else{
				if (sizeof($pkVals)==1){
					foreach ($pkVals as $value){
						$return =$value;
						break;
					}
				}else{
					$return=$pkVals;
				}
			}

		}else{ // oracle

			if ($notIsset) $bind['insert_id']='aaaaaaaaaaaaaaa';

			if ($opType == 1){ // insert
				$sql='INSERT INTO '.($schema?$schema.'.':'').$tablename.' ('.implode(',',$strcolname).') VALUES ('.implode(',',$strvalues).') '.($notIsset?'RETURNING '.$notIsset.' INTO :insert_id':'');
			}elseif ($opType == 2){ // update
				$im=array();
				foreach($strcolname as $key=> $values){
					$im[]=$strcolname[$key].'='.$strvalues[$key];
				}
				$sql='UPDATE '.($schema?$schema.'.':'').$tablename.' SET '.implode(',',$im).' WHERE '.implode(' AND ',$sqlPkVals).($notIsset?' RETURNING '.$notIsset.' INTO :insert_id':'');
			}
			db::eRef($sql,$bind,$db);
			if ($notIsset){
				$return=isset($bind['insert_id'])?$bind['insert_id']:true;
				if ($return==='aaaaaaaaaaaaaaa'){
					if (core::$debug){
						debug::consoleLog('Result: '.$return,error::INFO);
						debug::groupEnd();
					}
					throw new \Exception('DBWORK: Insert record error');
				}
			}else{
				$return =true;
				if (sizeof($pkVals)==1){
					foreach ($pkVals as $value){
						$return =$value;
						break;
					}
				}
			}
		}
		if (core::$debug){
			debug::consoleLog('Result: '.$return,error::INFO);
			debug::groupEnd();
		}
		return $return;
	}

	/**
	 * Insert or update $array_in data into $tablename with use sequence $sequence
	 * @param string $tablename
	 * @param array $arrayIn input array. Keys - fields name. Vals - values. Magic values is {NOW} - current timestamp and {+} - add or concat value with current. For example '{+}100'
	 * @param string $sequence
	 * @param string $db
	 * @return boolean or inserted ID
	 */
	static function setDataOrFail($tablename,$arrayIn='',$sequence='',$db=''){
		$errors=array();
		$rs=self::setData($tablename,$arrayIn,$sequence,$db,$errors);
		if ($errors)  throw new \Exception(translate::t('Errors during record').': '.implode(',',$errors));
		return $rs;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function set_mass_data($tablename,$arrayIn='',$parentArrayIn='',$clearbefore=true,$sequence='',$db=''){
		return self::setMassData($tablename,$arrayIn,$parentArrayIn,$clearbefore,$sequence,$db);
	}
	/**
	 * Set mass data to table
	 * @param string $tablename name of table
	 * @param array $arrayIn assocuate array of 2d data
	 * @param array $parentArrayIn associate array of each field
	 * @param boolean $clearbefore clear table before upload data to keys of $parent array_in
	 * @param string $sequence name of sequence
	 * @param string $db connection name
	 * @return array ids of key fields
	 */
	static function setMassData($tablename,$arrayIn='',$parentArrayIn='',$clearbefore=true,$sequence='',$db=''){
		//$tablename=strtoupper($tablename);
		if (core::$debug) debug::group('DBWork SetMassData start');
		if ($clearbefore) self::delData($tablename,$parentArrayIn,$db);
		$out=array();
		foreach($arrayIn as $value){
			$merge=array_merge($value,$parentArrayIn);
			if (core::$debug){
				debug::group('Iteration start');
				debug::consoleLog('Input data merged: ');
				debug::dir($merge);
			}
			// for each element need set data. Get struct once
			$out[]=self::setData($tablename,$merge,$sequence,$db);
			if (core::$debug) debug::groupEnd();
		}
		if (core::$debug) debug::groupEnd();
		return $out;
	}
	/**
	 * Get story of fields before and after
	 * @deprecated since version 3.5 use datawork::difference method
	 * @param string $tablename
	 * @param array $keys
	 * @param boolean $listfields
	 * @return string
	 */
	static function story($tablename,$keys,$listfields=false){
		$bind=array();
		$strbind=array();
		foreach($keys as $key=> $value){
			$strbind[$key]=$key.'=:'.$key;
			$bind[$key]=$value;
		}
		$sql='SELECT * FROM '.$tablename.($keys?' WHERE '.implode(' and ',$strbind):'');
		$rs=db::ea1($sql,$bind);
		$difference=array();
		if ($rs && sizeof(self::$storyData)){
			foreach($rs as $key=> $value){
				if (!isset(self::$storyData[$key])){
					$difference[$key]=array('new'=>$value);
				}elseif ($value != self::$storyData[$key]){
					$difference[$key]=array('old'=>self::$storyData[$key],'new'=>$value);
				}
			}
			self::$storyData=$rs;
			if (!$listfields) return $difference;
			// get difference with array
			$textdif=array();
			foreach($difference as $key=> $value){
				if (isset($listfields[$key])){
					if (is_array($listfields[$key])){
						// if has listfields - get name of difference keys
						$textdif[]=$listfields[$key][$value['new']];
					}else{
						$textdif[]=translate::t('"{field}" changed '.($value['old']?'from "{old}" ':'').'to "{new}"',array('field'=>$listfields[$key],'old'=>$value['old'],'new'=>$value['new']));
					}
				}
			}
			return $textdif;
		}elseif ($rs){
			self::$storyData=$rs;
			if (!$listfields) return 'created'; // record was created
			$textdif=array();
			foreach($rs as $key=> $value){
				if (isset($listfields[$key])){
					if (is_array($listfields[$key])){
						$textdif[]=$listfields[$key][$value['new']];
					}else{
						$textdif[]=$listfields[$key].' - '.$value['new'];
					}
				}
			}
			return $textdif;
		}else{
			return 'none'; // record is missed
		}
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function clear_story($parentArrayIn=array()){
		return self::clearStory($parentArrayIn);
	}
	static function clearStory($parentArrayIn=array()){
		self::$storyData=$parentArrayIn;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function set_story($old,$new,$table,$id,$login='',$historyTable='HISTORY'){
		return self::setStory($old,$new,$table,$id,$login,$historyTable);
	}
	/**
	 * write difference to base with total array in table HISTORY
	 * @deprecated since version 3.4 hardcode
	 */
	static function setStory($old,$new,$table,$id,$login='',$historyTable='HISTORY'){
		// postfix in table is _STORY_DESCRIPTION
		$diff=array();
		$descripions=array();
		foreach($old as $key=> $item){
			if (substr($key,-18) == '_STORY_DESCRIPTION'){
				$descriptions[substr($key,0,-18)]=$item;
				continue;
			}
			if ($new[$key] != $item) $diff[$key]=$item;
		}
		foreach($new as $key=> $item){
			if (substr($key,-18) == '_STORY_DESCRIPTION') continue;
			if ($old[$key] != $item) $diff[$key]=$item;
		}
		foreach($diff as $key=> $item){
			// пишем в историю изменения
			dbwork::setData($historyTable,array(
				'table_name'=>$table,
				'column_name'=>$key,
				'data'=>db::NOW,
				'val'=>$item,
				'ip'=>  request::ip(),
				'login'=>$login,
				'key_field'=>$id,
				'textval'=>isset($descriptions[$key])?$descriptions[$key]:$item,
				'new_textval'=>''
			));
//			$sql='INSERT INTO '.$historyTable.' VALUES (:table_name,:key,now(),:val,:ip,:login,:id,:descr)';
//			db::e($sql,array('table_name'=>$table,'key'=>$key,'val'=>$item,'ip'=>request::ip(),'login'=>$login,'id'=>$id,'descr'=>isset($descriptions[$key])?$descriptions[$key]:$item));
		}
		return (!empty($diff));
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function del_data($tablename,$arrayIn=array(),$db=''){
		return self::delData($tablename,$arrayIn,$db);
	}
	static function delData($tablename,$arrayIn=array(),$db=''){
		if (sizeof($arrayIn) == 0) return false;
		$bind=array();
		$strToDel=array();
		foreach($arrayIn as $key=> $value){
			$bind[$key]=$value;
			$strToDel[]=$key.'=:'.$key;
		}
		$sql='DELETE FROM '.$tablename.' WHERE '.implode(' AND ',$strToDel);
		return db::e($sql,$bind,$db);
	}
}