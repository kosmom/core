<?php
namespace c;

/**
 * Table static class
 * @author Kosmom <Kosmom.ru>
 */
class tables{
	static $header=array();
	static $data;
	static $fill;
	static $group;
	static $groupFooter;
	static $attributes=array();
	static $classes=array('table');
	static $row_attributes=false;
	static $cell_attributes=false;
	static $header_render_callback;
	/**
	 * Set table responsives
	 * @param string $responsive_border xxs, xs, sm, md
	 */
	static $responsive=false;
	static $sticky=false; // sticky or not header
	static $show_header=true; // false true or 'join'
	static $draw_if_empty=false; // false true

	static function addClass($class){
		if (\is_string(self::$classes)){
			self::$classes.=' '.$class;
		}elseif (\is_array(self::$classes)){
			self::$classes[]=$class;
		}
	}

	function __construct(){
		if (core::$debug)debug::trace('Create table with static class tables',error::WARNING);
	}
	static function sort($defaultField='',$field=''){
		if ($field=='')$field=$_GET['sort'];
		if (empty(self::$header[$field]['sort']))return $defaultField;
		return $field;
	}

	static function order($default_sort=null){
		if (isset($_GET['sort'])){
			$order=$_GET['order'];
			if ($order=='desc')return 'desc';
			return '';
		}
		if ($default_sort===null)return '';
		return $default_sort;
	}

	static function render($input=array(),$emptyCallback=null){
		if (core::$debug)if (empty($input)) debug::trace('Table rendered with empty var inputs',error::WARNING);
		if (!isset($input['draw_if_empty']))$input['draw_if_empty']=self::$draw_if_empty;
		if (!isset($input['responsive']))$input['responsive']=self::$responsive;
		if (!isset($input['sticky']))$input['sticky']=self::$sticky;
		if (!isset($input['fill'])) $input['fill']=self::$fill;
		if (!isset($input['group'])) $input['group']=self::$group;
		if (!isset($input['groupFooter'])) $input['groupFooter']=self::$groupFooter;
		if (!isset($input['header_render_callback'])) $input['header_render_callback']=self::$header_render_callback;
		if (isset($input['row_attributes']) && is_array(self::$row_attributes)){
			$input['row_attributes']+=self::$row_attributes;
		}elseif (!isset($input['row_attributes'])){
			$input['row_attributes']=self::$row_attributes;
		}
		if (!$input['draw_if_empty'] && !self::$data){
			if (is_callable($emptyCallback))return $emptyCallback();
			return $emptyCallback;
		}
		if (!isset($input['attributes']))$input['attributes']=self::$attributes;
		if (!isset($input['classes']))$input['classes']=self::$classes;
		if (!isset($input['show_header']))$input['show_header']=self::$show_header;
		$table=new table();
		if (!isset($input['header']))$table->header=self::$header;
		$table->data=self::$data;
		return $table->render($input);
	}
	static function renderAsArray($input=array()){
		if (!isset($input['fill'])) $input['fill']=self::$fill;
		if (!isset($input['attributes']))$input['attributes']=self::$attributes;
		$table=new table();
		if (!isset($input['header']))$table->header=self::$header;
		$table->data=self::$data;
		return $table->renderAsArray($input);
	}
	static function renderAsText($delimeterRow="\n\r",$delimeterCol="\n\r",$delimeterKeyVal=': ',$input=array()){
		if (!isset($input['fill'])) $input['fill']=self::$fill;
		if (!isset($input['attributes']))$input['attributes']=self::$attributes;
		$table=new table();
		if (!isset($input['header']))$table->header=self::$header;
		$table->data=self::$data;
		return $table->renderAsText($delimeterRow,$delimeterCol,$delimeterKeyVal,$input);
	}
	static function getTableObject(){
		$table=new table(self::$data,self::$header);
		$table->fill=self::$fill;
		$table->attributes=self::$attributes;
		$table->classes=self::$classes;
		$table->row_attributes=self::$row_attributes;
		$table->cell_attributes=self::$cell_attributes;
		return $table;
	}
	static function writesheetXlsx($input=array()){
		xlsx::writesheetFromTable($input);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function writesheet_xlsx($input=array()){
		xlsx::writesheetFromTable($input);
	}
}