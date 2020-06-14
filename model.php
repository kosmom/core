<?php
namespace c;

/**
 * Core ORM
 * @author Kosmom <Kosmom.ru>
 */
class model implements \Iterator{
	var $connection;
	var $connectionAlter;
	var $readonly=false;
	var $sequence=true;
	var $primaryField; // primary key PK (or can be set in fields var)
	var $fields=array();

	var $mode='model'; // or row

	private $collectionSource;
	private $storage; // storage before set storage class
	var $pk_value;
	private $collectionAutoGet;
	private $cacheTimeout;

	private function getAutoGet(){
		if (!$this->collectionAutoGet)return $this->collectionAutoGet=$this->get();
		return $this->collectionAutoGet;
	}

	private function calculateCache(){
		return md5(json_encode(array($this->queryWhere,$this->queryBind,$this->queryOrders)));
	}
	private function calculateAggregateCache($aggregate,$parameter){
		return md5(json_encode(array($this->queryWhere,$this->queryBind,$aggregate,$parameter)));
	}
	
	function withCache($seconds=null){
		$this->cacheTimeout=$seconds;
		return $this;
	}
	function next(){
		$this->getAutoGet();
		return $this->collectionAutoGet->next();
	}
	function rewind(){
		$this->getAutoGet();
		return $this->collectionAutoGet->rewind();
	}
	function current(){
		$this->getAutoGet();
		return $this->collectionAutoGet->current();
	}
	function valid(){
		$this->getAutoGet();
		return $this->collectionAutoGet->valid();
	}
	function key(){
		$this->getAutoGet();
		return $this->collectionAutoGet->key();
	}
	
	function __toString() {
		if ($this->pk_value)return (string)$this->pk_value;
	}

	function isRowMode(){
		return ($this->mode!='model');
	}

	function on_before_create(){
	}
	function on_after_create(){
	}
	function on_before_update(){
	}
	function on_after_update(){
	}
	function on_before_save(){
	}
	function on_after_save(){
	}
	function on_before_delete(){
	}
	function on_after_delete(){
	}

//	function __call($name,$arguments){
//		$method=strtoupper(substr($name,0,1)).substr($name,1);
//		// check on scope
//		if (method_exists($this,'scope'.$method)){
//			return call_user_func_array(array($this,'scope'.$method),$arguments);
//		}
//	}
//	static function __callStatic($name,$arguments){
//		return self::getThis()->__call($name,$arguments);
//	}

	function __get($name){
		if ($this->pk_value && $name==$this->getPrimaryField())return $this->calculateValue($this->getPrimaryField(), $this->pk_value,$this);
		//try get data from storage
		if (!$this->pk_value && isset($this->storage[$name]))return $this->storage[$name];
		// if storage has data - get storage
		// try get data from request
		if (!$this->pk_value)return null;
		try{
			return model::getDataOrFail($this,$this->pk_value,$name);
		} catch (\Exception $exc) {
			$this->formData();
			return model::getData($this,$this->pk_value,$name);
		}
	}
	private function formData($exception=true){
		if (model::is_set($this,$this->pk_value))return true;
		// calculate data from pk
		$this->queryOrders=array();
		$this->limitCount=0;
		$this->limitStart=0;
		if ($this->collectionSource){
			//set cache to all elements of collection
			echo $this->getSql();
			die('model get data exception');
		}else{
			$rs=db::ea1($this->getSql(),$this->queryBind,$this->getConnections());
			if (!$rs){
				if ($exception){
					throw new \Exception('not found');
				}else{
					return false;
				}
			}
			model::setData($this,$this->pk_value,$rs);
		}
		return true;
	}
	function __set($name,$value){
		$val=$value;
		if (!empty($this->fields[$name]['readonly']))throw new \Exception('Field is readonly');
		if (isset($this->fields[$name]['set']))$val=call_user_func($this->fields[$name]['set'],$val);
		if ($this->pk_value){
			model::setDataCell($this,$this->pk_value,$name,$val);
		}else{
			$this->storage[$name]=$val;
		}
	}
	function getConnections(){
		if ($this->connection)return $this->connection;
		return core::$data['db'];
	}
	function getConnectionsAlter(){
		if ($this->connectionAlter)return $this->connectionAlter;
		return $this->getConnections();
	}
	function calculateValue($field,$value, $values=array()){
	//if (is_null($value))throw new \Exception('no data in '.$field);
		if (is_null($value))return null;
		if (@!$this->fields[$field]['get'])return $value;
		if (@$this->fields[$field]['getThis'])return call_user_func($this->fields[$field]['get'],$value,$values);
		return call_user_func($this->fields[$field]['get'],$value);
	}
	/**
	* Fill array to model vars
	* @param array $array
	* @return model
	* @throws \Exception
	*/
	function fillOrFail($array){
		if (!is_array($array))  throw new \Exception('Model fill no array data');
		foreach($array as $key=> $value)$this->__set($key,$value);
		return $this;
	}
	/**
	 * Fill array to model vars
	 * @param array $array
	 * @return model
	 */
	function fill($array){
		try{
			return $this->fillOrFail($array);
		}catch(\Exception $exc){
			return $this;
		}
	}

	function __unset($name){
		unset($this->fields[$name]);
	}
	function __isset($name){
		return isset($this->fields[$name]);
	}
	function getTableName(){
		if (isset($this->tableName))return $this->tableName;
		if (isset($this->table))return $this->table;
		return get_called_class();
	}
	function getSchemeName(){
		if (isset($this->scheme))return $this->scheme;
		if (!isset($this->connection))return db::getConnectScheme(core::$data['db']);
		if (is_string($this->connection))return db::getConnectScheme($this->connection);
		foreach ($this->connection as $key=>$conn){
			if (is_bool($key))return $conn;
			return $key;
		}
	}
	function getPrimaryField(){
		if (isset($this->primaryField))return $this->primaryField;
		if (isset($this->fields)){
			foreach ($this->fields as $key=>$field){
				if (isset($field['pk']))return $this->primaryField=$key;
			}
		}
		return $this->primaryField='id';
	}
	function saveOrFail($exception=null){
		try {
			if (core::$data['db_exception'])return $this->save();
			core::$data['db_exception']=true;
			$rs=$this->save();
			core::$data['db_exception']=false;
			return $rs;
		} catch (\Exception $exc) {
			if ($exception){
				throw new \Exception($exception);
			}else{
				throw new \Exception($ex);
			}
		}

		
	}
	function clearCache(){
		unset(model::$cache[get_called_class()]);
	}
	function save(){
		if ($this->readonly)throw new \Exception('Model is readonly');
		// only for row mode
		$mode=$this->mode;
		$this->mode='row';
		$operation='create';
		try {
		if ($this->pk_value && ($this->toObject()->findOrFail($this->pk_value)))$operation='update';
		} catch (Exception $exc) {
		}
		if ($operation=='create'){
			$this->on_before_create();
		}else{
			$this->on_before_update();
		}
		$this->on_before_save();
		$this->mode=$mode;
		$result=$values=$this->pk_value?model::getChanged($this,$this->pk_value):$this->storage;
		foreach ($this->fields as $fieldKey=>$fieldVal){
			if (!@$fieldVal['dbname'])continue;
			if ($values[$fieldKey])$result[$fieldVal['dbname']]=$values[$fieldKey];
		}
		$errors=array();
		if ($result)$this->pk_value=dbwork::setData($this->getTableName(), $result,$this->sequence,$this->getConnectionsAlter(),$errors,$this->getSchemeName());
//model::setData($this,$this->pk_value,$result);
		model::un_set($this,$this->pk_value);
		$this->storage=null;
		if ($this->mode!='row')$this->find($this->pk_value);
		if ($operation=='create'){
			$this->on_after_create();
		}else{
			$this->on_after_update();
		}
		$this->on_after_save();
		return $this;
	}

	/* simple query builder part*/

	private $limitStart=0;
	private $limitCount=0;
	private $queryOrders=array();
	private $queryWhere=array();
	private $queryBind=array();
	private $bindCounter=0;

	/**
	 * Set order to query
	 * @param string $field field name
	 * @param string $order asc/desc
	 * @param int $prior order priority
	 * @param string|boolean $func sql function
	 * @param string|boolead $expression sql raw expression
	 * @return static
	 * @throws \Exception
	 */
	static function orderStatic($field,$order='',$prior=999,$func=false,$expression=false){
		return self::toObject()->order($field,$order,$prior,$func,$expression);
	}
	/**
	 * Set order to query
	 * @param string $field field name
	 * @param string $order asc/desc
	 * @param int $prior order priority
	 * @param string|boolean $func sql function
	 * @param string|boolead $expression sql raw expression
	 * @return static
	 * @throws \Exception
	 */
	function order($field,$order='',$prior=999,$func=false,$expression=false){
		if (!isset($this))return self::toObject()->order($field,$order,$prior,$func,$expression);
		if (!in_array($order,array('','asc','desc')))throw new \Exception('sort order status wrong');
		if ($order=='asc')$order='';
		if ($order=='desc')$order=' desc';
		$this->queryOrders[$prior][]=array('field'=>$field,'order'=>$order,'func'=>$func,'expression'=>$expression);
		return $this;
	}
	function orderBy($field,$order='',$prior=999,$func=false,$expression=false){
		if (!isset($this))return self::toObject()->order($field,$order,$prior,$func,$expression);
		return $this->order($field,$order,$prior,$func,$expression);
	}
	function delete(){
		if (!isset($this))return self::toObject()->delete();
		if ($this->isRowMode()){
			$this->on_before_delete();
			$sql='DELETE from '.$this->getScemeWithTable().$this->getWhereSqlPart();
			$rs=db::e($sql,$this->queryBind,$this->getConnections());
			$this->on_after_delete();
		}else{ //model
			$datas=$this->get();
			foreach ($datas as $data){
				$data->on_before_delete();
			}

			$sql='DELETE from '.$this->getScemeWithTable().$this->getWhereSqlPart();
			$rs=db::e($sql,$this->queryBind,$this->getConnections());

			foreach ($datas as $data){
				$data->on_after_delete();
			}
		}
		return $rs;
	}

	function updateOrCreate($paramsUpdate=array(),$paramsCreate=array()){
		if (!isset($this))return self::toObject()->updateOrCreate($paramsUpdate,$paramsCreate);
		$this->update($paramsUpdate);
		if (db::rows($this->getConnections()))return $this;
		return $this->create($paramsUpdate+$paramsCreate);
	}
	function update($params){
		if (!array($params)) throw new \Exception('Params must be array');
		if (!isset($this))return self::toObject()->update($params);
		$set=array();
		foreach ($params as $field=>$value){
			$set[]=$field.'=:cu_'.$field;
			$this->queryBind['cu_'.$field]=$value;
		}
		$sql='update '.$this->getScemeWithTable().' set '.implode(',',$set).$this->getWhereSqlPart();
		return db::e($sql,$this->queryBind,$this->getConnections());
	}
	private function sqlExpression($where){
		if (@$where['expression']===true){
			return $where['field'].' '.$where['prop'].' '.$where['value'];
		}elseif (@$where['expression']){
			return $where['expression'];
		}else{
			return $where['field'].' '.$where['prop'].' '.$where['value'];
		}
	}
	function whereRaw($sqlPart,$bind=array()){
		if (!isset($this))return self::toObject()->whereRaw($sqlPart,$bind);
		$this->queryWhere[]=array('expression'=>$sqlPart);
		if (is_array($bind))$this->queryBind+=$bind;
		return $this;
	}
	/**
	 * Set filter in raw text and In expression
	 * @param string $sqlPart
	 * @param array $arrayIn
	 * @return $this
	 */
	function whereInRaw($sqlPart,$arrayIn){
		if (!isset($this))return self::toObject()->whereInRaw($sqlPart,$arrayIn);
		$sqlInPart=db::in($this->queryBind,$arrayIn);
		$this->queryWhere[]=array('expression'=>str_replace('(:in)','('.$sqlInPart.')', $sqlPart));
		return $this;
	}
	/**
	 * Set filter as In to query
	 * @param string $field
	 * @param array $value
	 * @return static
	 */
	static function whereInStatic($field,$arrayIn){
		return self::toObject()->whereIn($field,$arrayIn);
	}
	/**
	 * Set filter as In to query
	 * @param string $field
	 * @param array $value
	 * @return static
	 */
	function whereIn($field,$arrayIn){
		if (!isset($this))return self::toObject()->whereIn($field,$arrayIn);
		$this->whereCondition($field,'in',$arrayIn);
		return $this;
	}
	/**
	 * Set filter to query
	 * @param string $field
	 * @param string|array $prop prop or value if prop '='
	 * @param any $value
	 * @return static
	 */
	static function whereStatic($field,$prop=null,$value=null){
		return self::toObject()->where($field,$prop,$value);
	}
	/**
	 * Set filter to query
	 * @param string $field
	 * @param string|array $prop prop or value if prop '='
	 * @param any $value
	 * @return static
	 */
	function where($field,$prop=null,$value=null){
		if (!isset($this))return self::toObject()->where($field,$prop,$value);
		if ($value===null){
			if (is_array($prop)){
				$value=$prop;
				$prop='in';
			}elseif ($prop===null && is_array($field)){
				foreach ($field as $key=>$item){
					$this->whereCondition($key, '=', $item);
				}
				return $this;
			}elseif ($prop!=='is null' && $prop!=='is not null'){
				$value=$prop;
				$prop='=';
			}
		}
		$this->whereCondition($field,$prop,$value);
		return $this;
	}

	/**
	 * @deprecated since version 3.5 use where function
	 */
	function is($field,$value=null){
		if (!isset($this))return self::toObject()->is($field,$value);
		if ($value===null){
			$this->whereCondition($field,'is not null','');
		}else{
			$this->whereCondition($field,'=',$value);
		}
		return $this;
	}
	function whereNull($field){
		return $this->where($field,'is null');
	}
	function whereNotNull($field){
		return $this->where($field,'is not null');
	}
	static function whereNullStatic($field){
		return self::toObject()->where($field,'is null');
	}
	static function whereNotNullStatic($field){
		return self::toObject()->where($field,'is not null');
	}
	function whereColumn($field,$prop=null,$value=null){
		if (!isset($this))return self::toObject()->whereColumn($field,$prop,$value);
		
		if ($value===null){
			if ($prop===null && is_array($field)){
				foreach ($field as $key=>$item){
					$this->whereCondition($key, '=', $item,true);
				}
				return $this;
			}else{
				$value=$prop;
				$prop='=';
			}
		}
		$this->whereCondition($field,$prop,$value,true);
		return $this;
	}
	function firstOrCreate($param = array()){
		if (!isset($this))return self::toObject()->firstOrCreate($param);
		$first=$this->first();
		if (!$first)return $this->create($param);
		return $first;
	}
	function create($param = array()){
		if (!isset($this))return self::toObject()->create($param);
		$new=$this->toObject();
		foreach ($this->queryWhere as $where){
			if ($where['prop']!='=')continue;
			$field=$where['field'];
			$new->$field=$this->queryBind[$where['field']];
		}
		foreach ($param as $field=>$value){
			$new->$field=$value;
		}
		return $new;
	}
	function between($field,$valueFrom,$valueTo){
		if (!isset($this))return self::toObject()->between($field,$valueFrom,$valueTo);
		$this->whereCondition($field,'>',$valueFrom);
		$this->whereCondition($field,'<',$valueTo);
		return $this;
	}
	private function whereCondition($field,$prop,$value,$expression=false){
		if (is_object($value) or is_numeric($value))$value=(string)$value;
		if ($prop=='=<')$prop='<=';
		if ($prop=='=>')$prop='>=';
		if ($prop=='in' && sizeof($value)==1){
			$prop='=';
			$value=$value[0];
		}
		switch ($prop){
			case 'filter_diap':
			case 'filter-diap':
			case 'filterDiap':
			return $this->whereRaw('('.db::filterDiap ($value,$field,$this->queryBind,$field).')');

			case 'in':
			if (!is_array($value))$value=array($value);
			if (empty($value))return $this->queryWhere[]=array('expression'=>'1=0');
			$value='('.db::in($this->queryBind,$value,$field).')';
			break;
			case 'not in':
			if (!is_array($value))$value=array($value);
			if (empty($value))return $this->queryWhere[]=array('expression'=>'1=1');
			$value='('.db::in($this->queryBind,$value,$field).')';
			break;
			case 'is null':
			case 'is not null':
				$value='';
				break;
			default:
				if ($expression){
					$result['expression']=true;
				}else{
					$bindField=$this->bindFind($field,$value);
					$this->queryBind[$bindField]=$value;
					$value=':'.$bindField;
				}
		}
		$field_val=$field;
		$result['prop']=$prop;
		$result['field']=$field_val;
		$result['value']=$value;
		if ($field_val!=$field)$result['expression']=true;
		$this->queryWhere[]=$result;
	}
	private function bindFind($field,$value){
		$field=ltrim($field, '_');
		if (!isset($this->queryBind[$field]))return $field;
		if ($this->queryBind[$field]==$value)return $field;
		return $field.$this->bindCounter++;
	}

	function cursor($callback){
		$name=get_called_class();
		$name=new $name;
		if (!@$this)return $name->cursor($callback);
		// no hash. big data and no storage
		$rs=db::query($this->getSql(),$this->queryBind,$this->getConnections());
		while ($row=db::fa($rs)){
			$name->mode='row';
			$name->fill($row);
			//$name->pk_value=$pk_value;
			call_user_func($callback,$name);
		}
	}
	/**
	 * Get first element of query
	 * @return static
	 */
	function firstOrFail($exception=null){
		if (!isset($this))return self::toObject()->firstOrFail($exception);
		$first=$this->first();
		if (!$first){
			if (is_string($exception))$exception=new \Exception ($exception);
			if ($exception instanceof \Exception) throw $exception;
			throw new \Exception('not found');
		}
		return $first;
	}
	/**
	 * Get first element of query
	 * @return static
	 */
	function first(){
		if (!@$this)return self::toObject()->first();
		return $this->get()->first();
	}
	function last(){
		if (!@$this)return self::toObject()->last();
		return $this->get()->last();
	}

	static function toObject(){
		$name=get_called_class();
		return new $name;
	}

	/**
	 * get multiple rows
	 * @return collection_object
	 */
	static function getStatic(){
		return self::toObject()->get();
	}
	/**
	 * get multiple rows
	 * @return collection_object
	 */
	function get(){
		if (!isset($this))return self::toObject()->get();
		// if has cache - return cache
		$hash=$this->calculateCache();
		$pk=$this->getPrimaryField();
		$is_full=empty($this->queryWhere);
		if (isset(model::$cache[get_called_class()][$hash])){
			return new collection_object(model::$cache[get_called_class()][$hash],get_called_class(),$pk,$is_full);
		}
		// if get from collection - mass request with "in" and group
		if ($this->collectionSource){

			//todo: error case
			//var_dump($item->catalog_trees()->where(catalog_tree::FIELD_IS_MAIN_TREE,1)->first()->tree()->name);
			// foreach (model1 as $model1){
			//var_dump($model1->submodel()->subsubmodel()->get()->toArray());

			//if get with relation
			$tempWhere=$this->queryWhere;
			$tempBind=$this->queryBind;
			$field=$tempWhere[0]['field'];
			array_shift($this->queryWhere);
			array_shift($this->queryBind);
			$collection_elements=$this->collectionSource->keys();
			if (!$this->collectionSource->is_full)$this->where($field, $collection_elements);
			$rs=db::ea($this->getSql(),$this->queryBind,$this->getConnections());
			foreach ($rs as $row){
				model::setData($this,$row[$pk],$row);
			}
			//set hashs for each element of collection
			$this->queryWhere=$tempWhere;
			$this->queryBind=$tempBind;
			$elements=  datawork::group($rs, array($field,'[]'),  $pk);
			foreach ($collection_elements as $element){
				$this->queryBind[$field]=$element;
				model::$cache[get_called_class()][$this->calculateCache()]=isset($elements[$element])?$elements[$element]:array();
			}

		}else{
			//get data for cache
			$rs=db::ea($this->getSql(),$this->queryBind,$this->getConnections(), $this->cacheTimeout);
			foreach ($rs as $row){
				model::setData($this,$row[$pk],$row);
			}
			// set cache
			model::$cache[get_called_class()][$hash]=datawork::group($rs,'[]',$pk);
		}
		return new collection_object(model::$cache[get_called_class()][$hash],get_called_class(),$pk,$is_full);
	}

	private function getWhereSqlPart(){
			if (!$this->queryWhere)return '';
			$wheres=array();
			foreach ($this->queryWhere as $where){
				$wheres[]=$this->sqlExpression($where);
			}
		return ' WHERE '.implode(' AND ',$wheres);
	}
	function count($distinctField=null){
		if (!isset($this))return self::toObject()->count($distinctField);
		return $this->aggregate('count', $distinctField);
	}
	function max($field){
		if (!isset($this))return self::toObject()->max($field);
		return $this->aggregate('max', $field);
	}
	function min($field){
		if (!isset($this))return self::toObject()->min($field);
		return $this->aggregate('min', $field);
	}
	function avg($field){
		if (!isset($this))return self::toObject()->avg($field);
		return $this->aggregate('avg', $field);
	}
	function sum($field){
		if (!isset($this))return self::toObject()->sum($field);
		return $this->aggregate('sum', $field);
	}
	private function aggregate($aggregate,$parameter){
		$this->globalScope();
		$hash=$this->calculateAggregateCache($aggregate, $parameter);
		if (isset(model::$cache[get_called_class()][$hash]))return model::$cache[get_called_class()][$hash];

		if ($this->collectionSource && isset($this->collectionSource->keys()[1])){
			// get whole request with group by expression
			$tempWhere=$this->queryWhere;
			$tempBind=$this->queryBind;
			$field=$tempWhere[0]['field'];
			array_shift($this->queryWhere);
			array_shift($this->queryBind);
			$collection_elements=$this->collectionSource->keys();
			if (!$this->collectionSource->is_full)$this->where($field, $collection_elements);
			$sql='SELECT '.$field.' f,'.$aggregate.'('.($parameter===null?'*':$parameter).') a from '.$this->getScemeWithTable().$this->getWhereSqlPart().' group by '.$field;
			$rs=db::ea($sql,$this->queryBind,$this->getConnections());
			$rs= datawork::group($rs,'f','a');
			$this->queryWhere=$tempWhere;
			$this->queryBind=$tempBind;
			foreach ($collection_elements as $row){
				$this->queryBind[$field]=$row;
				model::$cache[get_called_class()][$this->calculateAggregateCache($aggregate, $parameter)]=isset($rs[$row])?(float)$rs[$row]:false;
			}
			return model::$cache[get_called_class()][$hash];
		}
		$sql='SELECT '.$aggregate.'('.($parameter===null?'*':$parameter).') from '.$this->getScemeWithTable().$this->getWhereSqlPart();
		return model::$cache[get_called_class()][$hash]=(float)db::ea11($sql,$this->queryBind,$this->getConnections(), $this->cacheTimeout);
	}
	function globalScope(){
	}

	function __construct($pkValue=null,$fromCollection=null) {
		if ($fromCollection!==null)$this->collectionSource=$fromCollection;
		if ($pkValue!==null)$this->find($pkValue);
	}
	private function getScemeWithTable(){
		return db::wrapper($this->getSchemeName(),$this->getConnections()) .'.'.db::wrapper($this->getTableName(),$this->getConnections());
	}
	function exists(){
		$sql="select exists(".$this->getSql().") as 'exists' from dual";
		return (bool)db::ea11($sql,$this->queryBind,$this->getConnections());
	}
	function getSql(){
		$this->globalScope();
		$types=array();
		if ($this->fields)foreach ($this->fields as $fieldKey=>$fieldVal){
			if (@$fieldVal['type']=='date'){
				$fieldName=isset($fieldVal['dbname'])?$fieldVal['dbname']:$fieldKey;
				$types[]=db::dateFromDb($fieldName,'Y-m-d',$this->getConnections()).' "'.$fieldKey.'"';
			}elseif (@$fieldVal['type']=='datetime'){
				$fieldName=isset($fieldVal['dbname'])?$fieldVal['dbname']:$fieldKey;
				$types[]=db::dateFromDb($fieldName,'Y-m-d H:i:s',$this->getConnections()).' "'.$fieldKey.'"';
			}elseif (@$fieldVal['dbname']){
				$types[]=$fieldName.' "'.$fieldKey.'"';
			}
		}
		$return="SELECT t.*".($types?','.implode(',',$types):'')." from ".$this->getScemeWithTable().' t'.$this->getWhereSqlPart();

		if ($this->queryOrders){
			$orders=array();
			ksort($this->queryOrders);
			foreach ($this->queryOrders as $prior){
				foreach ($prior as $data){
					$field=$data['expression']?$this->sqlExpression($data):$data['field'];
					$orders[]=($data['func']?$data['func'].'(':'').$field.($data['func']?')':'').$data['order'];
				}
			}
			$return.=' ORDER BY '.implode(',',$orders);
		}
		if ($this->limitCount || $this->limitStart)$return=db::limit($return,$this->limitStart,$this->limitCount,$this->getConnections());
		return $return;
	}

	function limit($start,$count=0){
		if (!isset($this))return self::toObject()->limit($start,$count);
		$this->limitStart=$start;
		$this->limitCount=$count;
		return $this;
	}

	function toArray(){
		if ($this->pk_value){
			$this->formData();
			return model::getRawData($this,$this->pk_value);
		}else{
			$rs=$this->get();
			$out=array();
			foreach ($rs as $r){
				$out[]=$r->toArray();
			}
			return $out;
		}
	}
	/**
	 * create row replicate without primary key
	 * @return static
	 * @throws \Exception
	 */
	function replicate(){
		if (!$this->isRowMode())throw new \Exception('replicate possible only in row mode');
		$name=get_called_class();
		$name=new $name;
		$source=$this->toArray();
		unset($source[$this->getPrimaryField()]);
		$name->fill($source);
		return $name;
	}
	function relationMorphToMany($model,$entity_id_field,$entity_type_field=null){
		return $this->relation($model,$entity_id_field)->where($entity_type_field,'=',get_called_class());
	}
	function relationMorphToOne($model,$entity_id_field,$entity_type_field=null){
		return $this->relationToOne($model,$entity_id_field)->where($entity_type_field,'=',get_called_class());
	}
	function relationInner($model,$foreign_key=null,$local_key=null){
		return $this->relation($model,$foreign_key,$local_key,true);
	}
	function relation($model,$foreign_key=null,$local_key=null,$is_inner=false,$to_one=false){
		// уххх

		if ($foreign_key===null)$foreign_key=$obj->getPrimaryField();
		if ($local_key===null)$local_key=$this->getPrimaryField();

		// get base collection from keys
		$baseCollection=null;
		if ($this->collectionSource){
			$generator=new $this->collectionSource->generator;
			if ($generator->getPrimaryField()==$local_key){
				$baseCollection=$this->collectionSource;
			}else{
				$baseCollection=new collection_object(array_values(datawork::group($this->collectionSource->toArray(),$local_key,$local_key)),$this->collectionSource->generator,$local_key);
			}
		}
		$obj=new $model(null, $baseCollection);

		if ($this->isRowMode()){
			$obj->where($foreign_key,$this->$local_key);
			return $to_one?$obj->first():$obj;
		}else{
			// if full
			if (!empty($this->queryWhere) || $is_inner)$obj->where($foreign_key, array_unique(datawork::group($this->get()->toArray(),'[]',$local_key)));
			// switch to new model with search case
			return $obj;
		}
	}
	/**
	 * Get related row from model
	 * @param string $model model name
	 * @param string $foreignKey key of related model
	 * @param string $localKey key self model
	 * @return model
	 */
	function relationToOne($model,$foreignKey=null,$localKey=null){
		return $this->relation($model,$foreignKey,$localKey,false,true);
	}
	function lister($onPage=10,$page=null){
		if (!isset($this))return self::toObject()->lister($onPage,$page);
		if (is_null($page))$page=(int)$_GET['page'];
		$limitCount=$this->limitCount;
		$limitStart=$this->limitStart;
		$this->limitCount=0;
		$this->limitStart=0;
		$cnt=db::ea11('select count(*) from ('. $this->getSql().') t2',$this->queryBind,$this->getConnections());
		$this->limitCount=$limitCount;
		$this->limitStart=$limitStart;
		return array('count'=>$cnt,
			'count_on_page'=>$onPage,
			'pages'=>($onPage?ceil($cnt / $onPage):1),
			'cur_page'=>$page,
			);
	}

	/**
	 *
	 * @return models_object
	 */
	function toQueryBuilder($alias=false){
		if (!isset($this))return self::toObject()->toQueryBuilder($alias);
		return models::builder(get_called_class(),$alias);
	}
	/**
	 * Find row model by pk
	 * @param string|integer|array $pk_value
	 * @return model
	 */
	function find($pk_value){
		if (!isset($this))return self::toObject()->find($pk_value);
		
		$this->where($this->getPrimaryField(),$pk_value);
		if (!is_array($pk_value)){
			$this->pk_value=$pk_value;
			$this->mode='row';
		}
		return $this;
	}
	/**
	 * Find row model by pk
	 * @param string|integer|array $pk_value
	 * @return model
	 */
	static function findStatic($pk_value){
		return self::toObject()->find($pk_value);
	}
	/**
	 * Find row model by pk. Thrown exception if not found
	 * @param string|integer $pk_value
	 * @param \Exception,null $exception throwable not found exception
	 * @return model
	 */
	static function findOrFailStatic($pk_value,$exception=null){
		return self::toObject()->findOrFail($pk_value,$exception);
	}
	/**
	 * Find row model by pk. Thrown exception if not found
	 * @param string|integer $pk_value
	 * @param \Exception,null $exception throwable not found exception
	 * @return model
	 */
	function findOrFail($pk_value,$exception=null){
		if (!isset($this))return self::toObject()->findOrFail($pk_value,$exception);
		
		$this->find($pk_value);
		if (!$this->formData(false)){
			if ($exception instanceof \Exception) throw $exception;
			throw new \Exception('not found');
		}
		return $this;
	}
	/**
	 * Find row model by pk. Create new instance if not found
	 * @param string|integer $pk_value
	 * @return model
	 */
	static function findOrCreateStatic($pk_value){
		return self::toObject()->findOrCreate($pk_value);
	}
	/**
	 * Find row model by pk. Create new instance if not found
	 * @param string|integer $pk_value
	 * @return model
	 */
	function findOrCreate($pk_value){
		if (!isset($this))return self::toObject()->findOrCreate($pk_value);
		try {
			return $this->findOrFail($pk_value);
		} catch (\Exception $exc) {
			$model=new $this();
			$model->__set($model->getPrimaryField(),$pk_value);
			return $model;
		}
	}
	
	//function select
	//function unselect
	//function toTable
	//function toForm
	//function distinct
	
	/** Storage drive */
	
	/*
	 * Base data of models
	 * model.pk.key=>val
	 */
	static $base=array();
	/*
	 * Changed data of models
	 * model.pk.key=>val
	 */
	static $durty=array();

	/**
	 * Cache of get vals
	 * model.hash.array(ids)
	 */
	static $cache=array();

	static function getChanged($model,$pk){
		$modelName=get_class($model);
		return self::$durty[$modelName][$pk]?self::$durty[$modelName][$pk]+array($model->getPrimaryField()=>$pk):array();
	}
	static function is_set($model,$pk){
		$modelName=get_class($model);
		return (isset(self::$base[$modelName][$pk]));
	}
	static function un_set($model,$pk){
		$modelName=get_class($model);
		unset(self::$base[$modelName][$pk]);
	}
	static function getRawData($model,$pk,$field=null){
		$modelName=is_object($model)?get_class($model):$model;
		if ($field===null)return self::$base[$modelName][$pk];
		return self::$base[$modelName][$pk][$field];
	}
	static function getData($model,$pk,$field=null){
		$modelName=is_object($model)?get_class($model):$model;
		if ($field===null){
			$out=array();
			foreach (self::$base[$modelName][$pk] as $field=>$value){
				$out[$field]=self::getData($model, $pk,$field);
			}
			return $out;
		}
		if (isset(self::$durty[$modelName][$pk][$field]))return self::$durty[$modelName][$pk][$field];
		if (@$model->fields[$field]['get']){
			return self::$durty[$modelName][$pk][$field]=$model->calculateValue($field,self::$base[$modelName][$pk][$field],self::$base[$modelName][$pk]);
		}
		return @self::$durty[$modelName][$pk][$field]=self::$base[$modelName][$pk][$field];
	}
	static function getDataOrFail($model,$pk,$field=null){
		$modelName=is_object($model)?get_class($model):$model;
		if ($field===null){
			$out=array();
			foreach (self::$base[$modelName][$pk] as $field=>$value){
				$out[$field]=self::getDataOrFail($model, $pk,$field);
			}
			return $out;
		}
		if (isset(self::$durty[$modelName][$pk][$field]))return self::$durty[$modelName][$pk][$field];
		if (isset(self::$base[$modelName][$pk][$field])){
			if (@$model->fields[$field]['get']){
				return self::$durty[$modelName][$pk][$field]=$model->calculateValue($field,self::$base[$modelName][$pk][$field],self::$base[$modelName][$pk]);
			}
			return self::$durty[$modelName][$pk][$field]=self::$base[$modelName][$pk][$field];
		}
		throw new \Exception('no data');
	}
	static function setData($model,$pk,$values){
		$modelName=get_class($model);
		self::$base[$modelName][$pk]=$values;
	}
	static function setDataCell($model,$pk,$cell,$value){
		$modelName=get_class($model);
		self::$durty[$modelName][$pk][$cell]=$value;
	}
}