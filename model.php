<?php
namespace c;

/**
 * Core ORM
 * @author Kosmom <Kosmom.ru>
 */
class model implements \Iterator{
	var $connection;
	//var $lazy;
	//private $getters=array();
	var $readonly=false;
	var $sequence=true;
	var $primaryField; // primary key PK (or can be set in fields var)
	var $fields;
	var $filters; // global scopes

	var $mode='model'; // or row
	
	private $collectionSource;
	private $storage; // storage before set storage class
	var $pk_value;
	private $collectionAutoGet;
	
	private function getAutoGet(){
		if (!$this->collectionAutoGet)return $this->collectionAutoGet=$this->get();
		return $this->collectionAutoGet;
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

	function on_before_save(){
		
	}
	function on_after_save(){
		
	}
	function on_before_delete(){
		
	}
	function on_after_delete(){
		
	}
	
	
	function __call($name,$arguments){
		$method=strtoupper(substr($name,0,1)).substr($name,1);
		// check on scope
		if (method_exists($this,'scope'.$method)){
			return call_user_func_array(array($this,'scope'.$method),$arguments);
		}
		//$instance=models::builder(get_called_class());
		//return call_user_func_array(array($this,$name),$arguments);
	}
	static function __callStatic($name,$arguments){
		$class=get_called_class();
		$class=new $class;
		return $class->__call($name,$arguments);
	}

	function __get($name){
		if ($this->pk_value && $name==$this->getPrimaryField())return $this->calculateValue($this->getPrimaryField(), $this->pk_value,$this);
		//try get data from storage
		if (!$this->pk_value && $this->storage[$name])return $this->storage[$name];
		// if storage has data - get storage
		// try get data from request
		try{
			return storage::getDataOrFail($this,$this->pk_value,$name);
		} catch (\Exception $exc) {
			$this->formData();
			return storage::getData($this,$this->pk_value,$name);
		}
	}
	private function formData($exception=true){
		if (storage::is_set($this,$this->pk_value))return true;
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
			storage::setData($this,$this->pk_value,$rs);
		}
		return true;
	}
	function __set($name,$value){
		$val=$value;
		if (!empty($this->fields[$name]['readonly']))throw new \Exception('Field is readonly');
		if (isset($this->fields[$name]['set']))$val=call_user_func($this->fields[$name]['set'],$val);
		if ($this->pk_value){
			storage::setDataCell($this,$this->pk_value,$name,$val);
		}else{
			$this->storage[$name]=$val;
		}
	}
	function getConnections(){
		if ($this->connection)return $this->connection;
		return core::$data['db'];
	}
	function calculateValue($field,$value, $values=array()){
	//        if (is_null($value))throw new \Exception('no data in '.$field);
		if (is_null($value))return null;
		if (@!$this->fields[$field]['get'])return $value;
		if (@$this->fields[$field]['getThis'])return call_user_func($this->fields[$field]['get'],$value,$values);
		return call_user_func($this->fields[$field]['get'],$value);
	}
	/**
	* Fill array to model vars
	* @param array $array
	* @return \c\model
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
	 * @return \c\model
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
		if (!isset($this->connection))return core::$data['db'];
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
	function save(){
		if ($this->readonly)throw new \Exception('Model is readonly');
		$this->on_before_save();
		$result=$values=$this->pk_value?storage::getChanged($this,$this->pk_value):$this->storage;
		foreach ($this->fields as $fieldKey=>$fieldVal){
			if (!@$fieldVal['dbname'])continue;
			if ($values[$fieldKey])$result[$fieldVal['dbname']]=$values[$fieldKey];
		}
		$this->pk_value=db::setData($this->getTableName(), $result,$this->sequence,$this->getConnections());
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


	function order($field,$order='',$prior=999,$func=false,$expression=false){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->order($field,$order,$prior,$func,$expression);
		}
		if (!in_array($order,array('','asc','desc')))throw new \Exception('sort order status wrong');
		if ($order=='asc')$order='';
		if ($order=='desc')$order=' desc';
		$this->queryOrders[$prior][]=array('field'=>$field,'order'=>$order,'func'=>$func,'expression'=>$expression);
		return $this;
	}
	function orderBy($field,$order='',$prior=999,$func=false,$expression=false){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->order($field,$order,$prior,$func,$expression);
		}
		return $this->order($field,$order,$prior,$func,$expression);
	}
	function delete(){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->delete();
		}
		$this->on_before_delete();
		$sql="DELETE from ".$this->getSchemeName().'.'.$this->getTableName().$this->getWhereSqlPart();
		$rs=db::e($sql,$this->queryBind,$this->getConnections());
		$this->on_after_delete();
		return $rs;
	}
	//    function getBinds(){
	//		return $this->queryBind;
	//	}

	function update($params){
		if (!array($params)) throw new \Exception('Params must be set');
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->update($params);
		}
		$set=array();
		foreach ($params as $field=>$value){
			$set[]=$field.'=:update_'.$field;
			$this->queryBind['update_'.$field]=$value;
		}
		$sql="update ".$this->getSchemeName().'.'.$this->getTableName().' set '.implode(',',$set).$this->getWhereSqlPart();
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
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->whereRaw($sqlPart,$bind);
		}
		$this->queryWhere[]=array('expression'=>$sqlPart);
		if (is_array($bind))$this->queryBind+=$bind;
		return $this;
	}
	/**
	 * Set filter to query
	 * @param string $field
	 * @param string|any $prop prop or value if prop '='
	 * @param any $value
	 * @return static
	 */
	function where($field,$prop=null,$value=null){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->where($field,$prop,$value);
		}
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
		 * @deprecated since version 3.5
		 */
	function is($field,$value=null){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->is($field,$value);
		}
		if ($value===null){
			$this->whereCondition($field,'is not null','');
		}else{
			$this->whereCondition($field,'=',$value);
		}
		return $this;
	}
	function whereColumn($field,$prop=null,$value=null){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->whereColumn($field,$prop,$value);
		}
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
        function between($field,$valueFrom,$valueTo){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->between($field,$valueFrom,$valueTo);
		}
		$this->whereCondition($field,'>',$valueFrom);
		$this->whereCondition($field,'<',$valueTo);
		return $this;
	}
	private function whereCondition($field,$prop,$value,$expression=false){
		if (is_object($value) or is_numeric($value))$value=(string)$value;
		if ($prop=='=<')$prop='<=';
		if ($prop=='=>')$prop='>=';
		switch ($prop){
			case 'filter_diap':
			case 'filter-diap':
			case 'filterDiap':
			return $this->whereRaw('('.db::filterDiap ($value,$field,$this->queryBind,$field).')');

			case 'in':
			if (!is_array($value))$value=array($value);
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
	function firstOrFail(){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->firstOrFail();
		}
		$first=$this->first();
		if (!$first)throw new \Exception('Element not found');
		return $first;
	}
	/**
	 * Get first element of query
	 * @return static
	 */
	function first(){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->first();
		}
		return $this->get()->first();
	}
	function last(){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->last();
		}
		return $this->get()->last();
	}
	
	/**
	 * get multiple rows
	 * @return collection_object
	 */
	function get(){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->get();
		}
		// if has cache - return cache
		$hash=md5(json_encode(array($this->queryWhere,$this->queryBind)));
	//        var_dump(json_encode(array($this->queryWhere,$this->queryBind)));
	//        var_dump(md5(json_encode(array($this->queryWhere,$this->queryBind))));

		$is_full=empty($this->queryWhere);
		if (isset(storage::$cache[get_called_class()][$hash])){
			return new collection_object(storage::$cache[get_called_class()][$hash],get_called_class(),$this->getPrimaryField(),$is_full);
		}
		// if get from collection - mass request with "in" and group
		if ($this->collectionSource){
			//подмена первого where и bind, возможно костыль, но на первое время сойдет
			$tempWhere=$this->queryWhere;
			$tempBind=$this->queryBind;
			$field=$tempWhere[0]['field'];
			array_shift($this->queryWhere);
			array_shift($this->queryBind);
			$collenction_elments=$this->collectionSource->keys();
			if (!$this->collectionSource->is_full)$this->where($field, $collenction_elments);
			$rs=db::ea($this->getSql(),$this->queryBind,$this->getConnections());
			foreach ($rs as $row){
				storage::setData($this,$row[$this->getPrimaryField()],$row);
			}
			//set hashs for each element of collection
			$this->queryWhere=$tempWhere;
			$this->queryBind=$tempBind;
			$elements=  datawork::group($rs, array($field,'[]'),  $this->getPrimaryField());
			foreach ($collenction_elments as $element){
				$this->queryBind[$field]=$element;
	//                var_dump(json_encode(array($this->queryWhere,$this->queryBind)));
	//                var_dump(md5(json_encode(array($this->queryWhere,$this->queryBind))));
				$temphash=md5(json_encode(array($this->queryWhere,$this->queryBind)));
				storage::$cache[get_called_class()][$temphash]=isset($elements[$element])?$elements[$element]:array();
			}
			
	//            var_dump(storage::$cache);
	//            die();
		}else{
			//get data for cache
			$rs=db::ea($this->getSql(),$this->queryBind,$this->getConnections());
			foreach ($rs as $row){
				storage::setData($this,$row[$this->getPrimaryField()],$row);
			}
			// set cache
			storage::$cache[get_called_class()][$hash]=datawork::group($rs,'[]',$this->getPrimaryField());
		}
		return new collection_object(storage::$cache[get_called_class()][$hash],get_called_class(),$this->getPrimaryField(),$is_full);
	}

	private function getWhereSqlPart(){
//		if ($this->isRowMode()){
//			$wheres[]=$this->getPrimaryField().'='.$this->pk_value;
//		}else{
			if (!$this->queryWhere)return '';
			$wheres=array();
			foreach ($this->queryWhere as $where){
				$wheres[]=$this->sqlExpression($where,false);
			}
//		}
		return ' WHERE '.implode(' AND ',$wheres);
	}

	function count($distinctField=null){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->count($distinctField);
		}
		$this->globalScope();
		$sql="SELECT count(".($distinctField?$distinctField:'*').") from `".$this->getSchemeName().'`.'.$this->getTableName().$this->getWhereSqlPart();
		return (int)db::ea11($sql,$this->queryBind,$this->getConnections());
	}
	function max($field){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->max($field);
		}
		$this->globalScope();
		$sql="SELECT max(".$field.") from `".$this->getSchemeName().'`.'.$this->getTableName().$this->getWhereSqlPart();
		return (float)db::ea11($sql,$this->queryBind,$this->getConnections());
	}
	function min($field){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->min($field);
		}
		$this->globalScope();
		$sql="SELECT min(".$field.") from `".$this->getSchemeName().'`.'.$this->getTableName().$this->getWhereSqlPart();
		return (float)db::ea11($sql,$this->queryBind,$this->getConnections());
	}
	function avg($field){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->avg($field);
		}
		$this->globalScope();
		$sql="SELECT avg(".$field.") from `".$this->getSchemeName().'`.'.$this->getTableName().$this->getWhereSqlPart();
		return (float)db::ea11($sql,$this->queryBind,$this->getConnections());
	}
	function sum($field){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->sum($field);
		}
		$this->globalScope();
		$sql="SELECT sum(".$field.") from `".$this->getSchemeName().'`.'.$this->getTableName().$this->getWhereSqlPart();
		return (float)db::ea11($sql,$this->queryBind,$this->getConnections());
	}

	function globalScope(){

	}

	function __construct($pkValue=null,$fromCollection=null) {
		if ($fromCollection!==null)$this->collectionSource=$fromCollection;
		if ($pkValue!==null)$this->find($pkValue);
	}

	function getSql(){
		$this->globalScope();
		$types=array();
			foreach ($this->fields as $fieldKey=>$fieldVal){
				if (@$fieldVal['type']=='date'){
					$fieldName=@$fieldVal['dbname']?$fieldVal['dbname']:$fieldKey;
					$types[]=db::dateFromDb($fieldName,'Y-m-d',$this->getConnections()).' "'.$fieldKey.'"';
				}elseif (@$fieldVal['type']=='datetime'){
					$fieldName=$fieldVal['dbname']?$fieldVal['dbname']:$fieldKey;
					$types[]=db::dateFromDb($fieldName,'Y-m-d H:i:s',$this->getConnections()).' "'.$fieldKey.'"';
				}elseif (@$fieldVal['dbname']){
					$types[]=$fieldName.' "'.$fieldKey.'"';
				}
			}
			$return="SELECT t.*".($types?','.implode(',',$types):'')." from `".$this->getSchemeName().'`.'.$this->getTableName().' t'.$this->getWhereSqlPart();

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
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->limit($start,$count);
		}
		$this->limitStart=$start;
		$this->limitCount=$count;
		return $this;
	}

	function toArray(){
		if ($this->pk_value){
			$this->formData();
			return storage::getRawData($this,$this->pk_value);
		}else{
			$rs=$this->get();
			$out=array();
			foreach ($rs as $r){
				$out[]=$r->toArray();
			}
			return $out;
		}
	}
	function relationInner($model,$foreign_key=null,$local_key=null){
		return $this->relation($model,$foreign_key,$local_key,true);
	}
	function relation($model,$foreign_key=null,$local_key=null,$is_inner=false){
		// уххх
		$obj=new $model(null, $this->collectionSource);
		if ($foreign_key===null)$foreign_key=$obj->getPrimaryField();
		if ($local_key===null)$local_key=$this->getPrimaryField();
		if ($this->isRowMode()){
			// для состояния строки - new модель с ограничением по ключу
			
			return $obj->is($foreign_key,$this->$local_key);
		}else{
			// if full
//			var_dump($this->get()->toArray());
//			die();
			if (!empty($this->queryWhere) || $is_inner)$obj->where($foreign_key, array_unique(datawork::group($this->get()->toArray(),'[]',$local_key)));
			// Переключаемся на соседнюю модель, поиск по текущей модели
			//var_dump($obj);
			return $obj;
		}
	}

	function lister($onPage=10,$page=null){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->lister($onPage,$page);
		}
		if (is_null($page))$page=(int)$_GET['page'];
		$limitCount=$this->limitCount;
		$limitStart=$this->limitStart;
		$this->limitCount=0;
		$this->limitStart=0;
		$cnt=db::ea11('select count(*) from ('. $this->getSql().')',$this->getBinds(),$this->getConnections());
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
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->toQueryBuilder($alias);
		}
		return models::builder(get_called_class(),$alias);
	}
	/**
	 * Find row model by pk
	 * @param string|integer|array $pk_value
	 * @return model
	 */
	function find($pk_value){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->find($pk_value);
		}
	//        if (isset(models::$models[$this->activeModel]->filters)){
	//            foreach (models::$models[$this->activeModel]->filters as $filter){
	//                $this->where($filter[0],$filter[1],$filter[2]);
	//            }
	//        }
		$this->where($this->getPrimaryField(),$pk_value);
		if (!is_array($pk_value)){
			$this->pk_value=$pk_value;
			$this->mode='row';
		}
		return $this;
	}
	
	/**
	 * Find row model by pk. Thrown exception if not found
	 * @param string|integer $pk_value
	 * @return model
	 */
	function findOrFail($pk_value){
		if (!@$this){
			$name=get_called_class();
			$name=new $name;
			return $name->findOrFail($pk_value);
		}
		$this->where($this->getPrimaryField(),$pk_value);
		$this->pk_value=$pk_value;
		$this->mode='row';
		if (!$this->formData(false))throw new \Exception('not found');
		return $this;
	}
	/**
	 * Find row model by pk. Create new instance if not found
	 * @param string|integer $pk_value
	 * @return model
	 */
	function findOrCreate($pk_value){
		$name=get_called_class();
		if (!@$this){
			$name=new $name;
			return $name->findOrCreate($pk_value);
		}
		$this->is($this->getPrimaryField(),$pk_value);
		$this->pk_value=$pk_value;
		$this->mode='row';
		if ($this->formData(false))return $this;
		return new $name($pk_value);
	}
}