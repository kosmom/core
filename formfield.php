<?php
namespace c;
class formfield extends arrayaccess{
	var $name;
	var $label;
	var $label_full;
	/**
	 * Is label to html
	 * @var boolean
	 */
	var $label_html;
	var $value;
	var $values;
	var $tooltip;
	var $type='text';
	var $render='auto';
	var $resolver='c\\factory\\form_bootstrap';
	var $ico;
	var $placeholder;
	var $group_classes;
	var $classes;
	/**
	 * Disabled field
	 * @var boolean
	 */
	var $disabled=false;
	/**
	 * Is readonly field
	 * @var boolean
	 */
	var $readonly=false;
	/**
	 * Is value to html
	 * @var boolean
	 */
	var $html;
	/**
	 * URL for capche
	 * @var string
	 */
	var $url;
	var $inputmask;
	var $subform;
	var $attributes;
	var $filters;
	var $helper;
	var $validate;
	/**
	 * Position priority element in form
	 * @var float
	 */
	var $position;
	var $default;
	/**
	 * Is element with range [min][max] keys
	 * @var float
	 */
	var $range;
	var $prefix;
	var $postfix;
	var $datalist;
	
	function setName($val){
		$this->name=$val;
		return $this;
	}
	function setLabel($val){
		$this->label=$val;
		return $this;
	}
	function setLabelFull($val){
		$this->label_full=$val;
		return $this;
	}
	function setLabelHtml($val){
		$this->label_html=$val;
		return $this;
	}
	function setValue($val){
		$this->value=$val;
		return $this;
	}
	function setValues($val){
		$this->values=$val;
		return $this;
	}
	function setTooltip($val){
		$this->tooltip=$val;
		return $this;
	}
	function setType($val){
		$this->type=$val;
		return $this;
	}
	function setRender($val){
		$this->render=$val;
		return $this;
	}
	function setResolver($val){
		$this->resolver=$val;
		return $this;
	}
	function setIco($val){
		$this->ico=$val;
		return $this;
	}
	function setPlaceholder($val){
		$this->placeholder=$val;
		return $this;
	}
	function setGroupClasses($val){
		$this->group_classes=$val;
		return $this;
	}
	function setClasses($val){
		$this->classes=$val;
		return $this;
	}
	function setDisabled($val){
		$this->disabled=$val;
		return $this;
	}
	function setReadonly($val){
		$this->readonly=$val;
		return $this;
	}
	function setHtml($val){
		$this->html=$val;
		return $this;
	}
	function setUrl($val){
		$this->url=$val;
		return $this;
	}
	function setInputmask($val){
		$this->inputmask=$val;
		return $this;
	}
	function setSubform($val){
		$this->subform=$val;
		return $this;
	}
	function setAttributes($val){
		$this->attributes=$val;
		return $this;
	}
	function setFilters($val){
		$this->filters=$val;
		return $this;
	}
	function setHelper($val){
		$this->helper=$val;
		return $this;
	}
	function setValidate($val){
		$this->validate=$val;
		return $this;
	}
	function setPosition($val){
		$this->position=$val;
		return $this;
	}
	function setDefault($val){
		$this->default=$val;
		return $this;
	}
	function setRange($val){
		$this->range=$val;
		return $this;
	}
	function setPrefix($val){
		$this->prefix=$val;
		return $this;
	}
	function setPostfix($val){
		$this->postfix=$val;
		return $this;
	}
	function setDatalist($val){
		$this->datalist=$val;
		return $this;
	}
	function addToForms($key,$form=null){
		return forms::addField($key, $this, $form);
	}
	function addToForm($form,$key){
		return $form->addField($key,$this);
	}
}