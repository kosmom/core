<?php
namespace c;

/**
 * Form and render table
 * @author Kosmom <Kosmom.ru>
 */
class table{
	var $header_render_callback;
	var $header;
	var $data;
	var $fill;
	var $group;
	var $groupFooter;
	var $attributes=array();
	var $classes=array('table');
	var $row_attributes=false;
	var $cell_attributes=false;
	var $header_attributes=false;
	var $headerTotalHtml=false;
	private $preparedData=[];
	/**
	 * Set table responsives
	 * @param string $responsive_border xxs, xs, sm, md
	 */
	var $responsive=false;
	/**
	 * sticky or not header
	 * @var boolean
	 */
	var $sticky=false;
	/**
	 * Show header
	 * @var false|true|'join'
	 */
	var $show_header=true;
	/**
	 * Draw header if not set data
	 * @var boolean
	 */
	var $draw_if_empty=false;
	private $callableAttributes=array(); //column-attribute-callable
	private $lastGroupState=array();
	private $lastGroupFooterState=array();

	function __construct($data=null,$header=array()){
		$this->data=$data;
		$this->header=$header;
	}
	function sort($default='',$field=''){
		if ($field == '') $field=$_GET['sort'];
		if (empty($this->header[$field]['sort'])) return $default;
		return $field;
	}
	function order($field=''){
		return tables::order($field);
	}
	function is_data(){
		if ($this->data instanceof collection or $this->data instanceof model)return $this->data->count();
		if (is_array($this->data))return sizeof($this->data);
		return $this->preparedData=db::fa($this->data); // db query
	}
	function render($input=array(),$empty_callback=null){
		if (!isset($input['draw_if_empty'])) $input['draw_if_empty']=$this->draw_if_empty;
		if (!isset($input['responsive'])) $input['responsive']=$this->responsive;
		if (!isset($input['sticky'])) $input['sticky']=$this->sticky;
		if (!isset($input['fill'])) $input['fill']=$this->fill;
		if (!isset($input['header_render_callback'])) $input['header_render_callback']=$this->header_render_callback;
		if (isset($input['row_attributes']) && is_array($this->row_attributes)){
			$input['row_attributes']+=$this->row_attributes;
		}elseif (!isset($input['row_attributes'])){
			$input['row_attributes']=$this->row_attributes;
		}
		if (!$input['draw_if_empty'] && !$this->is_data()){
			return (is_callable($empty_callback))?$empty_callback():$empty_callback;
		}
		if (!isset($input['attributes'])) $input['attributes']=$this->attributes;
		if (!isset($input['classes'])) $input['classes']=$this->classes;
		if (!isset($input['show_header'])) $input['show_header']=$this->show_header;
		if ($input['attributes'] && is_array($input['attributes'])){
			$attributes=array();
			foreach($input['attributes'] as $key=> $val){
				$attributes[]=$key.'="'.input::htmlspecialchars($val).'"';
			}
			$attributes=' '.implode(' ',$attributes);
		}else{
			$attributes='';
		}
		if ($input['responsive']){
			mvc::addCss('responsive-tables');
			if (is_string($input['classes'])){
				$input['classes'].=' '.$input['responsive'].'-no-table';
			}elseif (is_array($input['classes'])){
				$input['classes'][]=$input['responsive'].'-no-table';
			}
		}
		if ($input['sticky']){
			mvc::addJs('sticky-tables');
			if (is_string($input['classes'])){
				$input['classes'].=' '.'table-sticky';
			}elseif (is_array($input['classes'])){
				$input['classes'][]='table-sticky';
			}
			//$this->addClass('table-sticky');
		}
		$out='<table'.($input['classes']?' class="'.(is_array($input['classes'])?implode(' ',$input['classes']):$input['classes']).'"':'').$attributes.'>';
		if ($input['show_header']) $out.=$this->renderHeader($input);
		return $out.$this->renderBody($input).'</table>';
	}
	/**
	 * @deprecated since version 3.4
	 */
	function add_class($class){
		return $this->addClass($class);
	}
	function addClass($class){
		if (is_string($this->classes)){
			$this->classes.=' '.$class;
		}elseif (is_array($this->classes)){
			$this->classes[]=$class;
		}
		return $this;
	}
	private function headerPrepare($header){
		$need_sort=false;
		foreach($header as &$item){
			if (is_string($item)){
				$item=array('name'=>$item);
				continue;
			}
			if (isset($item[0]) && !isset($item['name']))$item['name']=$item[0];
			if (isset($item['position']))$need_sort=true;
		}
		if (!$need_sort)return $header;
			datawork::stable_uasort($header,array('c\\datawork','positionCompare'));
		return $header;
	}

	/**
	 * @deprecated since version 3.4
	 */
	function render_header($input=array()){
		return $this->renderHeader($input);
	}
	function renderHeader($input=array()){
		if (!isset($input['header'])) $input['header']=$this->header;
		if (core::$debug) if (empty($input['header'])) debug::trace('Table header is not set',error::WARNING);
		if ($this->headerTotalHtml)return $this->headerTotalHtml;
		$input['header']=$this->headerPrepare($input['header']);
		$hasSubheader=false;
		foreach($input['header'] as $item){
			if (!isset($item['header']))continue;
			$hasSubheader=true;
			break;
		}
		foreach($input['header'] as $key=> $h){
			if (isset($h['header_attributes'])){
				if (!is_array(@$input['header_attributes'][$key]))$input['header_attributes'][$key]=array();
				$input['header_attributes'][$key]+=$h['header_attributes'];
			}
			if (isset($h['attributes'])){
				if (!is_array(@$input['cell_attributes'][$key]))$input['cell_attributes'][$key]=array();
				$input['cell_attributes'][$key]+=$h['attributes'];
			}
		}
		unset($item);
		if ($input['show_header'] == 'join'){
			// transformations with colspan
			$lastVal=null;
			$lastKey=null;
			foreach($input['header'] as $key=>$item){
				$name=$this->drawCellLabel($item);
				if ($name === $lastVal){
					unset($input['header'][$key]);
					if (isset($input['cell_attributes'][$lastKey]['colspan'])){
						$input['cell_attributes'][$lastKey]['colspan']++;
					}else{
						$input['cell_attributes'][$lastKey]['colspan']=2;
					}
				}else{
					$lastVal=$name;
					$lastKey=$key;
				}
			}
		}
		$out='<thead><tr>';
		$h=array();
		
		if ($hasSubheader){
			// transformations with colspan
			$lastVal=0;
			$lastKey=0;
			foreach($input['header'] as $key=> $item){
				if (isset($item['header']) && $item['header'] === $lastVal){
					if (isset($h[$lastKey])){
						$h[$lastKey]++;
					}else{
						$h[$lastKey]=2;
					}
				}else{
					$lastVal=@$item['header'];
					$lastKey=$key;
					$h[$lastKey]=1;
				}
			}
			foreach($input['header'] as $key=> $item){
				$lastHeader=null;
				if (empty($item['header'])){
					$input['cell_attributes'][$key]['rowspan']=2;
					$out.='<th'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$input['header'],$input):'').'>'.$this->drawSortTag($input,$key,$item).'</th>';
					$lastHeader=null;
				}else{
					if ($lastHeader === $item['header'] or empty($h[$key])) continue;
					$out.='<th colspan="'.$h[$key].'" '.(isset($input['header_attributes'][$key])?' '.$this->drawHeaderAttributes($key,$input['header'],$input):'').'>'.$item['header'].'</th>';
					$lastHeader=$item['header'];
				}
			}
			$out.='</tr><tr>';
			foreach($input['header'] as $key=> $item){
				if (empty($item['header'])) continue;
				$out.='<th'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$input['header'],$input):'').'>'.$this->drawSortTag($input,$key,$item).'</th>';
			}
		}else{
			foreach($input['header'] as $key=> $item){
				$out.='<th'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$input['header'],$input):'').'>'.$this->drawSortTag($input,$key,$item).'</th>';
			}
		}
		return $out.'</tr></thead>';
	}
	private function drawSortTag($input,$key,$item){
		$rs=$this->drawCellLabel($item);
		if ($this->header_render_callback){
			$callback=$this->header_render_callback;
			$rs=$callback($rs);
		}elseif (@core::$data['table_header_render_callback']){
			$cb=core::$data['table_header_render_callback'];
			$rs=$cb($rs);
		}
		if (!empty($input['header'][$key]['sort'])) return '<a class="table-sort" href="'.input::getLink(array('sort'=>$key,'order'=>$_GET['order'] || $_GET['sort'] != $key?false:'desc')).'">'.$rs.'</a>';
		return $rs;
	}
	private function drawCellLabel($item){
		if (isset($item['label']))return $item['label'];
		return @$item['name'];
	}
	private function drawCellValueWithCallback($key,$row,$input){
		if (isset($input['fill'][$key])){
			$cb=$input['fill'][$key];
			return $cb($row,$key);
		}
		return @$this->drawCellValue($row instanceof model?$row->$key:$row[$key],$key);
	}
	
	private function drawCell($key,$row,$input){
		$res=$this->drawCellValueWithCallback($key,$row,$input);
		return '<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$res.'</td>';
	}
	private function drawCellAttributes($cell,$row,$input){
		$out=array();
		foreach($input['cell_attributes'][$cell] as $attribute=> $callback){
			$out[]=$attribute.'="'.input::htmlspecialchars(is_callable($callback)?@$callback($row,$cell):$callback).'"';
		}
		return implode(' ',$out);
	}
	private function drawHeaderAttributes($cell,$row,$input){
		$out=array();
		foreach($input['header_attributes'][$cell] as $attribute=> $callback){
			$out[]=$attribute.'="'.input::htmlspecialchars(is_callable($callback)?$callback($row,$cell):$callback).'"';
		}
		return implode(' ',$out);
	}
	private function setCallableAttributes($cell_attributes){
		$this->callableAttributes=array();
		foreach($cell_attributes as $cell=> $attributes){
			foreach($attributes as $attribute=> $val){
				if (is_callable($val)) $this->callableAttributes[$cell][$attribute]=true;
			}
		}
	}
	private function drawGroup($input,$row){
		if (!isset($input['group'])) return '';
		$lastGroupState=array();
		$reset=false;
		$out='';
		foreach($input['group'] as $groupKey=> $item){
			if ($reset) $this->lastGroupState[$groupKey]='';
			$lastGroupState[$groupKey]=(isset($item['field']))?$row[$item['field']]:$item['fill']($row);
			if ($lastGroupState[$groupKey] == $this->lastGroupState[$groupKey]) continue;
			$reset=true;

			$row_attributes=array();
			foreach($input['row_attributes'] as $key=> $val){
				$row_attributes[]=$key.'="'.input::htmlspecialchars(is_callable($val)?$val($row):$val).'"';
			}
			$attrs=array();
			foreach($item['cell_attributes'] as $attribute=> $callback){
				$attrs[]=$attribute.'="'.input::htmlspecialchars(is_callable($callback)?$callback($row):$callback).'"';
			}
			$attrs[]='colspan='.sizeof($input['header']);
			$out.='<tr '.implode(' ',$row_attributes).'><td '.implode(' ',$attrs).'>'.$lastGroupState[$groupKey].'</td></tr>';

			$this->lastGroupState[$groupKey]=$lastGroupState[$groupKey];
		}
		return $out;
	}
	private function drawGroupFooter($input,$row){
		if (!isset($input['groupFooter'])) return '';
		$lastGroupState=array();
		$reset=false;
		$out='';
		foreach($input['groupFooter'] as $groupKey=> $item){
			if ($reset) $this->lastGroupFooterState[$groupKey]='';
			$lastGroupState[$groupKey]=(isset($item['field']))?$row[$item['field']]:$item['fill']($row);
			if ($lastGroupState[$groupKey] == $this->lastGroupFooterState[$groupKey]) continue;
			$reset=true;

			$row_attributes=array();
			foreach($input['row_attributes'] as $key=> $val){
				$row_attributes[]=$key.'="'.input::htmlspecialchars(is_callable($val)?$val($row):$val).'"';
			}
			$attrs=array();
			foreach($item['cell_attributes'] as $attribute=> $callback){
				$attrs[]=$attribute.'="'.input::htmlspecialchars(is_callable($callback)?$callback($row):$callback).'"';
			}
			$attrs[]='colspan='.sizeof($input['header']);
			$out.='<tr '.implode(' ',$row_attributes).'><td '.implode(' ',$attrs).'>'.$lastGroupState[$groupKey].'</td></tr>';

			$this->lastGroupFooterState[$groupKey]=$lastGroupState[$groupKey];
		}
		return $out;
	}
	private function getAttributes($input,$row){
		$attributes=array();
		foreach($input['row_attributes'] as $key=> $val){
			$attributes[]=$key.'="'.input::htmlspecialchars(is_callable($val)?$val($row):$val).'"';
		}
		return $attributes;
	}
	private function cellHtmlConvert($value,$key){
		if (@$this->header[$key]['is_html'])return $value;
		return input::htmlspecialchars($value);
	}
	private function drawCellValue($value,$key){
		if (!is_array($this->header[$key]))return $this->cellHtmlConvert($value,$key);
		if (!isset($this->header[$key]['values']))return $this->cellHtmlConvert($value,$key);
		return @$this->header[$key]['values'][$value]?$this->header[$key]['values'][$value]:$this->cellHtmlConvert($value,$key);
	}
	/**
	 * @deprecated since version 3.4
	 */
	function render_body($input=array()){
		return $this->renderBody($input);
	}
	function renderBody($input=array()){
		if (!isset($input['cell_attributes'])) $input['cell_atributes']=$this->cell_attributes;
		if (!isset($input['header'])) $input['header']=$this->header;
		if (!isset($input['group'])) $input['group']=$this->group;
		if (!isset($input['groupFooter'])) $input['groupFooter']=$this->groupFooter;
		$input['header']=$this->headerPrepare($input['header']);
		if ($input['responsive']){
			foreach($input['header'] as $key=> $h){
				$input['cell_attributes'][$key]['data-title']=$h['name'];
			}
		}
		// get header info
		foreach($input['header'] as $key=> $h){
			if (isset($h['attributes'])){
				if (!is_array(@$input['cell_attributes'][$key]))$input['cell_attributes'][$key]=array();
				$input['cell_attributes'][$key]+=$h['attributes'];
			}
			if (isset($h['fill']))$input['fill'][$key]=$h['fill'];
		}
		if (isset($input['cell_attributes']))$this->setCallableAttributes($input['cell_attributes']);
		if (!isset($input['row_attributes'])) $input['row_attributes']=$this->row_attributes;

		if (!isset($input['fill']))$input['fill']=$this->fill;
		foreach ($input['header'] as $key=>$h){ //needed 292?
			if (is_callable(@$h['fill']))$input['fill'][$key]=$h['fill'];
		}

		$row=array();
		$out='<tbody>';
		$lastrow=null;

		if (is_array($this->data)){
			if ($input['row_attributes']){
				if ($input['fill']){
					foreach($this->data as $key=>$row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';//.($key?$this->drawGroupFooter($input,$row):'');
						$lastrow=$row;
					}
				}else{
					foreach($this->data as $key=>$row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row[$key],$key).'</td>';
						}
						$out.='</tr>';//.($key?$this->drawGroupFooter($input,$row):'');
						$lastrow=$row;
					}
				}
			}else{
				if ($input['fill']){

					foreach($this->data as $key=>$row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';//.($key?$this->drawGroupFooter($input,$row):'');
						$lastrow=$row;
					}
				}else{
					foreach($this->data as $key=> $row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row[$key],$key).'</td>';
						}
						$out.='</tr>';
						$lastrow=$row;
					}
				}
			}
		}elseif ($this->data instanceof model or $this->data instanceof collection){ //empty($this->data) or 
			if ($input['row_attributes']){
				if ($input['fill']){
					foreach($this->data as $key=>$row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';//.($key?$this->drawGroupFooter($input,$row):'');
						$lastrow=$row;
					}
				}else{
					foreach($this->data as $key=>$row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row->$key,$key).'</td>';
						}
						$out.='</tr>';//.($key?$this->drawGroupFooter($input,$row):'');
						$lastrow=$row;
					}
				}
			}else{
				if ($input['fill']){

					foreach($this->data as $key=>$row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';//.($key?$this->drawGroupFooter($input,$row):'');
						$lastrow=$row;
					}
				}else{
					foreach($this->data as $key=> $row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row->$key,$key).'</td>';
						}
						$out.='</tr>';
						$lastrow=$row;
					}
				}
			}
		}else{
			if ($this->preparedData){
				$row=$this->preparedData;
				$this->preparedData=null;
			}
			$key=0;
			if ($input['row_attributes']){
				if ($input['fill']){
					if ($row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';
						$key++;
						$lastrow=$row;
					}
					while ($row=db::fa($this->data)){ // same
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';
						$key++;
						$lastrow=$row;
					}
				}else{
					if ($row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row[$key],$key).'</td>';
						}
						$out.='</tr>';
						$lastrow=$row;
						$key++;
					}
					while ($row=db::fa($this->data)){ // same
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr '.implode(' ',$this->getAttributes($input,$row)).'>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row[$key],$key).'</td>';
						}
						$out.='</tr>';
						$lastrow=$row;
						$key++;
					}

				}
			}else{
				if ($input['fill']){

					if ($row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';
						$lastrow=$row;
						$key++;
					}
					while ($row=db::fa($this->data)){ // same
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.=$this->drawCell($key,$row,$input);
						}
						$out.='</tr>';
						$lastrow=$row;
						$key++;
					}
				}else{
					if ($row){
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row[$key],$key).'</td>';
						}
						$out.='</tr>';
						$lastrow=$row;
						$key++;
					}
					while ($row=db::fa($this->data)){  //same
						$this->drawGroupFooter($input,$row);
						$out.=($key?$this->drawGroupFooter($input,$lastrow):'').$this->drawGroup($input,$row).'<tr>';
						foreach($input['header'] as $key=> $coll){
							$out.='<td'.(isset($input['cell_attributes'][$key])?' '.$this->drawCellAttributes($key,$row,$input):'').'>'.$this->drawCellValue(@$row[$key],$key).'</td>';
						}
						$out.='</tr>';
						$lastrow=$row;
						$key++;
					}
				}
			}
		}
		$this->lastGroupFooterState=array();
		return $out.$this->drawGroupFooter($input,$row).'</tbody>';
	}
	/**
	 * @deprecated since version 3.4
	 */
	function xlsx_writescheet($input=array()){
		return $this->xlsxWritescheet($input);
	}
	function xlsxWritescheet($input=array()){
		xlsx::writesheetFromTable($input,$this);
	}
	/**
	 * @deprecated since version 3.4
	 */
	function xlsx_generate($input=array(),$filename=''){
		return $this->xlsxGenerate($input,$filename);
	}
	function xlsxGenerate($input=array(),$filename='',$out=true){
		if (is_string($input) && $filename==''){
			$filename=$input;
			$input=array();
		}
		xlsx::writesheetFromTable($input,$this);
		return xlsx::generate($filename,$out);
	}
	/**
	 * Render table as raw text
	 * @param string $delimeterRow delimeter for rows
	 * @param string $delimeterCol delimeter for colls
	 * @param string $delimeterKeyVal delimeter for key => val render
	 * @param array $input parameters
	 * @return string
	 */
	function renderAsText($delimeterRow="\n\r",$delimeterCol="\n\r",$delimeterKeyVal=': ',$input=array()){
		$out=array();
		foreach ($this->renderAsArray($input) as $row){
			$rowTemp=[];
			foreach ($row as $key=>$cell){
				$rowTemp[]=$key.$delimeterKeyVal.$cell;
			}
			$out[]= implode($delimeterCol, $rowTemp);
		}
		return implode($delimeterRow,$out);
	}
	/**
	 * Render data as array with headers and fill parameters
	 * @param array $input parameters
	 * @return array
	 */
	function renderAsArray($input=array()){
		if (!isset($input['cell_attributes'])) $input['cell_atributes']=$this->cell_attributes;
		if (!isset($input['header'])) $input['header']=$this->header;
		$input['header']=$this->headerPrepare($input['header']);
		foreach($input['header'] as $key=> $h){
			if (isset($h['fill']))$input['fill'][$key]=$h['fill'];
		}
		$out=array();
		if (empty($this->data))return $out;
		if (is_array($this->data)){
			foreach($this->data as $key=>$row){
				$outRow=array();
				foreach($input['header'] as $key=> $coll){
					$outRow[$key]=$this->drawCellValueWithCallback($key,$row,$input);
				}
				$out[]=$outRow;
			}
		}else{
			while ($row=db::fa($this->data)){
				$outRow=array();
				foreach($input['header'] as $key=> $coll){
					$outRow[$key]=$this->drawCellValueWithCallback($key,$row,$input);
				}
				$out[]=$outRow;
			}
		}
		return $out;
	}
}