<?php
namespace c\factory;

class form_bootstrap{
	private $form;
	private $name;

	function __construct($form,$name) {
		$this->form=$form;
		$this->name=$name;
	}
	function renderField($item,$renderName){
		if ($item['type']=='none')return '';
		if (!isset($item['attributes']['id']))$item['attributes']['id']='core_form_field_'.\c\form::$fieldCounter++;
		$out=$this->renderFieldFormGroupBegin($item);
		if (@!$item['range'] && isset($item['ico']) && $this->form->prop['type']=='md-form')$out.= '<i class="'.$item['ico'].' prefix grey-text"></i>';
		if ($this->form->prop['type']=='md-form'){
				return $out.$this->renderFieldField($item,$renderName).$this->renderFieldLabel($item,$renderName).$this->renderFieldFormGroupEnd($item);
		}else{
				return $out.$this->renderFieldLabel($item).$this->renderFieldField($item,$renderName).$this->renderFieldFormGroupEnd($item);
		}
	}

	function renderFieldFormGroupBegin($item){
		if (@$item['type']=='hidden')return '';
		if ($this->form->prop['type']=='form-inline' && $item['type']=='submit')return '';
		$baseGroup='form-group';
		if ($this->form->prop['type']=='md-form'){
			$baseGroup='md-form';
		}elseif (\c\core::$data['render']=='bootstrap4'){
			$baseGroup.=' row';
		}
		if (\c\core::$data['render']=='bootstrap4' && in_array($item['type'],array('checkbox','boolean','radio')))$baseGroup='form-check';
		if (isset($item['group_classes'])){
			if (is_callable($item['group_classes'])){
				$classes=$item['group_classes']($item,$this->form->getData());
			}else{
				$classes=$item['group_classes'];
			}

			if (is_string($classes))$classes=explode(' ',$classes);
			//array only
			$classes=array_merge(array($baseGroup),$classes);
		}else{
			$classes=array($baseGroup);
		}
		if (isset($item['ico']) && $this->form->prop['type']!='md-form')$classes[]='has-feedback';

		$attributes=array();
		if (isset($item['group_attributes'])){
			if (is_callable($item['group_attributes'])){
				$attributes=$item['group_attributes']($item,$this->form->getData());
			}elseif (is_array($item['group_attributes'])){
				foreach ($item['group_attributes'] as $key=>$attribute){
					if (is_callable($attribute)){
						$attributes[$key]=$attribute($item,$this->form->getData());
					}else{
						$attributes[$key]=$attribute;
					}
				}
			}
			if (is_string($attributes)){
				$attr= explode(' ', $attributes);
			}else{
				foreach ($attributes as $key=>$val){
					$attr[]=\c\input::htmlspecialchars($key).'="'.\c\input::htmlspecialchars($val).'"';
				}
			}
		}

		return ' <div class="'.implode(' ',$classes).'" '.(@$attr? implode(' ', $attr):'').'>';
	}
	function renderFieldFormGroupEnd($item){
		if ($item['type']=='hidden')return '';
		return (($this->form->prop['type']!='form-inline' || $item['type']!='submit')?'</div>':'').($this->form->prop['type']=='form-horizontal'?'</div>':'');
	}
	function renderFieldLabel($item){
		if (@$item['type']=='hidden')return '';
		if ($this->form->prop['type']!='form-inline' || @$item['type']!='submit')return (!empty($item['label']) && @$item['type']!='check' && @$item['type']!='boolean' && @$item['type']!='checkbox'?'<label '.($this->form->prop['type']=='md-form' && ($item['type']=='file' || $item['type']=='select')?'class="active"':'').' for="'.$item['attributes']['id'].'" '.$this->horizontalGetLabelClass().'>'.(@$item['label_html']?$item['label']:\c\input::htmlspecialchars($item['label'])).'</label> ':'').$this->horizontalGetColDiv($item);
		return '';
	}

	private function horizontalGetLabelClass(){
		if (@$this->form->prop['type']!='form-horizontal')return '';
		if (\c\core::$data['render']=='bootstrap4'){
			$out=' class="col-form-label';
		}else{
			$out=' class="control-label';
		}
		foreach ($this->form->prop['label_classes_array'] as $key=>$val){
			$out.=' col-'.$key.'-'.$val;
		}
		return $out.'"';
	}

	private function horizontalGetColDiv($field){
		if (@$this->form->prop['type']!='form-horizontal')return '';
		$classes=array();
		if (!@$field['label'] || @$field['type']=='check' || @$field['type']=='checkbox'|| @$field['type']=='boolean'){
			foreach ($this->form->prop['label_classes_array'] as $key=>$val){
				$classes[]='col-'.$key.'-offset-'.$val;
			}
		}
		foreach ($this->form->prop['input_classes_array'] as $key=>$val){
			$classes[]='col-'.$key.'-'.$val;
		}
		return '<div class="'.implode(' ',$classes).'">';
	}

	private function inputGroupPrefix($item){
		if (empty($item['prefix']) && empty($item['postfix']))return '';
		return '<div class="input-group">'.(!empty($item['prefix'])?'<span class="'.(\c\core::$data['render']=='bootstrap4'?'input-group-text':'input-group-addon').'">'.$item['prefix'].'</span>':'');
	}
	private function inputGroupPostfix($item){
		if (empty($item['prefix']) && empty($item['postfix']))return '';
		return (!empty($item['postfix'])?'<span class="'.(\c\core::$data['render']=='bootstrap4'?'input-group-text':'input-group-addon').'">'.$item['postfix'].'</span>':'').'</div>';
	}

	function renderFieldField($item,$renderName){
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
			$item['value']= str_replace(',', '.', @$item['value']);
		}
		$renderValue=isset($item['value'])?is_callable($item['value'])?$item['value']($item):$item['value']:@$item['default'];
		if (@$item['range']){
			$value['min']=(isset($renderValue['min'])?'value="'.\c\input::htmlspecialchars($renderValue['min']).'" ':'');
			$value['max']=(isset($renderValue['max'])?'value="'.\c\input::htmlspecialchars($renderValue['max']).'" ':'');
		}else{
			$value=($renderValue!=='' && is_string($renderValue))?'value="'.\c\input::htmlspecialchars($renderValue).'" ':'';
		}
		$placeholder='';
		if (isset($item['placeholder'])){
			$placeholder='placeholder="'.\c\input::htmlspecialchars($item['placeholder']).'" ';
			if ($item['render']!='select2')\c\mvc::addJs('placeholder');
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
			\c\mvc::addJs('inputmask');
		}

		if (@$item['disabled'])$item['attributes']['disabled']='disabled';
		if (@$item['readonly'])$item['attributes']['readonly']='readonly';
		$attributes='';
		if (isset($item['attributes']) && is_array($item['attributes'])){
				$attributes=array();
			foreach($item['attributes'] as $key=>$val){
				$attributes[]=$key.'="'.\c\input::htmlspecialchars($val).'"';
			}
			$attributes=implode(' ',$attributes).' ';
		}
		$classes='';
		if (isset($item['classes'])){
			if (is_array($item['classes'])){
				foreach($item['classes'] as $key=>$val){
					$classes.=' '.\c\input::htmlspecialchars($val);
				}
			}else{
				$classes.=' '.\c\input::htmlspecialchars($item['classes']);
			}
		}
		$arg=array('name'=>$renderName,'attributes'=>$attributes,'value'=>$value,'classes'=>$classes,'multiple'=>false,'placeholder'=>$placeholder);
		if (isset(\c\core::$data['form_render'][@$item['type']])){
			if (is_callable(\c\core::$data['form_render'][$item['type']])){
				$a=\c\core::$data['form_render'][$item['type']];
				$a=$a($item,$arg);
				if (is_string($a))return $a;
				$item=$a;
			}elseif (is_callable(\c\core::$data['form_render'][$item['type']][$item['render']])){
				$a=\c\core::$data['form_render'][$item['type']][$item['render']];
				$a=$a($item,$arg);
				if (is_string($a))return $a;
				$item=$a;
			}
		}
		if (is_callable($item['render']))return $item['render']($item,$arg);
		if (@$item['type']=='hidden')return '<input'.($classes?' class="'.$classes.'"':'').' name="'.$renderName.'" type="'.$item['type'].'" '.$attributes.$value.'/>';
		
		$out=$this->inputGroupPrefix($item);
		
		switch ($item['type']){
			case 'submit':
				if ($this->form->ajax())$out.='<input name="'.$renderName.'" type="hidden" '.$value.'/>';
				if (@$item['html']){
					$out.=' <button class="btn btn-primary'.$classes.'" name="'.$renderName.'" '.$attributes.$value.'>'.$item['value'].'</button>';
				}else{
					$out.=' <input class="btn btn-primary'.$classes.'" name="'.$renderName.'" type="'.$item['type'].'" '.$attributes.$value.'/>';
				}
				break;
			case 'boolean':
			case 'check':
			case 'checkbox':
				if (\c\core::$data['render']=='bootstrap4'){
					$out.='<input type="checkbox" class="form-check-input '.$classes.'" value=1 name="'.$renderName.'" '.$attributes.(@$item['value']?'checked':'').'/><label class="form-check-label" for="'.$item['attributes']['id'].'">'.$item['label'].'</label>';
				}else{
					$out.='<div class="checkbox"><label><input type="checkbox" class="'.$classes.'" value=1 name="'.$renderName.'" '.$attributes.(@$item['value']?'checked':'').'/>'.$item['label'].'</label></div>';
				}
				break;
			case 'select':
				$multiple=!empty($item['multiple']);
				if ($item['render']=='auto'){
					if (sizeof($item['values'])>10 || (!$required && !$multiple)){
						$item['render']='select';
					}elseif ($this->form->prop['type']=='form-inline' || sizeof($item['values'])>5){
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

				if ($multiple && isset($item['value'])){
					if (is_string($item['value']))$item['value']=array($item['value']);
					$valArr=array_flip($item['value']);
				}

				switch ($item['render']){
					case 'select2':
						\c\mvc::addComponent('select2');
						\c\mvc::addCss('select2_bootstrap');
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
							if (!isset($item['values'][0]))$out.='<option disabled selected style="display: none;" value="">'.(@$item['placeholder']?\c\input::htmlspecialchars($item['placeholder']):'').'</option>';
						}else{
							if (!isset($item['values'][0]))$out.='<option value="">'.(@$item['placeholder']?\c\input::htmlspecialchars(@$item['placeholder']):'').'</option>';
						}
						if ($multiple){
							foreach ($item['values'] as $key=>$val){
								$out.='<option value="'.\c\input::htmlspecialchars($key).'"'.(isset($valArr[$key])?' selected':'').'>'.\c\input::htmlspecialchars($val).'</option>';
							}
						}else{
							foreach ($item['values'] as $key=>$val){
								$out.='<option value="'.\c\input::htmlspecialchars($key).'"'.((string)$renderValue===(string)$key?' selected':'').'>'.\c\input::htmlspecialchars($val).'</option>';
							}
						}
						$out.='</select>';
						break;
					case 'check':
						if ($multiple){
							foreach ($item['values'] as $key=>$val){
								$out.='<div class="checkbox"><label><input '.$attributes.' name="'.$renderName.'[]"'.(isset($valArr[$key])?' checked':'').' type="checkbox" value="'.\c\input::htmlspecialchars($key).'">'.(@$item['html']?$val:\c\input::htmlspecialchars($val)).'</label></div>';
							}
						}else{
							foreach ($item['values'] as $key=>$val){
								$out.='<div class="checkbox"><label><input '.$attributes.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="checkbox" value="'.\c\input::htmlspecialchars($key).'">'.(@$item['html']?$val:\c\input::htmlspecialchars($val)).'</label></div>';
							}
						}
						break;
					case 'radio':
						foreach ($item['values'] as $key=>$val){
							$out.='<div class="radio"><label><input '.$attributes.$required.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="radio" value="'.\c\input::htmlspecialchars($key).'">'.(@$item['html']?$val:\c\input::htmlspecialchars($val)).'</label></div>';
						}

						break;
					case 'check-inline':
					$out.='<div>';
					if ($multiple){
						foreach ($item['values'] as $key=>$val){
							$out.='<label class="checkbox-inline"><input '.$attributes.' name="'.$renderName.'[]"'.(isset($valArr[$key])?' checked':'').' type="checkbox" value="'.\c\input::htmlspecialchars($key).'">'.(@$item['html']?$val:\c\input::htmlspecialchars($val)).'</label>';
						}
					}else{
						foreach ($item['values'] as $key=>$val){
							$out.='<label class="checkbox-inline"><input '.$attributes.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="checkbox" value="'.\c\input::htmlspecialchars($key).'">'.(@$item['html']?$val:\c\input::htmlspecialchars($val)).'</label>';
						}
					}
					$out.='</div>';
						break;
					case 'radio-inline':
					$out.='<div>';
					foreach ($item['values'] as $key=>$val){
						$out.='<label class="radio-inline"><input '.$attributes.$required.' name="'.$renderName.'"'.($renderValue==$key?' checked':'').' type="radio" value="'.\c\input::htmlspecialchars($key).'">'.(@$item['html']?$val:\c\input::htmlspecialchars($val)).'</label>';
					}
					$out.='</div>';
						break;
					case 'fancytree':
						\c\mvc::addComponent('fancytree');
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
					$out.='<input class="form-control'.$classes.'" name="'.$renderName.'" type="password" '.$attributes.$placeholder.$required.'/>';
					break;
				case 'file':
					$multiple=!empty($item['multiple']);
					$has_file=@$item['value'] && is_file($item['value']);
					$out.=($has_file?'<img src="'.$item['value'].'?time='.time().'" style="max-width: 100%;display: block;">':'').'<input  name="'.$renderName.($multiple?'[]':'').'"'.($multiple?' multiple':'').' type="file" '.$attributes.$placeholder.($has_file?'':$required).'/>'.(isset($item['fill']) && is_callable($item['fill'])?$item['fill']($this->form->getData()):'');
					break;
				case 'capcha':
					$out.='<img src="'.$item['url'].'" style="max-width: 100%;display: block;"><input class="form-control'.$classes.'" name="'.$renderName.'" type="text" '.$attributes.$placeholder.$required.'/>';
					break;
				case 'date':
				case 'datetime':
				case 'time':
					if ($item['render']=='auto')$item['render']='jqueryui';
					if ($item['render']=='jqueryui'){
						\c\mvc::addComponent('jqueryui');
						if (@$item['range']){
							if ($this->form->prop['type']=='form-inline'){
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
					$out.='<p class="form-control-static'.$classes.'">'.\c\input::htmlspecialchars($item['value']).'</p><input name="'.$name.'" type="hidden" '.$attributes.$value.'/>';
					break;
				case 'color':
					if ($item['render']=='pick-a-color'){
						\c\mvc::addComponent('pick-a-color');
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
							\c\mvc::addJs('tinymce');
							$out.='<textarea name="'.$renderName.'" class="form-control tinymce'.$classes.'" '.$attributes.$placeholder.'>'.\c\input::htmlspecialchars(@$item['value']).'</textarea>';
							// todo: but you need manually set script initialize `.tinymce` after form
							break;
						case 'ckeditor':
							\c\mvc::addJs('ckeditor');
							\c\mvc::addJs('ckeditor_adapter');
							$out.='<textarea name="'.$renderName.'" class="form-control ckeditor'.$classes.'" '.$attributes.$placeholder.'>'.\c\input::htmlspecialchars(@$item['value']).'</textarea>';
							// todo: but you need manually set script initialize `.ckeditor` after form
							break;
						case 'textarea':
							$out.='<textarea name="'.$renderName.'" class="form-control'.$classes.' '.($this->form->prop['type']=='md-form'?'md-textarea':'').'" '.$attributes.$placeholder.$required.'>'.\c\input::htmlspecialchars(@$item['value']).'</textarea>';
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
			if (@!$item['range'] && isset($item['ico']) && $this->form->prop['type']!='md-form')$out.='<span aria-hidden="true" class="'.$item['ico'].' form-control-feedback"></span>';
			
			$out.=$this->inputGroupPostfix($item);
			$datalist='';
		if (isset($item['datalist'])){
			$datalist='<p class="help-block">';
			foreach($item['datalist'] as $key=>$dl){
				$datalist.='<a href="javascript:;" style="border-bottom: 1px dashed blue;margin: 5px;" onclick="$(this).closest(\'.form-group\').find(\':enabled\').val(\''.$key.'\')">'.$dl.'</a>';
			}
			$datalist.='</p>';
		}
		if (isset($item['helper']))$datalist.='<p class="help-block">'.(@$item['helperHTML']?$item['helper']:\c\input::htmlspecialchars($item['helper'])).'</p>';
		return $out.$datalist;
	}
}