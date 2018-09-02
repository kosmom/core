<?php
namespace c;

/**
 * Static form interface
 * @author Kosmom <Kosmom.ru>
 */
class forms{
	const FIELD_KEY='{FIELD_KEY}';
	static $forms=array();
	private static $activeForm='default';

	function __construct() {
		if (core::$debug)debug::trace('Create form with static class forms',error::WARNING);
	}
	
	private static function createForm($form){
		if (!isset(self::$forms[$form]))self::$forms[$form]=new form();
	}

        /**
         * Get form as object
         * @return form
         */
	static function getForm($form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form];
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function is_submit($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->isSubmit();
	}
	static function isSubmit($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->isSubmit();
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function render_field($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderField($name,$item);
	}

	static function renderField($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderField($name,$item);
	}
	static function renderFieldsByName($prefix,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldsByName($prefix);
	}
	static function renderFieldsByPosition($min=0,$max=999,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldsByPosition($min,$max);
	}

	static function classes($classes=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->classes($classes);
	}
	static function addClass($class,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->addClass($class);
	}

	static function attributes($attributes=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->attributes($attributes);
	}
	static function addAttribute($key,$value,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addAttribute($key,$value);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function render_field_label($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->render_field_label($name,$item);
	}
	static function renderFieldLabel($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldLabel($name,$item);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function render_field_field($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldField($name,$item);
	}
	static function renderFieldField($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldField($name,$item);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function render_field_form_group_begin($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldFormGroupBegin($name,$item);
	}
	static function renderFieldFormGroupBegin($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldFormGroupBegin($name,$item);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function render_field_form_group_end($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldFormGroupEnd($name,$item);
	}
	static function renderFieldFormGroupEnd($name,$item=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderFieldFormGroupEnd($name,$item);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function render_begin_tag($form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderBeginTag();
	}
	static function renderBeginTag($form=null){
		if ($form===null)$form=self::$activeForm;
		return self::$forms[$form]->renderBeginTag();
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function render_end_tag($form=null){
		return '</form>'; // for simplicity
		//if ($form===null)$form=self::$active_form;
		//return self::$forms[$form]->render_end_tag();
	}
	static function renderEndTag($form=null){
		return '</form>'; // for simplicity
		//if ($form===null)$form=self::$active_form;
		//return self::$forms[$form]->render_end_tag();
	}

	/**
	 * Add field to form stack with all arguments
	 * @param string $fieldKey field name
	 * @return form
	 * @example $fieldParams=array('name'=>array(
	 *								'label'=>'label',
	 *								'value'=>'value',
	 *								'tooltip'=>'tooltip',
	 *								'type'=>'text',
	 *								'render'=>'auto',
	 *								'readonly'=>true,
	 *								'attributes'=>array('id'=>'name'),
	 *								'filters'=>array('trim','lower'),
	 *								'validate'=>array(
	 *									array('type'=>'maxlength', 'value'=> 100,'level'=>'error','text'=>'Max length for label is 100 chars'),
	 *									array('type'=>'required'),
	 *									array('type'=>'pattern', 'value'=>'/^0{1,3}$/'),
	 *								)),
	 *						);
	 */
	static function addField($fieldKey,$fieldParams=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addField($fieldKey,$fieldParams);
	}
	static function addFieldAs($fieldKey,$asFieldKey,$fieldParams=array(),$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addFieldAs($fieldKey,$asFieldKey,$fieldParams);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function add_field($fieldKey,$fieldParams=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addField($fieldKey,$fieldParams);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_fields($fields,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addFields($fields);
	}
	/**
	 * @param array $fields form fields
	 * @param string $form
	 * @return form
	 */
	static function addFields($fields,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addFields($fields);
	}

	static function ajax($value=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->ajax($value);
	}

	/**
	 * Set form method GET or POST
	 * @param string $method
	 * @param string $form
	 * @return form
	 */
	static function method($method=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->method($method);
	}

	static function disabledReturn($false,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->disabledReturn($false);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function form_inline($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->formInline();
	}
	/**
	 * Set form draw mode to inline
	 * @param string $form
	 * @return form
	 */
	static function formInline($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->formInline();
	}
	/**
	 * Set form to horizontal view
	 * @param array $labelClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4)
	 * @param array|number $inputClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4) or number max cells in row
	 * @param string $form
	 * @deprecated since version 3.4
	 */
	static function form_horizontal($labelClassesArray,$inputClassesArray=12, $form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->formHorizontal($labelClassesArray,$inputClassesArray);
	}
	/**
	 * Set form to horizontal view
	 * @param array $labelClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4)
	 * @param array|number $inputClassesArray example: array('sm'=>2,'xs'=>4,'md'=>1,'lg'=>4) or number max cells in row
	 * @param string $form
	 */
	static function formHorizontal($labelClassesArray,$inputClassesArray=12, $form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->formHorizontal($labelClassesArray,$inputClassesArray);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function active_form($form=null){
		return self::activeForm($form);
	}
	/**
	 * Set or return active form
	 * @param string|null $form
	 * @return null|string
	 */
	static function activeForm($form=null){
		if ($form===null)return self::$activeForm;
		self::$activeForm=(string)$form;
	}

	/**
	 * Set key-val data to form
	 * @param array $data default - $_POST
	 * @param string $form
	 * @deprecated since version 3.4
	 */
	static function set_data($data=null,$force=false,$form=null){
		return self::setData($data,$force,$form);
	}
	/**
	 * Set key-val data to form
	 * @param array $data default - $_POST
	 * @param string $form
	 * @return form
	 */
	static function setData($data=null,$force=false,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->setData($data,true,$force);
	}
	static function setDataForce($data=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->setDataForce($data,true);
	}
	static function addData($data=null,$force=false,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addData($data,true,$force);
	}
	static function addDataForce($data=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addDataForce($data,true);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function fill_data($form=null){
		return self::fillData($form);
	}
	static function fillData($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->fillData();
	}

	/**
	 * Get key-val data from form
	 * @param string $form
	 * @return array
	 * @deprecated since version 3.4
	 */
	static function get_data($form=null){
		return self::getData($form);
	}
	/**
	 * Get key-val data from form
	 * @param string $form
	 * @return array
	 */
	static function getData($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->getData();
	}
	/**
	 *  Check form values to valid
	 * @deprecated since version 3.4
	 */
	static function is_valid($data=array(),$checkData=false,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->isValid($data,$checkData);
	}
	/**
	 *  Check form values to valid
	 */
	static function isValid($data=array(),$checkData=false,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->isValid($data,$checkData);
	}
	/**
	 *  Check form values to invalid
	 */
	static function isInvalid($data=array(),$checkData=false,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->isInvalid($data,$checkData);
	}
	/**
	 * Render full form
	 * @param string $form form name
	 * @return string html text
	 */
	static function render($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->render();
	}
	static function renderOtherFields($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->renderOtherFields();
	}

	static function addSubmitField($params=array(),$fieldKey=null,$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->addSubmitField($params,$fieldKey);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function render_as_table($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->renderAsTable();
	}
	static function renderAsTable($form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->renderAsTable();
	}
	static function getFormDescription($key='',$form=null){
		if ($form===null)$form=self::$activeForm;
		self::createForm($form);
		return self::$forms[$form]->getFormDescription($key);
	}
}