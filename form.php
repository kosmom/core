<?php
namespace c;

/**
 * Form render & validate
 * @author Kosmom <Kosmom.ru>
 */
class form implements \ArrayAccess{
	const FIELD_KEY='{FIELD_KEY}';

	var $prop=array();
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
	
	static private $fieldCounter=0;

	function offsetExists($offset){
		return isset($this->fields[$offset]);
	}

	function offsetGet($offset){
		return $this->offsetExists($offset)?$this->fields[$offset]:null;
	}

	function offsetSet($offset, $value){
		if (is_null($offset))throw new \Exception('can not add empty field to form');
		if ($this->offsetExists($offset))unset($this->fields[$offset]);
		$this->addField($offset,$value);
	}

	function offsetUnset($offset){
		unset($this->fields[$offset]);
	}

	function __construct($fields=null){
		if (is_array($fields))$this->addFields($fields);
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

	function __toString() {
		return $this->render();
	}

	function toForms($alias='default'){
		forms::$forms[$alias]=$this;
		forms::activeForm($alias);
	}

	/**
	 * Get object of subform structure
	 * @param string $name subform field key name
	 * @param null|number counter counter of subform
	 */
	function getSubform($name,$counter=null){
		if (!isset($this->fields[$name]))throw new \Exception('form element is miss');
		if (!isset($this->fields[$name]['subform']))throw new \Exception('form element is not subform state');
		if ($counter==null)return $this->fields[$name]['value'];
		if (isset($this->fields[$name]['value'][$counter])){
			return $this->fields[$name]['value'][$counter];
		}else{
			$this->fields[$name]['value'][$counter]=clone $this->fields[$name]['subform'];
			//input::filter($this->forms[$name]['value'][$counter]->forms,'iconv');
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
		if (!is_array($fields))throw new \Exception('Form addFields is not array');
		if (core::$debug)debug::group('Form addField');
		if (core::$debug)debug::dir($fields);
		if (core::$debug)debug::groupEnd();
		foreach ($fields as $key=>$item){
			if (!isset($item['type']))$item['type']='text';
			if (@$item['type']=='submit'){
				$this->submit[$key][$item['value']]=1;
				if (core::$debug && !$item['value'])debug::trace('Form add_field SUBMIT not have value parameter',error::WARNING);
			}
			if (@$item['type']=='file')$this->fileForm=true;
			if (isset($item['validator'])){
				if (core::$debug)debug::trace('Form add_field validator send. Need validate',error::WARNING);
				$item['validate']=$item['validator'];
				unset($item['validator']);
			}
			if (isset($item['validators'])){
				if (core::$debug)debug::trace('Form add_field validators send. Need validate',error::WARNING);
				$item['validate']=$item['validators'];
				unset($item['validators']);
			}
			if (isset($item['filter'])){
				if (core::$debug)debug::trace('Form add_field filter send. Need filters',error::WARNING);
				$item['filters']=$item['filter'];
				unset($item['filter']);
			}
			if (@$item['type']=='subform'){
				foreach ($item['subform']->fields as $subitem){
					if ($subitem['type']=='file')$this->fileForm=true;
				}
			}
			if (!isset($item['label_full']))@$item['label_full']=$item['label'];

			if (isset(core::$data['form_attribute'])){
				foreach (core::$data['form_attribute'] as $function){
					$item=$function($item);
				}
			}

			// name const transform
			if (isset($item['name']) && is_array($item['name']) && $item['name'][1]==self::FIELD_KEY)$item['name'][1]=$key;
			if (isset($item['position']))$this->needSort=true;
			$this->fields[$key]=$item;
		}
		return $this;
	}

	/**
	 * Add field to form
	 * @param array $fields
	 * @deprecated since version 3.4
	 */
	function add_fields($fields){
		return $this->addFields($fields);
	}

	/**
	 * Add field to form
	 * @param string $fieldKey
	 * @param array $params params of field
	 * @return form
	 * @deprecated since version 3.4
	 */
	function add_field($fieldKey,$params=null){
		return $this->addField($fieldKey,$params);
	}
	/**
	 * Add field to form
	 * @param string $fieldKey
	 * @param array $params params of field
	 * @return form
	 */
	function addField($fieldKey,$params=null){
		if (is_array($fieldKey))return $this->addFields($fieldKey);
		if (!$params)throw new \Exception('Form add_field not set parameters');
		return $this->addFields(array($fieldKey=>$params));
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
		return $this->addFields(array($fieldKey=>$params+$this->fields[$asFieldKey]));
	}
	function addSubmitField($params=array(),$fieldKey='submit'){
		if (is_string($params))$params=array('value'=>$params);
		return $this->addField($fieldKey,$params+array('type'=>'submit','value'=>'Submit','classes'=>'btn btn-primary'));
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
	 * Set or get is file attribute for form
	 * @param null|boolean $isFile
	 * @return boolean
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
		$method=strtoupper($method);
		if (!in_array($method,array('GET','POST')))throw new \Exception('form method is not support');
		$this->prop['method'] = $method;
		return $this;
	}

	/**
	 * @deprecated since version 3.4
	 * @return form
	 */
	function form_inline(){
		return $this->formInline();
	}

	/**
	 * set form to inline render mode
	 * @return \c\form
	 */
	function formInline(){
		$this->prop['type']='form-inline';
		unset($this->prop['label_classes_array']);
		unset($this->prop['input_classes_array']);
		return $this;
	}

	/**
	 * Set form to horizontal render mode
	 * @param array $label_classes_array example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4)
	 * @param array|number $input_classes_array example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4) or number max cells in row
	 * @param string $form
	 * @deprecated since version 3.4
	 */
	function form_horizontal($label_classes_array,$input_classes_array=12){
		return $this->formHorizontal($label_classes_array,$input_classes_array);
	}

	/**
	 * Set form to horizontal render mode
	 * @param array $labelClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4)
	 * @param array|number $inputClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4) or number max cells in row
	 * @param string $form
	 */
	function formHorizontal($labelClassesArray,$inputClassesArray=12){
		$this->prop['type']='form-horizontal';
		$this->prop['label_classes_array']=$labelClassesArray;
		if (is_array($inputClassesArray)){
			$this->prop['input_classes_array']=$inputClassesArray;
			return;
		}
		if (is_numeric($inputClassesArray)){
			$total=array();
			foreach($labelClassesArray as $key=>$val){
				$total[$key]=(int)$inputClassesArray-$val;
			}
			$this->prop['input_classes_array']=$total;
		}
		return $this;
	}

	/**
	 * Set key-val data to form
	 * @param array $data default - $_POST
	 * @param string $form
	 * @deprecated since version 3.4
	 */
	function set_data($data=null){
		return $this->setData($data);
	}

	function addDataForce($data=null,$namePriority=true){
		return $this->addData($data,$namePriority,true);
	}
	function addData($data=null,$namePriority=true,$force=false){
		if (core::$debug)debug::group('Form setData');
		if ($data===null)$data=(@$this->prop['method']=='GET'?$_GET:$_POST);
		if ($this->ajax)input::filter($data,'iconv'); //? need tests

		if ($data instanceof model)$data=$data->toArray();
		if (is_array($data)){
			foreach ($this->fields as $key=>$item){
				$field=$key;
				$value=false;
				if ($namePriority && isset($item['name']))$field=$item['name'];
				if (@$data[$field] instanceof model){
					$value=(string)$data[$field];
				}elseif (is_string($field)){
					if (isset($data[$field]))$value=$data[$field];
				}elseif (is_array($field)){ // probably not needed
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
		}elseif (is_object($data)){
			foreach ($this->fields as $key=>$item){
				$field=$key;
				$value=false;
				if ($namePriority && isset($item['name']))$field=$item['name'];
				if (is_string($field)){
					if (isset($data->$field))$value=$data->$field;
				}elseif (is_array($field)){ // probably not needed
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
			if (core::$debug)debug::consoleLog('data is not array',error::ERROR);
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
		$this->data=null;
		return $this->addData($data,$namePriority,$force);
	}
	function setDataForce($data=null,$namePriority=true){
		return $this->setData($data,$namePriority,true);
	}

	/**
	 * Get key-val data from form
	 * @param string $form
	 * @return array
	 * @deprecated since version 3.4
	 */
	function get_data(){
		return $this->getData();
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
				if (@$item['type']=='check' || @$item['type']=='checkbox' || @$item['type']=='boolean'){
				$out[$name]=(bool)$item['value'];
				continue;
			}
			if (!isset($item['value']) && !isset($item['default'])) continue;
			if (isset($item['value'])){
				if (@$item['type']=='subform'){
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
			if (!@is_array($item['validate']) && @!$item['subform']) continue;
			if (isset($item['subform'])){
				$result=input::validate($value,$item['validate'],$item);
				if ($result===false)$valid=false;
				foreach ($item['value'] as $value){
					$result=$value->isValid($data[$key],$checkData);
					if ($result===false)$valid=false;
				}
			}else{
				if (@$item['type']=='select' && isset($item['validate'])){
					foreach($item['validate'] as &$validate){
						if ($validate['type']=='in' && empty($validate['values']))$validate['values']=array_keys($item['values']);
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
		foreach ($this->fields as $key=>&$params){
			if (!is_callable($params['fill']))  continue;
			$out=$params['fill']($this->getData(),$key);
			if (!is_null($out))$params['value']=$out;
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
		if (!is_array($this->fields))return false;
		$sort_form=$this->fields;

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
		if (!is_array($this->fields))return false;
		$sort_form=$this->fields;

		if ($this->needSort)datawork::stable_uasort($sort_form,array('c\\datawork','positionCompare'));
		foreach ($sort_form as $name=>$item){
			if ($this->renderedFields[$name])continue;
			if ($item['type']=='subform')continue;
			$out.=$this->renderField($name,$item);
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
			if (!is_array($field['name'])) continue;
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
	 * @param number $min
	 * @param number $max
	 * @return string html render part of form
	 */
	function renderFieldsByPosition($min=0,$max=999){
		$partform=array();
		foreach ($this->fields as $key=>$field){
			if (!isset($field['position'])) continue;
			$partform[$key]=$field;
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
		if (@$this->prop['type'])$classes[]=$this->prop['type'];

		if (is_array($this->attributes)){
			$attributes=array();
			foreach($this->attributes as $key=>$val){
				$attributes[]=$key.'="'.input::htmlspecialchars($val).'"';
			}
			$attributes=implode(' ',$attributes).' ';
		}else{
			$attributes='';
		}

		return '<form '.$attributes.($this->target?'target="'.input::htmlspecialchars($this->target).'" ':'').'method='.(@$this->prop['method']?$this->prop['method']:'POST').($this->fileForm?' enctype="multipart/form-data"':'').($classes?' class="'.(implode(' ',$classes)).'"':'').'>';
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

	private function horizontalGetLabelClass(){
		if (@$this->prop['type']!='form-horizontal')return '';
		$out=' class="control-label';
		foreach ($this->prop['label_classes_array'] as $key=>$val){
			$out.=' col-'.$key.'-'.$val;
		}
		return $out.'"';
	}

	private function horizontalGetColDiv($field){
		if (@$this->prop['type']!='form-horizontal')return '';
		$classes=array();
		if (!@$field['label'] || @$field['type']=='check' || @$field['type']=='checkbox'|| @$field['type']=='boolean'){
			foreach ($this->prop['label_classes_array'] as $key=>$val){
				$classes[]='col-'.$key.'-offset-'.$val;
			}
		}
		foreach ($this->prop['input_classes_array'] as $key=>$val){
			$classes[]='col-'.$key.'-'.$val;
		}
		return '<div class="'.implode(' ',$classes).'">';
	}

	private function inputGroupPrefix($item){
		if (empty($item['prefix']) && empty($item['postfix']))return '';
		return '<div class="input-group">'.(!empty($item['prefix'])?'<span class="'.(core::$data['render']=='bootstrap4'?'input-group-text':'input-group-addon').'">'.$item['prefix'].'</span>':'');
	}
	private function inputGroupPostfix($item){
		if (empty($item['prefix']) && empty($item['postfix']))return '';
		return (!empty($item['postfix'])?'<span class="'.(core::$data['render']=='bootstrap4'?'input-group-text':'input-group-addon').'">'.$item['postfix'].'</span>':'').'</div>';
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
	/**
	 * Calculate item data with all algoritms
	 * @param array $item
	 */
	function calcField(&$item){
		
	}
	function renderField($name,$item=null){
		$item=$this->mergeItem($name,$item);
		if (@$item['type']=='none')return '';
		if (!isset($item['attributes']['id']))$item['attributes']['id']='core_form_field_'.self::$fieldCounter++;
		$out=$this->renderFieldFormGroupBegin('',$item);
		if (@!$item['range'] && isset($item['ico']) && $this->prop['type']=='md-form')$out.= '<i class="'.$item['ico'].' prefix grey-text"></i>';
		if ($this->prop['type']=='md-form'){
			return $out.$this->renderFieldField($name,$item).$this->renderFieldLabel('',$item).$this->renderFieldFormGroupEnd('',$item);
		}else{
			return $out.$this->renderFieldLabel('',$item).$this->renderFieldField($name,$item).$this->renderFieldFormGroupEnd('',$item);
		}
	}
	private function mergeItem($name,$item){
		if ($item===null){
			if (isset($this->fields[$name])){
				return $this->fields[$name];
			}else{
				if (core::$debug)debug::consoleLog('Form field '.$name.' not exists',error::ERROR);
				throw new \Exception('Form field '.$name.' not exists');
			}
		}elseif (is_array($item)){
			if (is_array($this->fields[$name]))$item+=$this->fields[$name];
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
		if ($name!='')$item=$this->mergeItem($name,$item);
		if (@$item['type']=='hidden')return '';
		if (@$this->prop['type']=='form-inline' && $item['type']=='submit')return '';
		$base_group='form-group';
		if ($this->prop['type']=='md-form')$base_group='md-form';
		if (core::$data['render']=='bootstrap4' && in_array($item['type'],array('checkbox','boolean','radio')))$base_group='form-check';
		if (isset($item['group_classes'])){
			if (is_callable($item['group_classes'])){
				$classes=$item['group_classes']($item,$this->getData());
			}else{
				$classes=$item['group_classes'];
			}

			if (is_string($classes))$classes=explode(' ',$classes);
			//array only
			$classes=array_merge(array($base_group),$classes);
		}else{
			$classes=array($base_group);
		}
		if (isset($item['ico']) && $this->prop['type']!='md-form')$classes[]='has-feedback';

		$attributes=array();
		if (isset($item['group_attributes'])){
			if (is_callable($item['group_attributes'])){
				$attributes=$item['group_attributes']($item,$this->getData());
			}elseif (is_array($item['group_attributes'])){
				foreach ($item['group_attributes'] as $key=>$attribute){
					if (is_callable($attribute)){
						$attributes[$key]=$attribute($item,$this->getData());
					}else{
						$attributes[$key]=$attribute;
					}
				}
			}
			if (is_string($attributes)){
				$attr= explode(' ', $attributes);
			}else{
				foreach ($attributes as $key=>$val){
					$attr[]=input::htmlspecialchars($key).'="'.input::htmlspecialchars($val).'"';
				}
			}
		}

		return ' <div class="'.implode(' ',$classes).'" '.(@$attr? implode(' ', $attr):'').'>';
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_field_form_group_end($name,$item=null){
		return $this->renderFieldFormGroupEnd($name,$item);
	}
	function renderFieldFormGroupEnd($name,$item=null){
		if ($name!='')$item=$this->mergeItem($name,$item);
		if ($item['type']=='hidden')return '';
		return ((@$this->prop['type']!='form-inline' || $item['type']!='submit')?'</div>':'').(@$this->prop['type']=='form-horizontal'?'</div>':'');
	}
	/**
	 * @deprecated since version 3.4
	 */
	function render_field_label($name,$item=null){
		return $this->renderFieldLabel($name,$item);
	}
	function renderFieldLabel($name,$item=null){
		if ($name!='')$item=$this->mergeItem($name,$item);
		if (@$item['type']=='hidden')return '';
		if (@$this->prop['type']!='form-inline' || @$item['type']!='submit')return (!empty($item['label']) && @$item['type']!='check' && @$item['type']!='boolean' && @$item['type']!='checkbox'?'<label for="'.$item['attributes']['id'].'" '.$this->horizontalGetLabelClass().'>'.(@$item['label_html']?$item['label']:input::htmlspecialchars($item['label'])).'</label> ':'').$this->horizontalGetColDiv($item);
		return '';
	}
	/**
	 * @deprecated since version 3.4
	 */
	function render_field_field($name,$item=null){
		return $this->renderFieldField($name,$item);

	}
	function renderFieldField($name,$item=null){
		if ($name!='')$item=$this->mergeItem($name,$item);
		$this->renderedFields[$name]=true;
		$keyName=$name;
		if (!isset($item['name']))$item['name']=$keyName;
			if (is_string($item['name']))$item['name']=array($item['name']);
			if ($this->counter!==null)array_unshift($item['name'],$this->counter);
			if ($this->subform!==null)array_unshift($item['name'],$this->subform);
			if (is_array($item['name'])){
				$keyName=array_shift($item['name']);
				foreach ($item['name'] as $subitem){
					$keyName.='['.$subitem.']';
				}
			}elseif (is_callable($item['name'])){
				$keyName=$item['name']($item);
			}
		$renderName=$keyName;
		$required='';
		if (@$item['validate'])foreach($item['validate'] as $validate){
			if ($validate['type']=='required' || $validate['type']=='require'){
				$required='required ';
				continue;
			}
			if ($validate['type']=='maxlength')$item['attributes']['maxlength']=$validate['value'];
		}
		if (empty($item['render']))$item['render']='auto';
                if ($item['type']=='float'){
                    $item['type']='number';
                    if (!isset($item['attributes']['step']))$item['attributes']['step']='.0000001';
                    $item['value']= str_replace(',', '.', $item['value']);
                }
		$renderValue=isset($item['value'])?is_callable($item['value'])?$item['value']($item):$item['value']:@$item['default'];
		if (@$item['range']){
			$value['min']=(isset($renderValue['min'])?'value="'.input::htmlspecialchars($renderValue['min']).'" ':'');
			$value['max']=(isset($renderValue['max'])?'value="'.input::htmlspecialchars($renderValue['max']).'" ':'');
		}else{
			$value=($renderValue!=='')?'value="'.input::htmlspecialchars($renderValue).'" ':'';
		}
		$placeholder='';
		if (isset($item['placeholder'])){
			$placeholder='placeholder="'.input::htmlspecialchars($item['placeholder']).'" ';
			if ($item['render']!='select2')mvc::addJs('placeholder');
		}

		if (isset($item['inputmask'])){
                    $mask=$item['inputmask'];
			if (is_array($item['inputmask'])){
				$masks=array();
				foreach ($item['inputmask'] as $maskKey=>$mask){
					$masks[]="'".$maskKey."':'".$mask."'";
				}
				$item['attributes']['data-inputmask']=implode(',',$masks);
			}elseif($mask=='email' or $mask=='mail' or $mask=='phone' or $mask=='ip' or $mask=='date' or $mask=='mm/dd/yyyy'){
				if ($mask=='mail')$mask=='email';
				$item['attributes']['data-inputmask']="'alias':'".$mask."'";
			}elseif (substr($mask,0,1)=="'"){
				$item['attributes']['data-inputmask']=$item['inputmask'];
			}else{
				$item['attributes']['data-inputmask']="'mask':'".$mask."'";
			}
			if (is_array(@$item['classes'])){
				$item['classes'][]='inputmask';
			}else{
				@$item['classes'].=' inputmask';
			}
			mvc::addJs('inputmask');
		}
                
		if (@$item['disabled'])$item['attributes']['disabled']='disabled';
		if (@$item['readonly'])$item['attributes']['readonly']='readonly';
		$attributes='';
		if (isset($item['attributes']) && is_array($item['attributes'])){
				$attributes=array();
			foreach($item['attributes'] as $key=>$val){
				$attributes[]=$key.'="'.input::htmlspecialchars($val).'"';
			}
			$attributes=implode(' ',$attributes).' ';
		}
		$classes='';
		if (isset($item['classes'])){
			if (is_array($item['classes'])){
				foreach($item['classes'] as $key=>$val){
					$classes.=' '.input::htmlspecialchars($val);
				}
			}else{
				$classes.=' '.input::htmlspecialchars($item['classes']);
			}
		}
		$arg=array('name'=>$renderName,'attributes'=>$attributes,'value'=>$value,'classes'=>$classes,'multiple'=>false,'placeholder'=>$placeholder);
		if (isset(core::$data['form_render'][@$item['type']])){
			if (is_callable(core::$data['form_render'][$item['type']])){
				$a=core::$data['form_render'][$item['type']];
				$a=$a($item,$arg);
				if (is_string($a))return $a;
				$item=$a;
			}elseif (is_callable(core::$data['form_render'][$item['type']][$item['render']])){
				$a=core::$data['form_render'][$item['type']][$item['render']];
				$a=$a($item,$arg);
				if (is_string($a))return $a;
				$item=$a;
			}
		}
		if (is_callable($item['render']))return $item['render']($item,$arg);
		if (@$item['type']=='hidden')return '<input'.($classes?'class="'.$classes.'"':'').' name="'.$renderName.'" type="'.$item['type'].'" '.$attributes.$value.'/>';
		
		$out=$this->inputGroupPrefix($item);
		
		switch ($item['type']){
			case 'submit':
				if ($this->ajax())$out.='<input name="'.$renderName.'" type="hidden" '.$value.'/>';
				if (@$item['html']){
					$out.=' <button class="btn btn-primary'.$classes.'" name="'.$renderName.'" '.$attributes.$value.'>'.$item['value'].'</button>';
				}else{
					$out.=' <input class="btn btn-primary'.$classes.'" name="'.$renderName.'" type="'.$item['type'].'" '.$attributes.$value.'/>';
				}
				break;
			case 'boolean':
			case 'check':
			case 'checkbox':
				if (core::$data['render']=='bootstrap4'){
					$out.='<input type="checkbox" class="form-check-input '.$classes.'" value=1 name="'.$renderName.'" '.$attributes.(@$item['value']?'checked':'').'/><label class="form-check-label" for="'.$item['attributes']['id'].'">'.$item['label'].'</label>';
				}else{
					$out.='<div class="checkbox"><label><input type="checkbox" class="'.$classes.'" value=1 name="'.$renderName.'" '.$attributes.(@$item['value']?'checked':'').'/>'.$item['label'].'</label></div>';
				}
				break;
			case 'select':
				// multiple values
				$multiple=!empty($item['multiple']);
				if ($item['render']=='auto'){
					if (sizeof($item['values'])>10 || (!$required && !$multiple)){
						$item['render']='select';
					}elseif (@$this->prop['type']=='form-inline' || sizeof($item['values'])>5){
						if ($multiple){
							$item['render']='check';
						}else{
							$item['render']='radio';
						}
					}else{
						if ($multiple){
							$item['render']='check-inline';
						}else{
							$item['render']='radio-inline';
						}
					}
				}

				if ($multiple){
					if (is_string($item['value']))$item['value']=array($item['value']);
					$valArr=array_flip($item['value']);
				}

				switch ($item['render']){
					case 'select2':
						mvc::addComponent('select2');
						mvc::addCss('select2_bootstrap');
						if (!empty($item['ajax'])){
							$out.='<input class="form-control select2-remote'.$classes.'" name="'.$renderName.'" type="hidden" '.$attributes.$value.$required.'/>';
							break;
						}else{
							$item['render']='select';
						}
						// add script to select2 element
						// no break as render=select
					case 'select':
						$out.='<select class="form-control'.$classes.'" name="'.$renderName.($multiple?'[]':'').'"'.($multiple?' multiple':'').' '.$attributes.$required.'>';
						if ($required){
							if (!isset($item['values'][0]))$out.='<option disabled selected style="display: none;" value="">'.(@$item['placeholder']?input::htmlspecialchars($item['placeholder']):'').'</option>';
						}else{
							if (!isset($item['values'][0]))$out.='<option value="">'.(@$item['placeholder']?input::htmlspecialchars(@$item['placeholder']):'').'</option>';
						}
						if ($multiple){
							foreach ($item['values'] as $key=>$val){
								$out.='<option value="'.input::htmlspecialchars($key).'"'.(isset($valArr[$key])?' selected':'').'>'.input::htmlspecialchars($val).'</option>';
							}
						}else{
							foreach ($item['values'] as $key=>$val){
								$out.='<option value="'.input::htmlspecialchars($key).'"'.($renderValue==$key?' selected':'').'>'.input::htmlspecialchars($val).'</option>';
							}
						}
						$out.='</select>';
						break;
					case 'check':
						if ($multiple){
							foreach ($item['values'] as $key=>$val){
								$out.='<div class="checkbox"><label><input '.$attributes.' name="'.$renderName.'[]"'.(isset($valArr[$key])?' checked':'').' type="checkbox" value="'.input::htmlspecialchars($key).'">'.($item['html']?$val:input::htmlspecialchars($val)).'</label></div>';
							}
						}else{
							foreach ($item['values'] as $key=>$val){
								$out.='<div class="checkbox"><label><input '.$attributes.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="checkbox" value="'.input::htmlspecialchars($key).'">'.($item['html']?$val:input::htmlspecialchars($val)).'</label></div>';
							}
						}
						break;
					case 'radio':
						foreach ($item['values'] as $key=>$val){
							$out.='<div class="radio"><label><input '.$attributes.$required.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="radio" value="'.input::htmlspecialchars($key).'">'.(@$item['html']?$val:input::htmlspecialchars($val)).'</label></div>';
						}

						break;
					case 'check-inline':
					$out.='<div>';
					if ($multiple){
						foreach ($item['values'] as $key=>$val){
							$out.='<label class="checkbox-inline"><input '.$attributes.' name="'.$renderName.'[]"'.(isset($valArr[$key])?' checked':'').' type="checkbox" value="'.input::htmlspecialchars($key).'">'.($item['html']?$val:input::htmlspecialchars($val)).'</label>';
						}
					}else{
						foreach ($item['values'] as $key=>$val){
							$out.='<label class="checkbox-inline"><input '.$attributes.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="checkbox" value="'.input::htmlspecialchars($key).'">'.($item['html']?$val:input::htmlspecialchars($val)).'</label>';
						}
					}
					$out.='</div>';
						break;
					case 'radio-inline':
					$out.='<div>';
					foreach ($item['values'] as $key=>$val){
						$out.='<label class="radio-inline"><input '.$attributes.$required.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="radio" value="'.input::htmlspecialchars($key).'">'.(@$item['html']?$val:input::htmlspecialchars($val)).'</label>';
					}
					$out.='</div>';
						break;
					case 'fancytree':
						mvc::addComponent('fancytree');
						$out.='<div class="fancytree '.$classes.'" '.$attributes.'></div>';
							// todo: but you need manually set script initialize `.fancytree` after form
							/*
							 * need add script
	if (typeof(after_load)=='undefined'){
	function after_load(){
	<?foreach($_GET['ft_1'] as $tree){?>$(".fancytree").fancytree("getTree").getNodeByKey('<?=$tree?>').setSelected(true);<? }?>
	<?if (isset($_GET['ft_1_active'])){?>$(".fancytree").fancytree("getTree").activateKey("<?=$_GET['ft_1_active']?>");<? }?>
	}
	}
	$(".fancytree").fancytree({
	source: {
		url: "http://...",
		complete: after_load
	},
	minExpandLevel: 1,
	checkbox: true,
	selectMode: 3,
	aria: true

	});
							 $("form").submit(function () {
								$(".fancytree").fancytree("getTree").generateFormElements();
								});
							 */
							break;
					}

					break;
				case 'password':
					$out.='<input class="form-control'.$classes.'" name="'.$renderName.'" type="'.$item['type'].'" '.$attributes.$placeholder.$required.'/>';
					break;
				case 'file':
					$has_file=@$item['value'] && is_file($item['value']);
					$out.=($has_file?'<img src="'.$item['value'].'?time='.time().'" style="max-width: 100%;display: block;">':'').'<input name="'.$renderName.'" type="'.$item['type'].'" '.$attributes.$placeholder.($has_file?'':$required).'/>'.(isset($item['fill']) && is_callable($item['fill'])?$item['fill']($this->getData()):'');
					break;
				case 'capcha':
					$out.='<img src="'.$item['url'].'" style="max-width: 100%;display: block;"><input class="form-control'.$classes.'" name="'.$renderName.'" type="text" '.$attributes.$placeholder.$required.'/>';
					break;
				case 'date':
				case 'datetime':
				case 'time':
					if ($item['render']=='auto')$item['render']='jqueryui';
					if ($item['render']=='jqueryui'){
						mvc::addComponent('jqueryui');
						if (@$item['range']){
							if ($this->prop['type']=='form-inline'){
								$out.='<label>'.$item['range_before_text'].'</label> <input name="'.$renderName.'[min]" type="text" class="form-control '.$item['type'].'picker'.$classes.'" '.$attributes.$placeholder.$required.$value['min'].'/>';
								if (isset($item['ico']))$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
//var_dump($attributes);
								$out.=' <label>'.$item['range_to_text'].'</label> <input name="'.$renderName.'[max]" type="text" class="form-control '.$item['type'].'picker'.$classes.'" '.str_replace('id=', 'id2=', $attributes).$placeholder.$required.$value['max'].'/>';
								if (isset($item['ico']))$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
							}else{
								$out.='<div class="row"><div class="col-xs-6">';
								$out.=$item['range_before_text'].' <input name="'.$renderName.'[min]" type="text" class="form-control '.$item['type'].'picker'.$classes.'" '.$attributes.$placeholder.$required.$value['min'].'/>';
								if (isset($item['ico']))$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
								$out.='</div><div class="col-xs-6">';
								$out.=$item['range_to_text'].' <input name="'.$renderName.'[max]" type="text" class="form-control '.$item['type'].'picker'.$classes.'" '.str_replace('id=', 'id2=', $attributes).$placeholder.$required.$value['max'].'/>';
								if (isset($item['ico']))$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
								$out.='</div></div>';
							}
						}else{
							$out.='<input name="'.$renderName.'" type="text" class="form-control '.$item['type'].'picker'.$classes.'" '.$attributes.$placeholder.$value.$required.'/>';
						}
					}

					break;
				case 'static':
					// static plant text
					$out.='<p class="form-control-static'.$classes.'">'.input::htmlspecialchars($item['value']).'</p><input name="'.$name.'" type="hidden" '.$attributes.$value.'/>';
					break;
				case 'color':
					if ($item['render']=='pick-a-color'){
						mvc::addComponent('pick-a-color');
						$out.='<input name="'.$renderName.'" type="text" class="form-control pick-a-color '.$classes.'" '.$attributes.$required.$value.'/>';
						break;
					}
				default:
					// text field
					if ($item['render']=='auto' && isset($item['validate'])){
						foreach ($item['validate'] as $validator){
							if ($validator['type']=='maxlength' && $validator['value']>150){
								$item['render']='textarea';
								break;
							}
						}
					}
					switch($item['render']){
						case 'div':
						$out.='<div class="component">';
						if ($item['range']){
								$out.='<input name="'.$renderName.'[min]" type="hidden" '.$value['min'].'/>';
								$out.='<input name="'.$renderName.'[max]" type="hidden" '.$value['max'].'/>';
							}else{
								$out.='<input name="'.$renderName.'" type="hidden" '.$value.'/>';
							}
							$out.='<div class="'.$classes.'" '.$attributes.'></div>';
					$out.='</div>';

					/* for slider
	var c_adr_dom_min=$('#c_adr_dom').parent().find('input').eq(0);
	var c_adr_dom_max=$('#c_adr_dom').parent().find('input').eq(1);
	$('#c_adr_dom').slider({
		range: true,
		min: 0,
		max: 100,
		values: [c_adr_dom_min.val(),c_adr_dom_max.val()],
		slide: function (event,ui){
			c_adr_dom_min.val(ui.values[0]);
			c_adr_dom_max.val(ui.values[1]);
		}

	})
					 */

					break;
						case 'tinymce':
							mvc::addJs('tinymce');
							$out.='<textarea name="'.$renderName.'" class="form-control tinymce'.$classes.'" '.$attributes.$placeholder.'>'.input::htmlspecialchars(@$item['value']).'</textarea>';
							// todo: but you need manually set script initialize `.tinymce` after form
							break;
						case 'ckeditor':
							mvc::addJs('ckeditor');
							mvc::addJs('ckeditor_adapter');
							$out.='<textarea name="'.$renderName.'" class="form-control ckeditor'.$classes.'" '.$attributes.$placeholder.'>'.input::htmlspecialchars(@$item['value']).'</textarea>';
							// todo: but you need manually set script initialize `.ckeditor` after form
							break;
						case 'textarea':
							$out.='<textarea name="'.$renderName.'" class="form-control'.$classes.' '.($this->prop['type']=='md-form'?'md-textarea':'').'" '.$attributes.$placeholder.$required.'>'.input::htmlspecialchars(@$item['value']).'</textarea>';
							break;
						default :
							if (@$item['range']){
								$out.='<div class="row"><div class="col-xs-6">';
								$out.='<input name="'.$renderName.'[min]" type="'.$item['type'].'" class="form-control'.$classes.'" '.$attributes.$placeholder.$required.$value['min'].'/>';
								if (isset($item['ico']))$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
								$out.='</div><div class="col-xs-6">';
								$out.='<input name="'.$renderName.'[max]" type="'.$item['type'].'" class="form-control'.$classes.'" '.str_replace('id=', 'id2=', $attributes).$placeholder.$required.$value['max'].'/>';
								if (isset($item['ico']))$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
								$out.='</div></div>';
							}else{
								$out.='<input name="'.$renderName.'" type="'.$item['type'].'" class="form-control'.$classes.'" '.$attributes.$placeholder.$required.$value.'/>';
							}
					}
			}
			if (@!$item['range'] && isset($item['ico']) && $this->prop['type']!='md-form')$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
			
			$out.=$this->inputGroupPostfix($item);
			$datalist='';
		if (isset($item['datalist'])){
			$datalist='<p class="help-block">';
			foreach($item['datalist'] as $key=>$dl){
				$datalist.='<a href="javascript:;" style="border-bottom: 1px dashed blue;margin: 5px;" onclick="$(this).closest(\'.form-group\').find(\':enabled\').val(\''.$key.'\')">'.$dl.'</a>';
			}
			$datalist.='</p>';
		}
		if (isset($item['helper']))$datalist.='<p class="help-block">'.(@$item['helperHTML']?$item['helper']:input::htmlspecialchars($item['helper'])).'</p>';
		return $out.$datalist;
	}
	function getFormDescription($key=''){
		$listfields=array();
		foreach ($this->fields as $k=>$value){
			$item=array('name'=>isset($value['label_full'])?$value['label_full']:$value['label'],'values'=>$value['values']);
			if (isset($value['name'])){
				if (is_string($value['name'])){
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