<?php
namespace c;

/**
 * use request class
 * @deprecated since version 3.4
 */
class browser{

	/**
	 * @deprecated since version 3.4
	 */
	static function get($userAgent=null){
		return request::getBrowser($userAgent);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function checkForBrowserVersion($browser,$conditions){
		return request::checkForBrowserVersion($browser,$conditions);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function spider($USER_AGENT=''){
		return request::spider($USER_AGENT);
	}
}