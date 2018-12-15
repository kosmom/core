<?php
namespace c;

/**
 * Core DataBase class
 * @author Kosmom <Kosmom.ru>
 */
class db{
	const SYSDATE='{SYSDATE}';
	const NOW='{SYSDATE}';
	const NEXTVAL='{NEXTVAL}';
	const INFINITY='{INFINITY}';
	const NULL='{NULL}';
	private static $counter=0;
	private static $dbs=array();
	private static $db_config=array();
	private static $hydrators=array();
	private static $lastHydrator=0;

	/**
	* Return full bind array for sql query with autobinds
	* @param string $sql query
	* @param array $bind input bind
	* @return array full bind
	*/
	static function autobind($sql,$bind=array()){
		if ($bind instanceof \SimpleXMLElement)$bind=(array)$bind;
		if (!is_array($bind))$bind=self::bindToArray($sql,$bind);
		// bind to simple type
		foreach ($bind as &$b){
			if ($b instanceof model)$b=(string)$b;
		}
		if (!core::$data['db_autobind'])return $bind;

		foreach (core::$data['db_autobind'] as $key=>$item){
			if (isset($bind[$key]))  continue;
			// todo: :% probably not working
			if (preg_match('/:'.$key.'\b/i',$sql))$bind[$key]=$item;
		}
		return $bind;
	}

	static function wrapper($object,$db=''){
		$db=self::dbPrepare($db);
		return self::$dbs[core::$env][$db]->wrapper($object);
	}
	private static function bindToArray($sql,$bind){
		$matches=array();
		$out=array();
		preg_match_all('/:([\w]*)\b/i',$sql,$matches);
		foreach ($matches[1] as $str_bind){
			if (isset(core::$data['db_autobind'][$str_bind]))continue;
			$out[$str_bind]=$bind;
		}
		return $out;
	}

	/**
	 * Auto search db from array
	 * @param array $db
	 * @return string
	 */
	static function autodb($db){
		if (!is_array($db))return $db;
		if (sizeof($db))return $db[0];
		if (isset(core::$data['db']) && in_array(core::$data['db'],$db) )return core::$data['db'];
		foreach($db as $item){
			if (isset(self::$dbs[core::$env][$item]))return $item;
		}
		foreach($db as $item){
			if (isset(self::$db_config[core::$env][$item]))return $item;
		}
		return $db[0];
	}
	/**
	 * Execute SQL
	 */
	static function e($sql=null,$bind=array(),$db='',$transaction=true){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		if (is_array($sql)){
			if ($transaction)self::beginTransaction($db);
			$out=array();
			foreach ($sql as $key=>$val){
				if (is_array($val) && isset($val['sql'])){
					switch ($val['type']){
						case 'ea1':
							$out[$key]=self::ea1($val['sql'],$bind,$db);
							break;
						case 'ea11':
							$out[$key]=self::ea11($val['sql'],$bind,$db);
							break;
						case 'ec':
							$out[$key]=self::ec($val['sql'],$bind,$db);
							break;
						case 'ea':
							$out[$key]=self::ea($val['sql'],$bind,$db);
						case 'e':
						default:
							$out[$key]=self::e($val['sql'],$bind,$db,false);
					}
				}else{
					$out[$key]=self::e($val,$bind,$db);
				}
			}
			if ($transaction)self::commit($db);
			return $out;
		}else{
			return self::dbOutput(self::$dbs[core::$env][$db]->execute($sql,self::autobind($sql, $bind)));
		}
	}

	static private function dbPrepare($db){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=self::autodb($db);
		if (empty(self::$dbs[core::$env][$db]))self::connect($db);
		return $db;
	}
	static function query($sql=null,$bind=array(), $db=''){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		self::$hydrators[++self::$lastHydrator]=array('link'=>self::$dbs[core::$env][$db]->query($sql,self::autobind($sql, $bind),$db),'db'=>$db);
		return self::$lastHydrator;
	}

	static function beginTransaction($db=''){
		$db=self::dbPrepare($db);
		self::$dbs[core::$env][$db]->beginTransaction();
	}
	static function commit($db=''){
		$db=self::dbPrepare($db);
		self::$dbs[core::$env][$db]->commit();
	}
	static function rollback($db=''){
		$db=self::dbPrepare($db);
		self::$dbs[core::$env][$db]->rollback();
	}

		private static function dbOutput($result,$mode=null){
			if (!$result)return $result;
			if ($mode===null && isset(core::$data['db_output']))$mode=core::$data['db_output'];
			switch ($mode){
				case null:
				case core::DATA_DB_ARRAY:
					return $result;
				case core::DATA_DB_COLLECTON:
					return new collection($result);
				case core::DATA_DB_COLLECTON_OBJECT:
					return new collection_object($result);
				case core::DATA_DB_SPL_FIXED_ARRAY:
					return \SplFixedArray::fromArray($result);
			}
		}
	/**
	 * Fetch assoc hydrator after db::query
	 * @param number|boolean $queryNumber
	 * @return array|false
	 */
	static function fa($queryNumber=false){
		if ($queryNumber===false)$queryNumber=self::$lastHydrator;
		if (empty(self::$hydrators[$queryNumber]))return false;
		return self::$dbs[core::$env][self::$hydrators[$queryNumber]['db']]->fa(self::$hydrators[$queryNumber]['link']);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function e_ref($sql,&$bind,$db=''){
		return self::eRef($sql,$bind,$db);
	}
	/**
	 * Execute SQL with returning reference bind param
	 * @param string $sql SQL query
	 * @param array $bind variable bind
	 * @param string $db database
	 * @return array
	 * @example $sql="update table set link=link where 1=1 returning link into :test";<br>$bind=array('test'=>'max column length');<br>c\db::e_ref($sql,$bind);
	 */
	static function eRef($sql=null,&$bind,$db=''){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		return self::dbOutput(self::$dbs[core::$env][$db]->execute_ref($sql,$bind));
	}

	/**
	 * Execute assoc SQL
	 * @param string $sql
	 * @param array $bind
	 * @param string|null $db
	 * @return collection
	 */
	static function ea($sql=null,$bind=array(), $db=null){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		return self::dbOutput(self::$dbs[core::$env][$db]->execute_assoc($sql,self::autobind($sql, $bind)));
	}

	/**
	 * Execute object assoc SQL
	 * @param string $sql
	 * @param array $bind
	 * @param string|null $db
	 * @return collection|boolean
	 */
	static function eo($sql=null,$bind=array(), $db=''){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		$rs=self::$dbs[core::$env][$db]->execute_assoc($sql,self::autobind($sql, $bind));
		if ($rs)return new collection_object($rs);
		return false;
	}
	/**
	 * Execute assoc 1 row SQL
	 */
	static function ea1($sql=null,$bind=array(),$db=''){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		return self::$dbs[core::$env][$db]->ea1($sql,self::autobind($sql, $bind));
	}
	/**
	 * Execute object 1 row SQL
	 */
	static function eo1($sql=null,$bind=array(),$db=''){
		if ($sql==null)global $sql;
		$db=self::dbPrepare($db);
		return (object)self::$dbs[core::$env][$db]->ea1($sql,self::autobind($sql, $bind));
	}
	/**
	 * Execute assoc 1 cell SQL
	 */
	static function ea11($sql=null,$bind=array(),$db=''){
		$out=self::ea1($sql,$bind,$db);
		if (is_array($out))foreach ($out as $item)return $item;
		return false;
	}
	static function massExecute($sql,$beginSql='begin ',$repeatSql='',$endSql="end;",$bind=array(),$db=''){
		$db=self::dbPrepare($db);
		return self::$dbs[core::$env][$db]->massExecute($sql,$beginSql,$repeatSql,$endSql,$bind);
	}
	/**
	 * Execute column SQL
	 */
	static function ec($sql=null,$bind=array(),$db=''){
		$rs=self::ea($sql,$bind,$db);
		$out=array();
		foreach ($rs as $item){
			foreach($item as $val){
				$out[]=$val;
				break;
			}
		}
		return $out;
	}
	/**
	 * get type of db
	 */
	static function type($db=''){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=self::autodb($db);
		self::getConfig();
		return self::$db_config[core::$env][$db]['type'];
	}

	/**
	 * set limit for sql query
	 */
	static function limit($sql,$from=0,$count=0,$db=''){
		$db=self::dbPrepare($db);
		return self::$dbs[core::$env][$db]->db_limit($sql,$from,$count);
	}

	/**
	 * Add <i>in (...)</i> syntax into sql query with modified $bind variable
	 * @param array $bind
	 * @param array $value input array
	 * @param string $variable using for binds
	 * @return string
	 */
	static function in(&$bind,$value=array(),$variable=null,$wrapper=null){
		if ($variable===null)$variable='temp_bind_'.(self::$counter++).'_';
		$out=array();
		foreach ($value as $key=>$item){
			if (is_callable($wrapper)){
				$out[]= call_user_func($wrapper, ':'.$variable.'_'.$key);
			}else{
				$out[]=':'.$variable.'_'.$key;
			}
			$bind[$variable.'_'.$key]=$item;
		}
		return implode(',',$out);
	}

	private static function getConfig(){
		if (empty(self::$db_config[core::$env])){
			if (file_exists(__DIR__.'/global-config/db'.core::$env.'.php'))require __DIR__.'/global-config/db'.core::$env.'.php';
			if (file_exists('config/db'.core::$env.'.php'))require 'config/db'.core::$env.'.php';
		}
	}
	static function addConnect($connectName,$params){
		if ($params['type']=='oci8')$params['type']='oracle';
		self::$db_config[core::$env][$connectName]=$params;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function set_connect($connectName,$params){
		return self::setConnect($connectName,$params);
	}
	/**
	 * Set manually connect params
	 * @param string $connectName
	 * @param array $params
	 */
	static function setConnect($connectName,$params){
		self::getConfig();
		if ($params['type']=='oci8')$params['type']='oracle';
		self::$db_config[core::$env][$connectName]=$params;
	}
	private static function connect($db){
		self::getConfig();
		$class='db_'.self::$db_config[core::$env][$db]['type'];
		if (!file_exists(__DIR__.'/factory/'.$class.'.php'))throw new \Exception('Connection type of '.$db.' in env '.core::$env.' dont recognized');
                $class='c\\factory\\'.$class;
                self::$dbs[core::$env][$db]=new $class(self::$db_config[core::$env][$db],$db);
		if (!self::$dbs[core::$env][$db]->connect())return false;
	}
	/**
	 * Get last inserted ID
	 */
	static function last($db='',$dop=false){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=self::autodb($db);
		if (empty(self::$dbs[core::$env][$db]))return false;
		return self::$dbs[core::$env][$db]->insertId($dop);
	}

	/**
	 * Get last inserted ID
	 */
	static function lastId($db='',$dop=false){
		return self::last($db,$dop);
	}
	/**
	 * Get number changed rows with last query
	 */
	static function rows($db=''){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=self::autodb($db);
		if (empty(self::$dbs[core::$env][$db]))return false;
		return self::$dbs[core::$env][$db]->rows();
	}

	static function explain($sql,$bind=array(),$db=''){
		if ($db=='')$db=core::$data['db'];
		if (is_array($db))$db=self::autodb($db);
		if (empty(self::$dbs[core::$env][$db]))self::connect($db);
		return self::$dbs[core::$env][$db]->explain($sql,self::autobind($sql, $bind));
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function date_from_db($field,$format='Y-m-d H:i:s',$db=''){
		return self::dateFromDb($field,$format,$db);
	}
	static function dateFromDb($field,$format='Y-m-d H:i:s',$db=''){
		$db=self::dbPrepare($db);
		return self::$dbs[core::$env][$db]->date_from_db($field, $format);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function date_to_db($timestamp=null,$format=null,$db=''){
	   return self::dateToDb($timestamp,$format,$db);
	}
	static function dateToDb($timestamp=null,$format=null,$db=''){
		$db=self::dbPrepare($db);
		if ($format===null && !is_numeric($timestamp))$timestamp=  strtotime($timestamp);
		return self::$dbs[core::$env][$db]->date_to_db($timestamp,$format);
	}

	// functions from dbwork
	static function setData($tablename,$array_in='',$sequence=true,$db='',&$outErrors=true,$schema=''){
		return dbwork::setData($tablename,$array_in,$sequence,$db,$outErrors,$schema);
	}
	static function setDataOrFail($tablename,$arrayIn='',$sequence='',$db='',$schema=''){
		return dbwork::setDataOrFail($tablename,$arrayIn,$sequence,$db,$schema);
	}
	static function filterDiap($string,$field,&$bind,$bindPrefix=null,$blockDelimeter=',',$diapDilimeter='-'){
		return dbwork::filterDiap($string,$field,$bind,$bindPrefix,$blockDelimeter,$diapDilimeter);
	}
	static function describeTable($tablename,$schema='',$db=''){
		return dbwork::describeTable($tablename,$schema,$db);
	}
	static function setMassData($tableName,$arrayIn=array(),$parentArrayIn=array(),$clearBefore=true,$sequence='',$db='',$schema=''){
		return dbwork::setMassData($tableName,$arrayIn,$parentArrayIn,$clearBefore,$sequence,$db,$schema);
	}
	static function getConnectScheme($db=''){
		if ($db=='')$db=core::$data['db'];
		self::getConfig();
		return self::$db_config[core::$env][$db]['name'];
	}
}