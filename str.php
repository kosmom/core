<?php
namespace c;

class str{
	private $val;
	private $charset;
	
	public function __toString () {
		return $this->val;
	}
	public function val(){
		return $this->val;
	}
	public function __construct($value,$charset=null) {
		$this->val=(string)$value;
		$this->charset=$charset?$charset:core::$charset;
	}
	static function make($string,$charset=null){
		return new str($string,$charset);
	}
	
	function substr($start, $length = null) {
		$this->val= mb_substr($this->val, $start, $length, $this->charset);
		return $this;
	}
	/**
	 * Equivelent of Javascript's String.substring
	 * @link http://www.w3schools.com/jsref/jsref_substring.asp
	 */
	function substring ($string, $start, $end=null) {
		if(empty($end))return $this->substr($string, $start);
		return self::substr($string, $end - $start);
	}
	function charAt($point){
		return substr($this->val,$point,1);
	}
	function trim($mask=' \t\n\r\0\0xB'){
		$this->val=trim($this->val,$mask);
		return $this;
	}
	function ltrim($mask=' \t\n\r\0\0xB'){
		$this->val=ltrim($this->val,$mask);
		return $this;
	}
	function rtrim($mask=' \t\n\r\0\0xB'){
		$this->val=rtrim($this->val,$mask);
		return $this;
	}
	function pos($needle,$offset=0){
		return mb_strpos($this->val, $needle, $offset, $this->charset);
	}
	function ipos($needle,$offset=0){
		return mb_stripos($this->val, $needle, $offset, $this->charset);
	}
	function indexOf($needle,$offset=0){
		return $this->pos($needle,$offset);
	}
	function replace($search,$replace){
		$this->val=str_replace($search,$replace,$this->val);
		return $this;
	}
	
	function strtr($from,$to=null){
		if ($to===null){
			$this->val=strtr($this->val,$from);
		}else{
			$this->val= strtr($this->val,$from,$to);
		}
		return $this;
	}
	
	function length(){
		return mb_strlen($this->val, $this->charset);
	}
	function len(){
		return $this->length();
	}
	function strtolower(){
		$this->val= mb_strtolower($this->val, $this->charset);
		return $this;
	}
	function lower(){
		return $this->strtolower();
	}
	function strtoupper(){
		$this->val= mb_strtoupper($this->val, $this->charset);
		return $this;
	}
	function ucfirst(){
		$this->val= ucfirst($this->val);
		return $this;
	}
	function ucwords($delimeters=' \t\r\n\f\v'){
		$this->val= ucwords($this->val,$delimeters);
		return $this;
	}
	function upper(){
		return $this->strtoupper();
		
	}
	function match ($regex) {
		$matches=array();
		preg_match_all($regex, $this->val, $matches, PREG_PATTERN_ORDER);
		return $matches[0];
	}
	function explode($delimeter){
		return explode($delimeter, $this->val);
	}
	function split($delimeter){
		return $this->explode($delimeter);
	}
	function nl2br(){
		$this->val= nl2br($this->val);
		return $this;
	}
	function urlencode(){
		$this->val= urlencode($this->val);
		return $this;
	}
	function urldecode(){
		$this->val= urldecode($this->val);
		return $this;
	}
	function strip_tags($alowabas_tags=null){
		$this->val= strip_tags($this->val,$alowabas_tags);
		return $this;
	}
	function htmlspecialchars(){
		$this->val= htmlspecialchars($this->val);
		return $this;
	}
	function htmlspecialchars_decode(){
		$this->val= htmlspecialchars_decode($this->val);
		return $this;
	}
	function ord($point){
		return ord($this->charAt($point));
	}
	function crc32(){
		return crc32($this->val);
	}
	function md5(){
		return md5($this->val);
	}
	function sha1(){
		return sha1($this->val);
	}
	function shuffle(){
		$this->val= str_shuffle($this->val);
		return $this;
	}
	function reverse(){
		$this->val= strrev($this->val);
		return $this;
	}
	function strrev(){
		return $this->reverse();
	}
	function similar_text($phrase){
		$percent=0;
		similar_text($this->val, $phrase, $percent);
		return $percent;
	}
	function pad($length,$pad_string,$pad_type=\STR_PAD_RIGHT){
		$this->val= str_pad($this->val, $length, $pad_string, $pad_type);
		return $this;
	}
	function str_pad($length,$pad_string,$pad_type=\STR_PAD_RIGHT){
		return $this->pad($length,$pad_string,$pad_type);
	}
	function repeat($multiplier){
		$this->val= str_repeat($this->val, $multiplier);
		return $this;
	}
	function wordwrap($width=75,$break='\n',$cut=false){
		$this->val= wordwrap($this->val, $width, $break, $cut);
		return $this;
	}
	function translit(){
		$this->val= translate::translit($this->val);
		return $this;
	}
	function t($vars=array()){
		$this->val=translate::t($this->val, $vars);
		return $this;
	}
	function iconv(){
		$this->val=input::iconv($this->val);
		return $this;
	}
	function iconvBack(){
		$this->val=input::iconv($this->val);
		return $this;
	}
	function toPic($autorotate=true){
		return new pic($this->val, $autorotate);
	}
	function totime($now=null){
		if (!$now)$now=time();
		return strtotime($this->val,$now);
	}
	function strtotime($now=null){
		return $this->totime($now);
	}
	
	function mat_filter(){
		$this->val= translate::mat($this->val);
		return $this;
	}
	function mat_replace_star(){
		$this->val= translate::mat($this->val,'star');
		return $this;
	}
	function mat_replace_soft(){
		$this->val= translate::mat($this->val,'soft');
		return $this;
	}
	function grammar(){
		$this->val= translate::grammar($this->val);
		return $this;
	}
	function smartKeyboard(){
		$this->val= translate::smartKeyboard($this->val);
		return $this;
	}
	function jsonDecode(){
		$this->val=input::json_decode($this->val, $this->charset);
		return $this;
	}
	function dd($exit=true){
		debug::z($this->val,$exit);
	}
	function log($filename){
		error::log($filename, $this->val);
	}
	function call($callback){
		$this->val=$callback($this->val);
		return $this;
	}
	function validate($validators){
		return input::validate($this->val, $validators);
	}
	function bb($isHtml=false){
		$this->val=input::bb($this->val,$isHtml);
		return $this;
	}
}