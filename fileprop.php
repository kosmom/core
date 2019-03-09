<?php
namespace c;

class fileprop extends arrayaccess{
	/**
	 * Title of the page
	 * @var string
	 */
	var $name;
	
	/**
	 * Position from start
	 * @var float
	 */
	var $position;
	
	/**
	 * Current file absolute dir
	 * @var string
	 */
	var $__DIR__;
	
	/**
	 * Relative file dir
	 * @var string
	 */
	var $link;
	/**
	 * Relative file dir
	 * @var string
	 */
	var $href;
	
	/**
	 * Submenu of the page
	 * @var array|null
	 */
	var $submenu;
	
	/**
	 * Is active page
	 * @var boolean 
	 */
	var $active;
	
	/**
	 * Get submenu of menu
	 * @return fileprop[]|null
	 */
	function getSubMenu(){
		return mvc::getMenu($this->__DIR__);
	}
}