<?php
namespace c;

/**
 * ORM Storage
 * @author Kosmom <Kosmom.ru>
 */
class storage{
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
		return self::$durty[$modelName][$pk]+array($model->getPrimaryField()=>$pk);
	}
	static function is_set($model,$pk){
		$modelName=get_class($model);
		return (isset(self::$base[$modelName][$pk]));
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