<?php
namespace c;

/**
 * Render errors containers class
 * @author Kosmom <Kosmom.ru>
 */
class render{

	private static $container=0;

	static function container($fluid=false){
		if (self::$container++)return;
		?><div class="container<?=$fluid?'-fluid':''?>"><?php
	}
	static function containerFluid(){
		return self::container(true);
	}
	static function containerClose(){
		if (!self::$container--)return;
		?></div><?php
	}
/**
 * @deprecated since version 3.4
 */
	static function show_errors($type='html'){
		return self::showAlerts($type);
	}
/**
 * @deprecated since version 3.5 use showAlerts method
 */
	static function showErrors($type='html'){
		return self::showAlerts($type);
	}
	static function showAlerts($type='html'){
		switch($type){
			case 'html':
				if (error::count()){?><div class='alert alert-danger'><?php foreach(error::errors() as $item){?><div><?=$item?></div><?php }?></div><?php }
				if (error::count(error::WARNING)){?><div class='alert alert-warning'><?php foreach(error::warnings() as $item){?><div><?=$item?></div><?php }?></div><?php }
				if (error::count(error::SUCCESS)){?><div class='alert alert-success'><?php foreach(error::success() as $item){?><div><?=$item?></div><?php }?></div><?php }
				return;
			case 'ajax':
				$answer=array();
				if (error::count()){
					foreach(error::errors() as $item){
						$answer[]=array('type'=>'message','message'=>'Error: '.$item);
					}
				}
				if (error::count(error::WARNING)){
					foreach(error::warnings() as $item){
						$answer[]=array('type'=>'message','message'=>'Warning: '.$item);
					}
				}
				if (error::count(error::SUCCESS)){
					foreach(error::success() as $item){
						$answer[]=array('type'=>'message','message'=>$item);
					}
				}
				return $answer;
			default:
				if (file_exists($type))return include $type;
				throw new \Exception('not set render template');
		}
	}

	static function form($form=null){
		return forms::render($form);
	}
}