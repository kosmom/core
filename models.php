<?php
namespace c;

/**
 * Models ORM class
 * @author Kosmom <Kosmom.ru>
 */
class models{
	const PK='(PK)';
	static $models;
	static $di;
	/**
	 * @deprecated since version 3.4
	 */
	static function require_model($className){
		return self::requireModel($className);
	}
	static function requireModel($className){
		if (isset(self::$models[$className]))return \false;

	if (\class_exists($className)){
			self::$models[$className]=new $className;
		}else{
			$path=__DIR__.'/models/'.$className.'.php';
			if (!\file_exists($path)){
			if (isset(self::$di[$className]))$path=self::$di[$className];
			if (!\file_exists($path))throw new \Exception('Class "'.$className.'" not exists on '.$path);
			}
			require $path;
			self::$models[$className]=new $className;
		}
	}
	static function query($queryString){
		$temp=new models_object();
		return $temp->query($queryString);
	}
	static function builder($className,$alias=\false){
		$temp=new models_object();
		return $temp->builder($className,$alias);
	}

	static function from($className,$alias=\false){
		$temp=new models_object();
		return $temp->builder($className,$alias);

	}

	static function get($className,$alias=\false){
		$temp=new models_object();
		return $temp->builder($className,$alias);
	}

	}
	class models_object{
	private $groupArray=array(
		'count'=>\true,
		'min'=>\true,
		'max'=>\true,
		'avg'=>\true
	);
	private $limitStart=0;
	private $limitCount=0;
	private $bindCounter=0;
	private $attributesProps=array(); // attributes will transport to each model
	private $possibleConnections;
	private $activeModel;
	private $activeAlias;
	private $queryFields=array();
	private $queryModels=array();
	private $queryJoins=array();
	private $queryOrders=array();
	private $queryWhere=array();
	private $queryGroupMode=\false;
	private $queryOn=array();
	private $queryBind=array();
	private $wasSelect=\false;

	function query($queryString){
		$parts=explode('>',$queryString);
		foreach($parts as $num=>$part){
			// get selected strings if it has
			// where []
			$wheres=array();
			\preg_match_all('|\[(.*)\]|U',$part,$wheres);
			$part=preg_replace('|\[.*\]|U','',$part);


			// select {}
			$selects=array();
			\preg_match_all('|\{(.*)\}|U',$part,$selects);
			$part=preg_replace('|\{.*\}|U','',$part);


			$alias=array();
			\preg_match('|^\w+|',$part,$alias);
			$alias=$alias[0];

			$specific=array();
			\preg_match_all('|([\.\#\:\/\\\\])(\w*)|',$part,$specific);
			$className=\null;
			foreach ($specific[1] as $numberic=>$class){
				switch($class){
					case '.':
					$className=$specific[2][$numberic];
					break;
			}
			}
			if (empty($className))throw new \Exception('Class Name not set in \''.$part.'\'');

			//action
			if (!$this->queryJoins){
				$this->builder($className,$alias?$alias:\false);
			}else{
				$this->join($className,$alias);
			}
			//selects fields
			foreach ($selects[1] as $select){
				$this->select(explode(',',$select));
			}
			foreach($wheres[1] as $where){
				$whereparts=array();
				\preg_match('|^(\w+)([\=])(.*)|',$where,$whereparts);
				if (empty($whereparts[1])){
					throw new \Exception('relation where part not recognize in \''.$where.'\'');
				}else{
					////////////////////////////////
					if (substr($whereparts[3],-3)=='(+)'){
						$this->on($whereparts[1],$whereparts[2],substr($whereparts[3],0,-3));
					}else{
						$this->where($whereparts[1],$whereparts[2],$whereparts[3]);
					}
				}
			}
			foreach ($specific[1] as $numberic=>$class){
				switch ($class){
					// where PK = #
					case '#':
					if (is_numeric($specific[2][$numberic])){
						$this->where(models::$models[$className]->getPrimaryField(),'=',$specific[2][$numberic]);
					}else{
						models::$models[$className]->$specific[2][$numberic]($this);
					}
					break;
					// order by
					case '/':
						$this->order($specific[2][$numberic]?$specific[2][$numberic]:models::$models[$className]->getPrimaryField());
						break;
					case '\\':
						$this->order($specific[2][$numberic]?$specific[2][$numberic]:models::$models[$className]->getPrimaryField(),'desc');
				}
			}
		}
		return $this;
	}
	private function importConnectios($className){
		if (isset(models::$models[$className]->connection)){
			$connections=models::$models[$className]->connection;
		}else{
			$connections=models::$models[$className]->getSchemeName();
		}
		if (\is_array($connections)){
			$out=array();
			foreach ($connections as $cKey=>$cVal){
				if (\is_numeric($cKey) or \is_bool($cKey)){
					$out[$cVal]=\true;
				}elseif (\is_numeric($cVal) or \is_bool($cVal)){
					$out[$cKey]=\true;
				}else{
					throw new \Exception('Cant import possible connections');
				}
			}
			return $out;
		}elseif (is_string($connections)){
			return array($connections=>\true);
		}
	}
	private function addPossibleConnections($className){
		$this->possibleConnections=$this->importConnectios($className);
	}
	private function intersectPossibleConnections($className){
		$this->possibleConnections=\array_intersect_key($this->possibleConnections,$this->importConnectios($className));
	}
	function builder($className,$alias=\false){
		if (!$alias)$alias=$className;
		models::requireModel($className);
		$this->addPossibleConnections($className);
		if (\method_exists(models::$models[$className],'construct'))models::$models[$className]->construct($this);
		$this->activeModel=$className;
				$this->activeAlias=$alias;
		$this->queryJoins=array($alias=>array('class'=>$className));
		$this->queryModels=array($className=>\true);
		return $this;
	}

	function join($className,$alias=\false,$withAlias=\false){
		if (!$alias)$alias=$className;
		models::requireModel($className);
		$this->queryModels[$className]=$alias;
		$this->intersectPossibleConnections($className);
		foreach ($this->possibleConnections as $key=>$true){
			if (empty($this->possibleConnections[$key]))unset($this->possibleConnections[$key]);
		}
		if (empty($this->possibleConnections))throw new \Exception('no possible connection');
		// find join class
		if (\method_exists(models::$models[$className],'construct'))models::$models[$className]->construct($this);
		$found=\false;
		if ($withAlias){
			if (empty($this->queryJoins[$withAlias]))throw new \Exception('alias '.$alias.' not exists');
			//var_dump($this->queryJoins);
			// if set with class is alias
			foreach (models::$models[$className]->joins as $toClassName=>$props){
				if ($toClassName!=$this->queryJoins[$withAlias]['class'])  continue;
				if (is_string($props))$props=array('field'=>$props);
				// in current models has connection parameters - field from - hou set. field to - key
				$fieldFrom=$props['field'];
				$fieldTo=models::$models[$toClassName]->getPrimaryField();
				$found=\true;
				break;
			}
			foreach (models::$models[$this->queryJoins[$withAlias]['class']]->joins as $toClassName=>$props){
				if ($toClassName!=$className) continue;
				if (is_string($props))$props=array('field'=>$props);
				// in current models has connection parameters - field from - hou set. field to - key
				$fieldTo=$props['field'];
				$fieldFrom=models::$models[$toClassName]->getPrimaryField();
				$found=\true;
				break;
			}
		}else{
			// all connects for modules equal with current set
			foreach (models::$models[$className]->joins as $toClassName=>$props){
				if (!isset($this->queryModels[$toClassName]))  continue;
				if (\is_string($props))$props=array('field'=>$props);
				// link found. get fields
				$withAlias=$toClassName;
				// in current models has connection parameters - field from - hou set. field to - key
				$fieldFrom=$props['field'];
				$fieldTo=models::$models[$toClassName]->getPrimaryField();
				$found=\true;
				break;
			}
			if (!$found){

				// check used models with has if its has chains
				foreach ($this->queryJoins as $al=>$prop){
					foreach (models::$models[$prop['class']]->joins as $toClassName=>$props){
						if (!isset($this->queryModels[$toClassName]))  continue;
						if (\is_string($props))$props=array('field'=>$props);
						// link found.get fields
						$withAlias=$al;
						$fieldFrom=models::$models[$toClassName]->getPrimaryField();
						$fieldTo=$props['field'];
						$found=\true;
						break(2);
					}
				}
			}

			// find models linked to write phrase in var
		}
		if (!$found)throw new \Exception('models no linked');
		$this->queryJoins[$alias]=array('class'=>$className,'withAlias'=>$withAlias,'fieldTo'=>$fieldTo,'fieldFrom'=>$fieldFrom);
		$this->activeModel=$className;
		$this->activeAlias=$alias;
		return $this;
	}
	function order($field,$order='',$prior=999,$alias=\null,$func=\false,$expression=\false){
		if (!$alias)$alias=$this->activeAlias;
		if (!\in_array($order,array('','asc','desc')))  throw new \Exception('sort order status wrong');
		if ($order=='asc')$order='';
		if ($order=='desc')$order=' desc';
		$this->queryOrders[$prior][]=array('alias'=>$alias,'field'=>$field,'order'=>$order,'func'=>$func,'expression'=>$expression);
		return $this;
	}
	function orderBy($field,$order='',$prior=999,$alias=\null,$func=\false,$expression=\false){
		return $this->order($field,$order,$prior,$alias,$func,$expression);
	}
	function unselect($as,$alias=\null){
		if (!\is_array($as))$as=array($as);
		\array_walk($as,function(&$item){
			$item='-'.$item;
		});
		return $this->select($as,$alias);
	}
	function select($raw_fields,$tableAlias=\null){
		if (!$tableAlias)$tableAlias=$this->activeAlias;
		if (\is_string($raw_fields))$raw_fields=array($raw_fields);
		$fields=array();
		foreach ($raw_fields as $field){
			if ($field=='*'){
				$this->wasSelect=\true;
				$rs=\array_keys(models::$models[$this->queryJoins[$tableAlias]['class']]->fields);
				foreach ($rs as $item){
					$as=$item;
					if (isset(models::$models[$this->queryJoins[$tableAlias]['class']]->fields[$item]['as']))$as=models::$models[$this->queryJoins[$tableAlias]['class']]->fields[$item]['as'];
					$fields[$item]= $as;
				}
			}elseif (\substr($field,0,1)=='-'){
				if (!$this->wasSelect)  $this->select("*");
				$as=\substr($field,1);
				$fields=\array_filter($fields,function($row)use($as){
					if ($row==$as)return \false;
					return \true;
				});
				unset($this->queryFields[$as]);
			}else{
				$this->wasSelect=\true;
				$as=$field;
				if (isset(models::$models[$this->queryJoins[$tableAlias]['class']]->fields[$field]['as']))$as=models::$models[$this->queryJoins[$tableAlias]['class']]->fields[$field]['as'];
				$fields[$field]=$as;
			}
		}
		if (empty($this->queryJoins[$tableAlias]))throw new \Exception('alias '.$tableAlias.' not exists');
		$modelFieldGroups=models::$models[$this->queryJoins[$tableAlias]['class']]->field_group;
		$modelFields=models::$models[$this->queryJoins[$tableAlias]['class']]->fields;
		$modelExpressions=models::$models[$this->queryJoins[$tableAlias]['class']]->expressions;
		foreach ($fields as $field=>$as){
			$func=\false;
			$last=\substr($as,-1,1);
			if ($last=='/' or $last=='\\')$as=trim(substr($as,0,-1));
			if (\is_numeric($field))$field=$as;
			$pos=\strpos($as,' ');
			if ($pos!==\false){
				$field=\substr($as,0,$pos);
				$as=\substr($as,$pos+1);
				$func=array();
				\preg_match('|^(\w+)\((.*)\)|',$field,$func);
				if ($func){
					$field=$func[2];
					$func=\strtolower($func[1]);
					if (!$this->queryGroupMode && isset($this->groupArray[$func]))$this->queryGroupMode=\true;
				}
			}
			if (isset($modelFieldGroups[$field])){
				$final_fields=$modelFieldGroups[$field];
				$as=\false;
			}else{
				$final_fields=array($field);
			}
			foreach ($final_fields as $final_field){
	//$final_field=strtolower($final_field);
	//				echo $final_field;
				if (!$func && !isset($modelFields[$final_field]) && !isset($modelExpressions[$final_field]) )throw new \Exception('field "'.$final_field.'" not exist in "'.$this->queryJoins[$tableAlias]['class'].'" model');
				if ($final_field=='*'){
					$format='number';
				}else{
					$format=$modelFields[$final_field]['type'];
				}
				$fill=array('format'=>$format,'func'=>$func,'class'=>$this->queryJoins[$tableAlias]['class'],'alias'=>$tableAlias,'expression'=>\false);
				if (isset($modelExpressions[$final_field])){
					$fill['expression']=\true;
					$fill['field']=$modelExpressions[$final_field];
				}else{
					$fill['field']=isset($modelFields[$final_field]['dbname'])?$modelFields[$final_field]['dbname']:$final_field;
				}

				$this->queryFields[$as===\false?$final_field:$as]=$fill;
				if ($last=='/' or $last=='\\')$this->order($this->expressions($field,$tableAlias), $last=='/'?'asc':'desc',999,$tableAlias,$func,$fill['expression']);
			}
		}
		return $this;
	}

	/**
	 * use formatPrepare
	 * @deprecated since version 3.4
	 */
	function format_prepare($format,$value){
		return $this->formatPrepare($format,$value);
	}
	function formatPrepare($format,$value){
		switch($format){
			case 'date':
				return db::dateFromDb($value,'d.m.Y',$this->getConnections());
			case 'datetime':
				return db::dateFromDb($value,'d.m.Y H:i:s',$this->getConnections());
		}
		return $value;
	}
	private function sqlExpression($where,$need_write_alias=\null){
		if ($where['expression']===\true){
			return \str_replace('{{alias}}',($need_write_alias?$where['alias'].'.':''),$where['field']).' '.$where['prop'].' '.$where['value'];
		}elseif ($where['expression']){
			return $where['expression'];
		}else{
			return ($need_write_alias?$where['alias'].'.':'').$where['field'].' '.$where['prop'].' '.$where['value'];
		}
	}
	function getForm(){
		return new form($this->getFields());
	}
	function toForm(){
		return new form($this->getFields());
	}
	function getFields(){
		if (empty($this->queryFields))$this->select('*');
		$need_write_alias=(\sizeof($this->queryJoins)>1);
		$out=array();
		foreach ($this->queryFields as $as =>$prop){
			$out[$as]=models::$models[$prop['class']]->fields[$as];
			if ($need_write_alias)$out[$as]['name']=array($prop['alias'],$prop['field']);
		}
		return $out;
	}
	function delete(){
		$sql="DELETE from ".models::$models[$this->activeAlias]->getSchemeName().'.'.models::$models[$this->activeAlias]->getTableName();
		if ($this->queryWhere){
			$wheres=array();
			foreach ($this->queryWhere as $where){
				$wheres[]=$this->sqlExpression($where,\false);
			}
			$sql.=' WHERE '.\implode(' AND ',$wheres);
		}
		return db::e($sql,$this->getBinds(),$this->getConnections());
	}
	function getSql(){
		if (empty($this->queryFields))$this->select('*');
		$needWriteAlias=(sizeof($this->queryJoins)>1);
		$select=array();
		foreach ($this->queryFields as $as =>$prop){
			if (models::$models[$prop['class']]->fields[$as]['nosql'])continue;
			if ($prop['expression']){
				$prepare=$this->sqlExpression($prop,$needWriteAlias);
			}else{
				$prepare=$this->formatPrepare($prop['format'],($needWriteAlias && $prop['alias']?$prop['alias'].'.':'').$prop['field']);
			}
			$select[]=($prop['func']?$prop['func'].'(':'').$prepare.($prop['func']?')':'').($prepare==$as && !$prop['func']?'':' "'.ltrim($as,'_').'"');
		}
		if (empty($select))$select=array('*');
		$joins=array();
		foreach ($this->queryJoins as $alias=>$prop){
			if (!$joins){
				$joins[]=models::$models[$prop['class']]->getSchemeName().'.'.models::$models[$prop['class']]->getTableName().($needWriteAlias?' '.$alias:'');
				continue;
			}
			$on=array();
			if ($this->queryOn[$alias]){
				foreach ($this->queryOn[$alias] as $where){
					$on[]=$this->sqlExpression($where,1);
				}
				$on=\implode(' AND ',$on);
			}
			$joins[]='left join '.models::$models[$prop['class']]->getSchemeName().'.'.models::$models[$prop['class']]->getTableName().' '.$alias.' on '.$alias.'.'.$prop['fieldFrom']. ' = '.$prop['withAlias'].'.'.$prop['fieldTo'].($on?' AND '.$on:'');
		}
		//print_r($this->query_joins);
		$return='select '.\implode(',',$select).' from '.\implode(' ',$joins);

		if ($this->queryWhere){
			$wheres=array();
			foreach ($this->queryWhere as $where){
				$wheres[]=$this->sqlExpression($where,$needWriteAlias);
			}
			$return.=' WHERE '.\implode(' AND ',$wheres);
		}
		if ($this->queryGroupMode){
			$groups=array();
			foreach ($this->queryFields as $as =>$prop){
				if ($prop['func'] && isset($this->groupArray[$prop['func']]))continue;
				$prepare=$this->formatPrepare($prop['format'],$prop['field']);
				$groups[]=($prop['func']?$prop['func'].'(':'').($needWriteAlias?$prop['alias'].'.':'').$prepare.($prop['func']?')':'');
			}
			if ($groups)$return.=' GROUP BY '.implode(',',$groups);
		}
		if ($this->queryOrders){
			$orders=array();
			\ksort($this->queryOrders);
			foreach ($this->queryOrders as $prior){
				foreach ($prior as $data){
					$field=$data['expression']?$this->sqlExpression($data,$needWriteAlias):($needWriteAlias?$data['alias'].'.':'').$data['field'];
					$orders[]=($data['func']?$data['func'].'(':'').$field.($data['func']?')':'').$data['order'];
				}
			}
			$return.=' ORDER BY '.\implode(',',$orders);
		}
		if ($this->limitCount || $this->limitStart)$return=db::limit($return,$this->limitStart,$this->limitCount,$this->getConnections());
		return $return;
	}
	/**
	 * Update request
	 * @param array $params
	 * @return boolean success of result
	 * @throws Exception
	 */
	function update($params){
		if (\sizeof($this->queryJoins)>1)  throw new \Exception('Query with joins cannot be updated');
		if (empty($params))  throw new \Exception('Params must be set');
		$set=array();
		foreach ($params as $field=>$value){
			$set[]=$this->expressions($field,$this->activeAlias).'=:update_'.$field;
			$this->queryBind['update_'.$field]=$value;
		}
		$sql="update ".models::$models[$this->activeAlias]->getSchemeName().'.'.models::$models[$this->activeAlias]->getTableName().' set '.\implode(',',$set);
		if ($this->queryWhere){
			$wheres=array();
			foreach ($this->queryWhere as $where){
				$wheres[]=$this->sqlExpression($where);
			}
			$sql.=' WHERE '.\implode(' AND ',$wheres);
		}
		return db::e($sql,$this->getBinds(),$this->getConnections());
	}
	function whereRaw($sqlPart,$bind=array()){
		$this->queryWhere[]=array('expression'=>$sqlPart);
		if (is_array($bind))$this->queryBind+=$bind;
		return $this;
	}
	function where($field,$prop,$value=\null,$alias=\null){
		$this->whereCondition($field,$prop,$value,$alias);
		return $this;
	}
	function is($field,$value=\null,$alias=\null){
			if ($value===\null){
				$this->whereCondition($field,'is not null','',$alias);
			}else{
				$this->whereCondition($field,'=',$value,$alias);
			}
			return $this;
	}
	function between($field,$valueFrom,$valueTo,$alias=\null){
		$this->whereCondition($field,'>',$valueFrom,$alias);
		$this->whereCondition($field,'<',$valueTo,$alias);
		return $this;
	}
	function on($field,$prop,$value=\null){
		$this->whereCondition($field,$prop,$value,$alias,\true);
		return $this;
	}
	private function bindFind($field,$value){
		$field=\ltrim($field, '_');
		if (!isset($this->queryBind[$field]))return $field;
		if ($this->queryBind[$field]==$value)return $field;
		return $field.$this->bindCounter++;
	}
	private function expressions($param,$alias){
		if (isset(models::$models[$this->queryJoins[$alias]['class']]->expressions[$param]))return models::$models[$this->queryJoins[$alias]['class']]->expressions[$param];
		if (isset(models::$models[$this->queryJoins[$alias]['class']]->fields[$param]['dbname']))return models::$models[$this->queryJoins[$alias]['class']]->fields[$param]['dbname'];
		return $param;
	}
	private function whereCondition($field,$prop,$value,$alias=\null,$on=\false){
		if (\is_object($value))$value=(string)$value;
		if (!$alias)$alias=$this->activeAlias;
		if ($prop=='=<')$prop='<=';
		if ($prop=='=>')$prop='>=';
		$quotes=array('"'=>\true,"'"=>\true);
		switch ($prop){
			case 'filter_diap':
			case 'filter-diap':
			case 'filterDiap':
				return $this->whereRaw('('.db::filterDiap ($value,$this->expressions($field,$alias),$this->queryBind,$alias.'_'.$field).')');

			case 'in':
				if (!\is_array($value))$value=array($value);
				$value='('.db::in($this->queryBind,$value,$alias.'_'.$field).')';
				$prop=' in';
				break;
			case 'is null':
			case 'is not null':
				$value='';
				break;
			default:

				if ($quotes[\substr($value,0,1)] && $quotes[\substr($value,-1)]){
					$bindField=$this->bindFind($field,\substr($value,1,-1));
					$this->queryBind[$bindField]=\substr($value,1,-1);
				}else{
					$bindField=$this->bindFind($field,$value);
					$this->queryBind[$bindField]=$value;
				}
				$value=':'.$bindField;
		}
		$field_val=$this->expressions($field,$alias);
		$result=array('alias'=>$alias,'prop'=>$prop,'field'=>$field_val,'value'=>$value);
		if ($field_val!=$field)$result['expression']=\true;
		if ($on){
			$this->queryOn[$alias][]=$result;
		}else{
			$this->queryWhere[]=$result;
		}
	}
	function limit($start,$count=0){
		$this->limitStart=$start;
		$this->limitCount=$count;
		return $this;
	}
	function getConnections(){
		return \array_keys($this->possibleConnections);
	}
	function getBinds(){
		return $this->queryBind;
	}
	/**
	 *
	 * @param array $bind
	 * @return collection
	 */
	function ea($bind=array()){
		return db::ea($this->getSql(),array_merge($this->getBinds(),$bind),$this->getConnections());
	}


	private function getFieldAttributes(){
		if (!$this->queryFields)$this->select ('*');
		$fieldAttributes=$this->queryFields;
		foreach ($fieldAttributes as $fieldKey=>&$field){
			$field['table']=models::$models[$field['class']]->getTableName();
			if (isset(models::$models[$field['class']]->fields[$fieldKey])){
				$field['field_prop']=models::$models[$field['class']]->fields[$fieldKey];
			}
			if (models::$models[$field['class']]->getPrimaryField()==$fieldKey)$field['field_prop']['pk']=\true;
		}
		return $fieldAttributes;
	}


	/**
	 *
	 * @param array $bind
	 * @return collection_object
	 */
	function get($bind=array()){
		if (\is_string($bind) || \is_numeric($bind))return $this->find($bind);
		$eo=db::eo($this->getSql(),\array_merge($this->getBinds(),$bind),$this->getConnections());
		if  (!$eo) $eo=new collection_object(array());
		$eo->fieldAttributes=  $this->getFieldAttributes();
		foreach ($this->possibleConnections as $connect=>$t)break;
		$eo->connection=$connect;
		return $eo;
	}

	 /**
	 *
	 * @param string|integer $pk_value
	 * @return model
	 */
	function find($pk_value){
		if (isset(models::$models[$this->activeModel]->filters)){
			foreach (models::$models[$this->activeModel]->filters as $filter){
				$this->where($filter[0],$filter[1],$filter[2]);
			}
		}
		$this->is(models::$models[$this->activeModel]->getPrimaryField(),$pk_value);
		foreach ($this->possibleConnections as $connect=>$t)break;
		$model= new model(array(models::$models[$this->activeModel]->getPrimaryField()=>$pk_value), $this->getFieldAttributes(), $connect,\false);
		$model->lazy=array($this->getSql(),$this->getBinds(),$this->getConnections());
		return $model;
	}
	 /**
	 *
	 * @param string|integer $pk_value
	 * @return model
	 */
	function findOrFail($pk_value){
		if (isset(models::$models[$this->activeModel]->filters)){
			foreach (models::$models[$this->activeModel]->filters as $filter){
				$this->where($filter[0],$filter[1],$filter[2]);
			}
		}
		$this->is(models::$models[$this->activeModel]->getPrimaryField(),$pk_value);
		foreach ($this->possibleConnections as $connect=>$t)break;
		$data=$this->ea1();
		if (!$data)throw new \Exception('no data found');
		return new model($data, $this->getFieldAttributes(), $connect,\false);
	}

	/**
	 *
	 * @param type $bind
	 * @return collection_object
	 */
	function eo($bind=array()){
		return db::eo($this->getSql(),array_merge($this->getBinds(),$bind),$this->getConnections());
	}
	function ea1($bind=array()){
		return db::ea1($this->getSql(),array_merge($this->getBinds(),$bind),$this->getConnections());
	}
	function ea11($bind=array()){
		return db::ea11($this->getSql(),array_merge($this->getBinds(),$bind),$this->getConnections());
	}
	function ec($bind=array()){
		return db::ec($this->getSql(),array_merge($this->getBinds(),$bind),$this->getConnections());
	}
	function eq($bind=array()){
		return db::query($this->getSql(),array_merge($this->getBinds(),$bind),$this->getConnections());
	}
	//function getFields(){
		// todo: make total field description as in class for sql builder
	//}
	function lister($onPage=10,$page=\null){
		if (\is_null($page))$page=(int)$_GET['page'];
		$limitCount=$this->limitCount;
		$limitStart=$this->limitStart;
		$this->limitCount=0;
		$this->limitStart=0;
		$cnt=db::ea11('select count(*) from ('. $this->getSql().')',$this->getBinds(),$this->getConnections());
		$this->limitCount=$limitCount;
		$this->limitStart=$limitStart;
		return array('count'=>$cnt,
			'count_on_page'=>$onPage,
			'pages'=>($onPage?\ceil($cnt / $onPage):1),
			'cur_page'=>$page,
			);
	}
	function count($distinctField=\null){
		if ($distinctField===\null){
			$this->queryFields=array('count'=>array('func'=>'count', 'expression'=>'*','alias'=>'count'));
		}else{
			$this->queryFields=array('count'=>array('func'=>'count', 'expression'=>'distinct '.$distinctField,'as'=>'count'));
		}
		return (int)$this->ea11();
	}
	function max($field){
		$this->queryFields=array('max'=>array('func'=>'max', 'expression'=>$field,'alias'=>'max'));
		return (float)$this->ea11();
	}
	function min($field){
		$this->queryFields=array('min'=>array('func'=>'min', 'expression'=>$field,'alias'=>'min'));
		return (float)$this->ea11();
	}
	function avg($field){
		$this->queryFields=array('avg'=>array('func'=>'avg', 'expression'=>$field,'alias'=>'avg'));
		return (float)$this->ea11();
	}
	function sum($field){
		$this->queryFields=array('sum'=>array('func'=>'sum', 'expression'=>$field,'alias'=>'sum'));
		return (float)$this->ea11();
	}


	/**
	 *
	 * @return models_object
	 */
	/*
	function __call($name,$arguments){
		array_unshift($arguments,$this);
		return call_user_func_array(array(models::$models[$this->activeModel],$name),$arguments);
	}
	 */
	/**
	 *
	 * @return models_object
	 */
	function call($name,$arguments=array()){
		if (!\is_array($arguments))$arguments=array($arguments);
		\array_unshift($arguments,$this);
		return \call_user_func_array(array(models::$models[$this->activeModel],$name),$arguments);
	}
}