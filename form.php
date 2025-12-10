<?php
namespace c;

/**
 * Form render & validate
 * @author Kosmom <Kosmom.ru>
 */
class form implements \ArrayAccess{
	const FIELD_KEY='{FIELD_KEY}';
	var $defaultResolver='c\\factory\\form_bootstrap';
	var $prop=array('type'=>'normal');
	private $data;
	private $ajax=false;
	private $fileForm=false;
	var $fields=array();
	var $subform;
	var $counter;
	private $submit;
	private $needSort=false;
	var $disabledReturn=true;
	var $attributes;
	var $classes=array();
	var $target;
	private $renderedFields=array();
	private static $submitCount;

	static $fieldCounter=0;
	
	function offsetExists($offset){
		return isset($this->fields[$offset]);
	}

	/**
	 * Get form field
	 * @param string $offset
	 * @return formfield|null
	 */
	function offsetGet($offset){
		return $this->offsetExists($offset)?new formfield($this->fields[$offset]):null;
	}

	function offsetSet($offset,$value){
		if ($offset===null)throw new \Exception('can not add empty field to form');
		if ($this->offsetExists($offset))unset($this->fields[$offset]);
		$this->addField($offset,$value);
	}

	function offsetUnset($offset){
		unset($this->fields[$offset]);
	}

	function __construct($fields=null){
		if (\is_array($fields))$this->addFields($fields);
		if (isset(core::$data['form_prop_type']))$this->prop['type']=core::$data['form_prop_type'];
	}

	/**
	 * Is form was submited
	 * @return boolean|array key-val of field
	 */
	function isSubmit(){
		$request=$this->method()=='GET'?$_GET:$_POST;
		foreach ($this->submit as $field=>$item){
			foreach ($item as $value=>$t){
				if (@$request[$field]==$value)return array('field'=>$field,'value'=>$value);
			}
		}
		return false;
	}
	/**
	 * @deprecated since version 3.4
	 */
	function is_submit(){
		return $this->isSubmit();
	}
	function disabledReturn($false){
		$this->disabledReturn=$false;
		return true;
	}

	function classes($classes=null){
		if ($classes===null)return $this->classes;
		if (\is_string($classes))$classes=array($classes);
		$this->classes=$classes;
		return $this;
	}
	function addClass($class){
		$this->classes[]=$class;
		return $this;
	}
	function attributes($attributes=null){
		if ($attributes===null)return $this->attributes;
		$this->attributes=$attributes;
		return $this;
	}
	function addAttribute($key,$value){
		$this->attributes[$key]=$value;
		return $this;
	}

	function destroy(){
		$this->fields=null;
		return $this;
	}

	function isForm(){
		return (bool)$this->fields;
	}

	function __toString(){
		return $this->render();
	}

	function toForms($alias='default'){
		forms::$forms[$alias]=$this;
		forms::activeForm($alias);
	}

	/**
	 * Get object of subform structure
	 * @param string $name subform field key name
	 * @param null|int counter counter of subform
	 */
	function getSubform($name,$counter=null){
		if (!isset($this->fields[$name]))throw new \Exception('form element is miss');
		if (!isset($this->fields[$name]['subform']))throw new \Exception('form element is not subform state');
		if ($counter==null)return isset($this->fields[$name]['value'])?$this->fields[$name]['value']:array();
		if (isset($this->fields[$name]['value'][$counter])){
			return $this->fields[$name]['value'][$counter];
		}else{
			$this->fields[$name]['value'][$counter]=clone $this->fields[$name]['subform'];
			$this->fields[$name]['value'][$counter]->subform=$name;
			$this->fields[$name]['value'][$counter]->counter=$counter;
		}
		return $this->fields[$name]['value'][$counter];
	}

	function getSubformTemplate($name,$counter=null){
		if (!isset($this->fields[$name]))throw new \Exception('form element is miss');
		if (!isset($this->fields[$name]['subform']))throw new \Exception('form element is not subform state');
		$template=clone $this->fields[$name]['subform'];
		//input::filter($template->forms,'iconv');
		$template->subform=$name;
		$template->counter=$counter;
		return $template;
	}

	/**
	 * Add field to form stack with all arguments
	 * @param array $fields
	 * @example $field=array('name'=>array(
	 *				'label'=>'label',
	 *				'value'=>'value',
	 *				'tooltip'=>'tooltip',
	 *				'type'=>'text',
	 *				'render'=>'auto',
	 *				'readonly'=>true,
	 *				'inputmask'=>'mask',
	 *				'subform'=>false,
	 *				'attributes'=>array('id'=>'name'),
	 *				'filters'=>array('trim','lower'),
	 *				'helper'=>'helper text',
	 *				'validate'=>array(
	 *					array('type'=>'maxlength', 'value'=> 100,'level'=>'error','text'=>'Max length for label is 100 chars'),
	 *					array('type'=>'required'),
	 *					array('type'=>'pattern', 'value'=>'/^0{1,3}$/'),
	 *				)),
	 *			);
	 */
	function addFields($fields){
		if (!\is_array($fields))throw new \Exception('Form addFields is not array');
		if (core::$debug)debug::group('Form addField');
		if (core::$debug)debug::dir($fields);
		if (core::$debug)debug::groupEnd();
		foreach ($fields as $key=>$item){
			$this->addField($key, $item);
		}
		return $this;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function add_fields($fields){
		return $this->addFields($fields);
	}

	/**
	 * @deprecated since version 3.4
	 */
	function add_field($fieldKey,$params=null){
		return $this->addField($fieldKey,$params);
	}
	/**
	 * Add field to form
	 * @param string $key
	 * @param array|null $params params of field
	 * @return form
	 */
	function addField($key,$params=null){
		if ($params instanceof arrayaccess)$params=(array)$params;
		if (\is_array($key))return $this->addFields($key);
		if (!$params)throw new \Exception('Form addField not set parameters');
		if (\is_string($params))throw new \Exception('Form addField error in fields');
		
		if (!isset($params['type']))$params['type']='text';
			if ($params['type']=='submit'){
				$this->submit[$key][$params['value']]=1;
				if (core::$debug && !$params['value'])debug::trace('Form add_field SUBMIT not have value parameter',error::WARNING);
			}
			if ($params['type']=='file')$this->fileForm=true;
			if (isset($params['filter'])){
				if (core::$debug)debug::trace('Form add_field filter send. Need filters',error::WARNING);
				$params['filters']=$params['filter'];
				unset($params['filter']);
			}
			if ($params['type']=='subform'){
				foreach ($params['subform']->fields as $subitem){
					if ($subitem['type']=='file')$this->fileForm=true;
				}
			}
			if (!isset($params['label_full']))@$params['label_full']=$params['label'];

			if (isset(core::$data['form_attribute'])){
				foreach (core::$data['form_attribute'] as $function){
					$params=$function($params);
				}
			}

			// name const transform
			if (isset($params['name']) && \is_array($params['name']) && $params['name'][1]==self::FIELD_KEY)$params['name'][1]=$key;
			if (isset($params['position']))$this->needSort=true;
			$this->fields[$key]=$params;
		
		return $this;
	}
	/**
	 * Add field to form as already exists field
	 * @param string $fieldKey key of new field
	 * @param string $asFieldKey key of exists field
	 * @param array $params addition params
	 * @return form
	 * @throws \Exception
	 */
	function addFieldAs($fieldKey,$asFieldKey,$params=array()){
		if (!isset($this->fields[$asFieldKey]))throw new \Exception('Field as not exists in form');
		if (!$params)$params=array();
		return $this->addField($fieldKey,$params+$this->fields[$asFieldKey]);
	}
	function addSubmitField($params=array(),$fieldKey=null){
		if (\is_string($params))$params=array('value'=>$params);
		if (!$fieldKey)$fieldKey='submit'.(self::$submitCount++);
		return $this->addField($fieldKey,$params+array('type'=>'submit','value'=>'Submit'));
	}

	function ajax($value=null){
		if ($value===null)return !empty($this->ajax);
		$value=(bool)$value;
		$this->ajax=$value;
		$this->addField(array(
			'_ajax'=>array(
				'type'=>'hidden',
				'value'=>1
		)));
		if ($value){
			mvc::addJs('jquery.form');
			mvc::addJs('core');
		}
	}

	/**
	 * @deprecated since version 3.4
	 */
	function file_form($isFile=null){
		return $this->fileForm($isFile);
	}

	/**
	 * Set or get is file attribute for form
	 * @param null|boolean $is_file
	 * @return boolean
	 */
	function fileForm($is_file=null){
		if ($is_file===null)return $this->fileForm;
		$this->fileForm=(bool)$is_file;
	}

	function method($method=null){
		if (!$method)return isset($this->prop['method'])?$this->prop['method']:'POST';
		$method=\strtoupper($method);
		if (!\in_array($method,array('GET','POST')))throw new \Exception('form method is not support');
		$this->prop['method'] = $method;
		return $this;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function form_inline(){
		return $this->formInline();
	}

	/**
	 * set form to inline render mode
	 * @return form
	 */
	function formInline(){
		$this->prop['type']='form-inline';
		unset($this->prop['label_classes_array']);
		unset($this->prop['input_classes_array']);
		return $this;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function form_horizontal($label_classes_array,$input_classes_array=12){
		return $this->formHorizontal($label_classes_array,$input_classes_array);
	}

	/**
	 * Set form to horizontal render mode
	 * @param array $labelClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4)
	 * @param array|int $inputClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4) or number max cells in row
	 * @param string $form
	 */
	function formHorizontal($labelClassesArray,$inputClassesArray=12){
		$this->prop['type']='form-horizontal';
		$this->prop['label_classes_array']=$labelClassesArray;
		if (\is_array($inputClassesArray)){
			$this->prop['input_classes_array']=$inputClassesArray;
			return;
		}
		if (\is_numeric($inputClassesArray)){
			$total=array();
			foreach($labelClassesArray as $key=>$val){
				$total[$key]=(int)$inputClassesArray-$val;
			}
			$this->prop['input_classes_array']=$total;
		}
		return $this;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function set_data($data=null){
		return $this->setData($data);
	}

	function addDataForce($data=null,$namePriority=true){
		return $this->addData($data,$namePriority,true);
	}
	function addData($data=null,$namePriority=true,$force=false){
		$this->data=null;
		if (core::$debug)debug::group('Form setData');
		if ($data===null)$data=(@$this->prop['method']=='GET'?$_GET:$_POST);
		if ($this->ajax)input::filter($data,'iconv'); //? need tests

		if ($data instanceof model)$data=$data->toArray();
		if (\is_array($data)){
			foreach ($this->fields as $key=>$item){
				$field=$key;
				$value=false;
				if ($namePriority && isset($item['name']))$field=$item['name'];
				if (@$data[$field] instanceof model){
					$value=(string)$data[$field];
				}elseif (\is_string($field)){
					if (isset($data[$field]))$value=$data[$field];
				}elseif (\is_array($field)){ // probably not needed
					if (isset($field[1])){
						if (isset($data[$field[0]][$field[1]]))$value=$data[$field[0]][$field[1]];
					}else{
						if (isset($data[$field[0]]))$value=$data[$field[0]];
					}
				}
				if ($value===false)continue;
				if (@$item['filters'])input::filter($value,$item['filters']);
				if (@$item['type']=='subform'){
					foreach ($value as $counter=>$val){
						if (!isset($this->fields[$key]['value'][$counter])){
							$this->fields[$key]['value'][$counter]=clone $this->fields[$key]['subform'];
							//input::filter($this->forms[$key]['value'][$counter]->forms,'iconv');
							$this->fields[$key]['value'][$counter]->subform=$key;
							$this->fields[$key]['value'][$counter]->counter=$counter;
						}
						$this->fields[$key]['value'][$counter]->setData($val,$namePriority,$force);
					}
				}elseif (!$force || ($force && (!$this->fields[$key]['disabled'] && !$this->fields[$key]['readonly'] && $this->fields[$key]['type']!='submit'))){
					$this->fields[$key]['value']=$value;
				}
				if (core::$debug)debug::dir(array($key=>$value));
			}
		}elseif (\is_object($data)){
			foreach ($this->fields as $key=>$item){
				$field=$key;
				$value=false;
				if ($namePriority && isset($item['name']))$field=$item['name'];
				if (\is_string($field)){
					if (isset($data->$field))$value=$data->$field;
				}elseif (\is_array($field)){ // probably not needed
					if (isset($data->$field[0]))$value=$data->$field[0];
				}
				if ($value===false)continue;
				if ($item['filters'])input::filter($value,$item['filters']);
				if ($item['type']=='subform'){
					foreach ($value as $counter=>$val){
						if (!isset($this->fields[$key]['value'][$counter])){
							$this->fields[$key]['value'][$counter]=clone $this->fields[$key]['subform'];
							//input::filter($this->forms[$key]['value'][$counter]->forms,'iconv');
							$this->fields[$key]['value'][$counter]->subform=$key;
							$this->fields[$key]['value'][$counter]->counter=$counter;
						}
						$this->fields[$key]['value'][$counter]->setData($val,$namePriority,$force);
					}
				}else{
					if (!$force || ($force && (!$this->fields[$key]['disabled'] && !$this->fields[$key]['readonly'])))$this->fields[$key]['value']=$value;
				}
				if (core::$debug)debug::dir(array($key=>$value));
			}
		}else{ // array
			if (core::$debug)debug::consoleLog('data is not array, data is '. gettype($data),error::ERROR);
			if (core::$debug)debug::groupEnd();
			if (core::$debug)debug::trace('Form set data error. Data is not array',error::ERROR);
			return false;
		}
		if (core::$debug)debug::groupEnd();
		return $this;
	}


	/**
	 * Set key-val data to form
	 * @param array $data default - $_POST
	 * @paran boolean $namePriority check name attribute on set value
	 * @paran boolean $force set only not disabled fields
	 * @param string form
	 */
	function setData($data=null,$namePriority=true,$force=false){
		return $this->addData($data,$namePriority,$force);
	}
	function setDataForce($data=null,$namePriority=true){
		return $this->setData($data,$namePriority,true);
	}

	/**
	 * @deprecated since version 3.4
	 */
	function get_data(){
		return $this->getData();
	}
	function getFieldValue($fieldName){
		if ($this->data!==null)return $this->data[$fieldName];
		foreach ($this->fields as $name=>$item){
			if (isset($item['name'])){
				if ($item['name']!=$fieldName)continue;
			}else{
				if ($fieldName!=$name)continue;
			}
			if ($item['type']=='check' || $item['type']=='checkbox' || $item['type']=='boolean')return (bool)$item['value'];
			if (!isset($item['value']) && !isset($item['default']))return null;
			if (isset($item['value']))return $item['value'];
			return $item['default'];
		}
	}
	/**
	 * Get key-val data from form
	 * @param string $form
	 * @return array
	 */
	function getData(){
			$out=array();
			if ($this->data!==null)return $this->data;
			foreach ($this->fields as $name=>$item){
				if ($item['type']=='check' || $item['type']=='checkbox' || $item['type']=='boolean'){
				$out[$name]=(bool)$item['value'];
				continue;
			}
			if (!isset($item['value']) && !isset($item['default'])) continue;
			if (isset($item['value'])){
				if ($item['type']=='subform'){
					$value=array();
					foreach ($item['value'] as $key=>$val){
						$value[$key]=$val->getData();
					}
				}else{
					$value=$item['value'];
				}
			}else{
				$value=$item['default'];
			}
			if (isset($item['name'])){
				if (is_string($item['name'])){
					$out[$item['name']]=$value;
				}elseif (is_array($item['name'])){
					if (isset($item['name'][1])){
						$out[$item['name'][0]][$item['name'][1]]=$value;
					}else{
						$out[$item['name'][0]]=$value;
					}
				}
			}else{
				$out[$name]=$value;
			}
		}
		$this->data=$out;
		return $out;
	}

	function isInvalid($data=array(),$checkData=false){
		return !$this->isValid($data,$checkData);
	}

	/**
	 * @deprecated since version 3.4
	 */
	function is_valid($data=array(),$checkData=false){
		return $this->isValid($data,$checkData);
	}
	/**
	 * Check form values to valid
	 * @return boolean
	 */
	function isValid($data=array(),$checkData=false){
		$valid=true;
		foreach ($this->fields as $key=>$item){
			if (@$item['disabled']) continue;
			if (!@\is_array($item['validate']) && @!$item['subform'])continue;
			if (isset($item['subform'])){
				foreach ($item['value'] as $value){
					$result=$value->isValid($data[$key],$checkData); //$result=$item['subform']->isValid($data[$key],$checkData);
					if ($result===false)$valid=false;
				}
			}else{
				if (@$item['type']=='select' && isset($item['validate'])){
					foreach($item['validate'] as &$validate){
						if ($validate['type']=='in' && empty($validate['values']))$validate['values']=\array_keys($item['values']);
					}
					unset($validate);
				}
				$value=$checkData?$data[$key]:@$item['value'];
				$result=input::validate($item['type']=='file'?($_FILES[$key]['tmp_name']?$_FILES[$key]['tmp_name']:$value):$value,$item['validate'],$item,$key);
				if ($result===false){
					if (core::$debug){
						debug::consoleLog('Form validation false on field "'.$key.'". Field:',error::WARNING);
						debug::dir($item);
					}
					$valid=false;
				}
			}
		}
		return $valid;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function fill_data(){
			return $this->fillData();
		}
	function fillData(){
		$this->data=null;
		foreach ($this->fields as $key=>&$params){
			if (!\is_callable($params['fill']))continue;
			$out=$params['fill']($this->getData(),$key);
			if ($out!==null)$params['value']=$out;
		}
		return $this;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_as_table(){
		return $this->renderAsTable();
	}
	/**
	 * render form as table
	 * @return string html
	 */
	function renderAsTable(){
		$out='<table>';
		foreach ($this->fields as $name=>$item){
			if ($item['type']=='submit') continue;
			$out.='<tr><th>'.(isset($item['label_full'])?$item['label_full']:$item['label']).'</th><td>'.(isset($item['values'][$item['value']])?$item['values'][$item['value']]:$item['value']).'</td></tr>';
		}
		return $out.='</table>';
	}

	/**
	 * Render full form
	 * @return string html text
	 */
	function render(){
		if (core::$debug){
			debug::group('Form render collapse:');
			$htmlout='<?=$form->renderBeginTag()?>';
		}
		$out='';
		if (!$this->subform)$out=$this->renderBeginTag();
		if (!\is_array($this->fields))return false;
		$sort_form=$this->fields;
		if (!$this->needSort){
			foreach ($this->fields as $field){
				if (isset($field['position'])){
					$this->needSort=true;
					break;
				}
			}
		}
		if ($this->needSort)datawork::stable_uasort($sort_form,array('c\\datawork','positionCompare'));
		foreach ($sort_form as $name=>$item){
			if (core::$debug)$htmlout.='
<?=$form->renderField(\''.$name.'\')?>';
			$out.=$this->renderField($name,$item);
		}
		
		if (core::$debug){
			$htmlout.='
<?=$form->renderEndTag()?>';
			debug::consoleLog($htmlout);
			debug::groupEnd();
		}
		return $out.($this->subform?'':$this->renderEndTag());
	}
	/**
	 * Render not rendered early fields form
	 * @return string html text
	 */
	function renderOtherFields(){
		$out='';
		if (!\is_array($this->fields))return false;
		$sort_form=$this->fields;

		if ($this->needSort)datawork::stable_uasort($sort_form,array('c\\datawork','positionCompare'));
		if (core::$debug){
			debug::group('Form OtherFields render collapse:');
			$htmlout='';
		}
		foreach ($sort_form as $name=>$item){
			if (@$this->renderedFields[$name])continue;
			if ($item['type']=='subform')continue;
			$out.=$this->renderField($name,$item);
			if (core::$debug)$htmlout.='<?=$form->renderField(\''.$name.'\')?>
';
		}
		if (core::$debug){
			debug::consoleLog($htmlout);
			debug::groupEnd();
		}
		return $out;
	}

	/**
	 * Render part of form with prefix in 'name' prop array('prefix','name')
	 * @param string $prefix
	 * @return string html render part of form
	 */
	function renderFieldsByName($prefix){
		$partform=array();
		foreach ($this->fields as $key=>$field){
			if (!isset($field['name'])) continue;
			if (!\is_array($field['name'])) continue;
			if ($field['name'][0]==$prefix)$partform[$key]=$field;
		}
		if ($this->needSort)datawork::stable_uasort($partform,array('c\\datawork','positionCompare'));
		$htmlout='';
		$out='';
		foreach ($partform as $key=>$field){
			if (core::$debug)$htmlout.='
<?=$form->renderField(\''.$key.'\')?>';
			$out.=$this->renderField($key);
		}
		if (core::$debug){
			debug::consoleLog($htmlout);
			debug::groupEnd();
		}
		return $out;
	}
	/**
	 * Render part of form with position between min and max
	 * @param int $min
	 * @param int $max
	 * @return string html render part of form
	 */
	function renderFieldsByPosition($min=0,$max=999){
		$partform=array();
		foreach ($this->fields as $key=>$field){
			if (!isset($field['position'])) continue;
			if ($field['position']>=$min && $field['position']<=$max)$partform[$key]=$field;
		}
		$htmlout='';
		$out='';
		if ($this->needSort)datawork::stable_uasort($partform,array('c\\datawork','positionCompare'));
		foreach ($partform as $key=>$field){
			if (core::$debug)$htmlout.='
<?=$form->renderField(\''.$key.'\')?>';
			$out.=$this->renderField($key);
		}
		if (core::$debug){
			debug::consoleLog($htmlout);
			debug::groupEnd();
		}
		return $out;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_begin_tag(){
		return $this->renderBeginTag();
	}
	function renderBeginTag(){
		$classes=$this->classes;
		if ($this->ajax)$classes[]='form_ajax';
		if (!empty($this->prop['type']))$classes[]=$this->prop['type'];

		if (\is_array($this->attributes)){
			$attributes=array();
			foreach($this->attributes as $key=>$val){
				$attributes[]=$key.'="'.input::htmlspecialchars($val).'"';
			}
			$attributes=\implode(' ',$attributes).' ';
		}else{
			$attributes='';
		}		
		return '<form '.$attributes.($this->target?'target="'.input::htmlspecialchars($this->target).'" ':'').'method='.(@$this->prop['method']?$this->prop['method']:'POST').($this->fileForm?' enctype="multipart/form-data"':'').($classes?' class="'.(\implode(' ',$classes)).'"':'').'>';
	}
	function renderEndTag(){
		return '</form>';
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_end_tag(){
		return '</form>';
	}

	
	/**
	 * Get count of fill fields
	 * @return array
	 */
	function fillness(){
		$total=0;
		$fill=0;
		foreach ($this->fields as $field){
			if ($field['type']=='subform'){
				$f=$field['subform']->fillness();
				$total+=$f['total'];
				$fill+=$f['fill'];
				break;
			}
			$total++;
			if (!empty($field['value']))$fill++;
		}
		return array('total'=>$total,'fill'=>$fill);
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_field($name,$item=null){
		return $this->renderField($name,$item);
	}
	function getResolver($name,$item){
		$resolver=empty($item['resolver'])?$this->defaultResolver:$item['resolver'];
		if (\is_string($resolver))return new $resolver($this,$name);
		return $resolver;
	}
	function renderField($name,$item=null){
		$item=$this->mergeItem($name,$item);
		$resolver=$this->getResolver($name,$item);
		$this->renderedFields[$name]=true;

		//subform prepare
		$renderName=$name;
		if (!isset($item['name']))$item['name']=$renderName;
		if (\is_string($item['name']))$item['name']=array($item['name']);
		if ($this->counter!==null)\array_unshift($item['name'],$this->counter);
		if ($this->subform!==null)\array_unshift($item['name'],$this->subform);
		if (\is_array($item['name'])){
			   $renderName=\array_shift($item['name']);
				foreach ($item['name'] as $subitem){
					$renderName.='['.$subitem.']';
				}
		}elseif (\is_callable($item['name'])){
				$renderName=$item['name']($item);
		}
			return $resolver->renderField($item,$renderName);
	}
	private function mergeItem($name,$item){
		if ($item===null){
			if (isset($this->fields[$name])){
				return $this->fields[$name];
			}else{
				if (core::$debug)debug::consoleLog('Form field '.$name.' not exists',error::ERROR);
				throw new \Exception('Form field '.$name.' not exists');
			}
		}elseif (\is_array($item)){
			if (\is_array($this->fields[$name]))$item+=$this->fields[$name];
		}
		return $item;
	}
	/**
	 * @deprecated since version 3.4
	 */
	function render_field_form_group_begin($name,$item=null){
		return $this->renderFieldFormGroupBegin($name,$item);
	}
	function renderFieldFormGroupBegin($name,$item=null){
		if ($name)$item=$this->mergeItem($name,$item);
		$resolver=$this->getResolver($name,$item);
		return $resolver->renderFieldFormGroupBegin($item);
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_field_form_group_end($name,$item=null){
		return $this->renderFieldFormGroupEnd($name,$item);
	}
	function renderFieldFormGroupEnd($name,$item=null){
		if ($name)$item=$this->mergeItem($name,$item);
		$resolver=$this->getResolver($name,$item);
		return $resolver->renderFieldFormGroupEnd($item);
	}
	/**
	 * @deprecated since version 3.4
	 */
	function render_field_label($name,$item=null){
		return $this->renderFieldLabel($name,$item);
	}
	function renderFieldLabel($name,$item=null){
		if ($name)$item=$this->mergeItem($name,$item);
		$resolver=$this->getResolver($name,$item);
		return $resolver->renderFieldLabel($item);
	}
	/**
	 * @deprecated since version 3.4
	 */
	function render_field_field($name,$item=null){
		return $this->renderFieldField($name,$item);

	}
	function renderFieldField($name,$item=null){
		$item=$this->mergeItem($name,$item);
		$resolver=$this->getResolver($name,$item);
		$this->renderedFields[$name]=true;

		//subform prepare
		$renderName=$name;

		if (!isset($item['name']))$item['name']=$renderName;
		if (\is_string($item['name']))$item['name']=array($item['name']);
		if ($this->counter!==null)\array_unshift($item['name'],$this->counter);
		if ($this->subform!==null)\array_unshift($item['name'],$this->subform);
		if (\is_array($item['name'])){
				$renderName=\array_shift($item['name']);
				foreach ($item['name'] as $subitem){
						$renderName.='['.$subitem.']';
				}
		}elseif (\is_callable($item['name'])){
				$renderName=$item['name']($item);
		}
		return $resolver->renderFieldField($item,$renderName);
	}
	function getFormDescription($key=''){
		$listfields=array();
		foreach ($this->fields as $k=>$value){
			$item=array('name'=>isset($value['label_full'])?$value['label_full']:$value['label'],'values'=>$value['values']);
			if (isset($value['name'])){
				if (\is_string($value['name'])){
					$listfields[$value['name']]=$item;
				}elseif (isset($value['name'][1])){
					$listfields[$value['name'][0]][$value['name'][1]]=$item;
				}else{ // array with 1 element
					$listfields[$value['name'][0]]=$item;
				}
			}else{
				$listfields[$k]=$item;
			}
		}
		if ($key){
			return $listfields[$key];
		}
		return $listfields;
	}
}