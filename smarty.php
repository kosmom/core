<?php
namespace c;

class smarty{
	public static function smartdate($ddmmyy_hhmi){
		if (date('Y-m-d')==substr($ddmmyy_hhmi,0,10))return input::iconv('Сегодня ').substr($ddmmyy_hhmi,11,5);
		if (date('Y-m-d',time()-60*60*24)==substr($ddmmyy_hhmi,0,10))return input::iconv('Вчера ').substr($ddmmyy_hhmi,11,5);
		return substr($ddmmyy_hhmi,8,2).'.'.substr($ddmmyy_hhmi,5,2).'.'.substr($ddmmyy_hhmi,0,4);//.' '.substr($ddmmyy_hhmi,11);
	}
	public static function mailto($address,$text='',$encode='javascript_charcode'){
		if (empty($text)) $text=$address;
		if ($encode=='javascript'){
			$string='document.write(\'<a href="mailto:'.$address.'">'.$text.'</a>\');';
			$js_encode='';
			$y=strlen($string);
			for($x=0; $x<$y; $x++)$js_encode.='%'.bin2hex($string[$x]);
			return '<script>eval(unescape(\''.$js_encode.'\'))</script>';
        }elseif ($encode=='javascript_charcode'){
            $string='<a href="mailto:'.$address.'">'.$text.'</a>';
            $y=strlen($string);
            for($x=0; $x<$y; $x++)
                $ord[]=ord($string[$x]);
            return "<script>document.write(String.fromCharCode(".implode(',',$ord)."))</script>";
        }elseif ($encode=='hex'){
            $address_encode='';
            $y=strlen($address);
            for($x=0; $x<$y; $x++){
                if (preg_match('!\w!',$address[$x])){
                    $address_encode.='%'.bin2hex($address[$x]);
                }else{
                    $address_encode.=$address[$x];
                }
            }
            $text_encode='';
            $y=strlen($text);
            for($x=0; $x<$y; $x++){
                $text_encode.='&#x'.bin2hex($text[$x]).';';
            }
            return '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$address_encode.'">'.$text_encode.'</a>';
        }else{
            // no encoding
            return '<a href="mailto:'.$address.'">'.$text.'</a>';
        }
    }
    public static function numberformat($number,$delimeter='.'){
		$number=str_replace('.',',',$number);
		$broken_number = explode(',',$number);
        return ($broken_number[0]===0?'0':str_replace('&','&nbsp;',number_format($broken_number[0],0,'.','&'))).(@$broken_number[1]?$delimeter.$broken_number[1]:'');
    }
	public static function smartnumber($number,$round=null){
		$number2=str_replace (',','.',$number);
		if (!is_numeric($number2))return $number;
		if ($round!==null)$number2=round($number2,$round);
		if (substr($number2,0,1)=='.')return '0'.$number2;
		return (float)$number2;
	}
	public static function album($id){
		// для использования - нужно положить альбом в указаную папку
		include 'templates/plugins/album.php';
	}
	public static function comments($page){
		// для использования - нужно положить шаблон коментария в указаную папку
		include 'templates/plugins/comments.php';
	}
	public static function setup($val){
	    // вывод общих переменных
	    global $setup;
	    if (isset($setup[$val]))return $setup[$val];
	}
}