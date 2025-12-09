<?php
namespace c;

class smarty{
	/**
	 * @deprecated since version 3.5
	 */
	static function smartdate($ddmmyy_hhmi){
		return date::smartdate($ddmmyy_hhmi);
	}
	static function mailto($address,$text='',$encode='javascript_charcode'){
		if (empty($text))$text=$address;
		switch ($encode){
			case 'javascript':
				$string='document.write(\'<a href="mailto:'.$address.'">'.$text.'</a>\');';
				$js_encode='';
				$y=\strlen($string);
				for($x=0; $x<$y; $x++)$js_encode.='%'.\bin2hex($string[$x]);
				return '<script>eval(unescape(\''.$js_encode.'\'))</script>';
			case 'javascript_charcode':
				$string='<a href="mailto:'.$address.'">'.$text.'</a>';
				$y=\strlen($string);
				for($x=0; $x<$y; $x++)$ord[]=\ord($string[$x]);
				return "<script>document.write(String.fromCharCode(".\implode(',',$ord)."))</script>";
			case 'hex':
				$address_encode='';
				$y=\strlen($address);
				for($x=0; $x<$y; $x++)$address_encode.=\preg_match('!\w!',$address[$x])?'%'.\bin2hex($address[$x]):$address[$x];
				$text_encode='';
				$y=\strlen($text);
				for($x=0; $x<$y; $x++)$text_encode.='&#x'.\bin2hex($text[$x]).';';
				return '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$address_encode.'">'.$text_encode.'</a>';
		}
		// no encoding
		return '<a href="mailto:'.$address.'">'.$text.'</a>';
	}
	static function numberformat($number,$delimeter='.'){
		$number=\str_replace('.',',',$number);
		$broken_number =\explode(',',$number);
		return ($broken_number[0]===0?'0':\str_replace('&','&nbsp;',\number_format($broken_number[0],0,'.','&'))).(@$broken_number[1]?$delimeter.$broken_number[1]:'');
	}
	static function smartnumber($number,$round=null){
		$number2=\str_replace(',','.',$number);
		if (!\is_numeric($number2))return $number;
		if ($round!==null)$number2=\round($number2,$round);
		if (\substr($number2,0,1)=='.')return '0'.$number2;
		return (float)$number2;
	}
}