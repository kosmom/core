<?php
namespace c;

/**
 * Input paramters, validation work class
 * @author Kosmom <Kosmom.ru>
 */
class input{
	const VALIDATE_AUTO_TEXT='{{c_input_validate_auto_text}}';

	/**
	 * check var with filters
	 */
	static function test(){
		if (func_num_args()<1)return translate::t('Wrong call of function, need arguments');
		$input=func_get_args();

		foreach ($input as $key=>$value){
			if ($key==0){
				$var=$value;
			}elseif ($value=='fill'){
				if (trim($var)=='')return translate::t('Set empty value');
			}elseif (trim($value)!=''){
				if ($value=='plus'){
					if (floatval($var)<0)return translate::t('Value cannot be minus');
				}elseif ($value=='date'){
					if (!strtotime($var))return translate::t('Date wrong');
				}elseif ($value=='date_format'){
					if (!preg_match('/^\d{1,2}([-. /])\d{1,2}\1\d{2,4}$/', $var))return translate::t('Date set not in format DD.MM.YYYY');
				}elseif ($value=='mail'){
					if ((!preg_match('/^[\.\-_A-Za-z0-9]+?@[\.\-A-Za-z0-9]+?\.[A-Za-z0-9]{2,6}$/', $var)) && $var!='') return translate::t('mail format wrong');
				}
			}
		}
		return true;
	}
	/**
	 * Add http:// to string
	 * @param string $url
	 * @return string
	 */
	static function toUrl($url){
		$lowerUrl=self::lower($url);
		if (substr($lowerUrl,0,7)=='http://' or substr($lowerUrl,0,8)=='https://')return $url;
		return 'http://'.$url;
	}
	static function clearArray($array){
		if (is_array($array)){
			foreach ($array as $key=>$value){
				if (self::clearArray($value)==false){
					unset($array[$key]);
					continue;
				}
			}
		}else{
			if (empty($array))return false;
		}
		return $array;
	}
	/**
	 * Filter $var with $filters array
	 * @param string|integer $var input variable
	 * @param array|string $filters. Possible variants trim,lower,upper,int,float,bool,abs,ucfirst,ucwords,tag,iconv,phone
	 * @return super
	 */
	static function filter(&$var,$filters=''){
		if (!isset($var))$var='';
		if (empty($filters))return false;
		if (is_string($filters))$filters=array($filters);
		if (!is_array($filters))return false;
		foreach ($filters as $filter){
				if (gettype($filter)=='object'){
					$var=$filter($var);
					continue;
				}
				switch ($filter){
					case 'clear':
						$var=self::clearArray($var);
						break;
					case 'trim':
						$var=trim($var);
						break;
					case 'grammar':
						$var=  translate::grammar($var);
						break;
					case 'strtolower':
					case 'lower':
						$var=mb_strtolower($var,core::$charset);
						break;
					case 'strtoupper':
					case 'upper':
						$var=mb_strtoupper($var,core::$charset);
						break;
					case 'int':
					case 'intval':
						$var=(int)$var;
						break;
					case 'float':
					case 'floatval':
						$var=str_replace(',','.',$var);
						$var=(float)$var;
						break;
					case 'bool':
					case 'boolean':
						if ($var==='false')$var=0;
						$var=(bool)$var;
						break;
					case 'abs':
						$var=abs($var);
						break;
					case 'striptags':
					case 'strip_tags':
					case 'tag':
						$var=strip_tags($var);
						break;
					case 'iconv':
					case 'from-utf8':
						if (core::$charset==core::UTF8)break;
						$var=self::iconv($var);
//						$var=iconv('utf-8', core::$charset, $var);
						break;
					case 'ucfirst':
						$var=mb_strtoupper(mb_substr($var,0,1,core::$charset),core::$charset).mb_substr($var,1,mb_strlen($var,core::$charset)-1,core::$charset);
						break;
					case 'ucwords':
						$var=mb_convert_case($var, MB_CASE_TITLE, core::$charset);
						break;
					case 'phone':
						$var=self::phone($var);
						break;
				}
			}
			return new super();
	}
	private static function valid($validator,$val,$formKey){
		if ($validator['type']=='required' || $validator['type']=='require')return ($val!=='' && $val!==null);
		if ($val==='' or $val===null)return true;
		if (empty($validator['inverse']))$validator['inverse']=false;
		$i=$validator['inverse'];
		switch ($validator['type']){
				case 'int':
				case 'number':
				case 'numberic':
				case 'numeric':
					return is_numeric($val) xor $i;
				case 'maxlength':
					return mb_strlen($val,core::$charset)<=$validator['value'] xor $i;
				case 'minlength':
					return mb_strlen($val,core::$charset)>=$validator['value'] xor $i;
				case 'between':
					return ($val>=$validator['min'] && $val<=$validator['max']) xor $i;
				case 'min':
					return $val>=$validator['value'] xor $i;
				case 'max':
					return $val<=$validator['value'] xor $i;
				case 'in':
					return in_array($val,$validator['values']) xor $i;
				case 'email':
					return self::mailTest($val) xor $i;
				case 'match':
				case '==':
					return $val==$validator['value'] xor $i;
				case 'date':
				case 'datetime':
					return (input::strtotime($val)) xor $i;
				case 'pattern':
				case 'preg_match':
					return preg_match($validator['value'],$val) xor $i;
				case 'function':
				case 'closure':
				case 'anonymous':
				case 'callback':
				case 'func':
					return $validator['function']($val) xor $i;
				case 'filesize':
					return filesize($val)<=$validator['value'] xor $i;
				case 'extension':
					$extension= strtolower(filedata::extension(request::fileName($formKey)));
					return in_array($extension,$validator['values']) xor $i;
			}
			return true;
	}
	/**
	 * Validate var of validate filters
	 * @param any $val testing variable
	 * @param array $validators array of validators with need format
	 * @param array $label label of field with error thrown
	 * @param array $formKey key of field with error for file validate
	 * @return boolean result of validation
	 * @example $validators params is<br>
	 *		type - type of validator<br>
	 *		text - text of report of error or false if not need report
	 */
	static function validate($val,$validators=null,$label=array(),$formKey=null){
		if (empty($validators))return true;
		$valid=true;
		$errorDict=array(
			'int'=>'"{label_full}" field must be numberic',
			'number'=>'"{label_full}" field must be numberic',
			'numberic'=>'"{label_full}" field must be numberic',
			'numeric'=>'"{label_full}" field must be numberic',
			'maxlength'=>'"{label_full}" field max length is {value}',
			'minlength'=>'"{label_full}" field min length is {value}',
			'min'=>'"{label_full}" field min value is {value}',
			'max'=>'"{label_full}" field max value is {value}',
			'between'=>'"{label_full}" field must be between {min} and {max} value',
			'required'=>'"{label_full}" field cannot be empty',
			'require'=>'"{label_full}" field cannot be empty',
			'pattern'=>'"{label_full}" field has wrong value',
			'email'=>'"{label_full}" field not valid email address',
			'date'=>'"{label_full}" field has wrong date format',
			'datetime'=>'"{label_full}" field has wrong date format',
			'filesize'=>'"{label_full}" file has too big size',
			'extension'=>'"{label_full}" file extension is wrong',
		);
		foreach ($validators as $validator){
			if ($valid===false && (!isset($validator['text']) or $validator['text']===false))continue;
			$errorText=self::htmlspecialchars(translate::t($validator['text']!=self::VALIDATE_AUTO_TEXT?$validator['text']:($errorDict[$validator['type']]?$errorDict[$validator['type']]:'Field "{label_full}" has wrong value'),$validator+(array)$label));
			$errorLevel=@$validator['level']?$validator['level']:error::ERROR;
			$check=self::valid($validator, $val, $formKey);
			if ($valid && !$check)$valid=false;
			if (!$check && isset($validator['text']) && $validator['text']!==false)error::add($errorText,$errorLevel);
		}
		return $valid;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function find_first($string,$symbol){
		return self::findFirst($string,$symbol);
	}
	static function findFirst($string,$symbol){
		$i=0;
		do {
		$quote=mb_strpos($string, $symbol.$symbol,$i+1,core::$charset);
		$finish=mb_strpos($string, $symbol,$i+1,core::$charset);
		if ($finish===false)return false;
		if ($quote===false)return $finish;
		$i=$quote+1;
		} while ($quote==$finish);
		return $finish;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function find_first_prepare(&$string){
		return self::findFirstPrepare($string);
	}
	/**
	 * Transform string from equated symbol. String trimmed with transforming
	 * @param string $string equsted string, for example "'o''realy' and some else text"
	 * @return string normal out
	 */
	static function findFirstPrepare(&$string){
		$symbol=mb_substr($string,0,1,core::$charset);
		$delimeter=self::findFirst(mb_substr($string,1,NULL,core::$charset),$symbol);
		$out=mb_substr($string,1,$delimeter,core::$charset);
		$string=mb_substr($string,$delimeter+2,NULL,core::$charset);
		return str_replace($symbol.$symbol,$symbol,$out);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function check_mail($email){
		return self::checkMail($email);
	}
	/**
	 * Check real mail with domen mail port exists
	 * @param string $email
	 * @return boolean
	 */
	static function checkMail($email){
		$email=self::lower($email);
		$validMail=array('gmail.com','yandex.ru','rambler.ru','mail.ru','inbox.ru','bk.ru','bigmir.net','list.ru','pochta.ru','aport.ru','hotbox.ru');
		$emailArr = explode('@' , $email);
		if (empty($emailArr[1]))return false;
		if (in_array($emailArr[1],$validMail))return true;
		if (!getmxrr($emailArr[1], $mxhostsarr))return false;
		return true;
	}
	/**
	 * @deprecated use filter method
	 */
	static function setPost($var,$func=array(),$cannull=true){
		if (empty($_POST[$var]))$_POST[$var]=null;
		if ($_POST[$var]==null && $cannull)return false;
		$v=$_POST[$var];
		self::filter($v,$func);
		$_POST[$var]=$v;
	}
	/**
	 * @deprecated use filter method
	 */
	static function setGet($var,$func=array(),$cannull=true){
		if (empty($_GET[$var]))$_GET[$var]='';
		if ($_GET[$var]==null && $cannull)return false;
		$v=$_GET[$var];
		self::filter($v,$func);
		$_GET[$var]=$v;
	}
	/**
	 * @deprecated use filter method
	 */
	static function setVal($var,$func=array(),$cannull=true){
		if ($var=='' && $cannull)return;
		self::filter($var,$func);
		return $var;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function strip_tags_attributes($sSource, $aAllowedTags = array(), $aDisabledAttributes = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload','style','width','height')){
		return self::stripTagsAttributes($sSource,$aAllowedTags,$aDisabledAttributes);
	}
	static function stripTagsAttributes($sSource, $aAllowedTags = array(), $aDisabledAttributes = array(
		'onabort',
		'onactivate',
		'onafterprint',
		'onafterupdate',
		'onbeforeactivate',
		'onbeforecopy',
		'onbeforecut',
		'onbeforedeactivate',
		'onbeforeeditfocus',
		'onbeforepaste',
		'onbeforeprint',
		'onbeforeunload',
		'onbeforeupdate',
		'onblur',
		'onbounce',
		'oncellchange',
		'onchange',
		'onclick',
		'oncontextmenu',
		'oncontrolselect',
		'oncopy',
		'oncut',
		'ondataavaible',
		'ondatasetchanged',
		'ondatasetcomplete',
		'ondblclick',
		'ondeactivate',
		'ondrag',
		'ondragdrop',
		'ondragend',
		'ondragenter',
		'ondragleave',
		'ondragover',
		'ondragstart',
		'ondrop',
		'onerror',
		'onerrorupdate',
		'onfilterupdate',
		'onfinish',
		'onfocus',
		'onfocusin',
		'onfocusout',
		'onhelp',
		'onkeydown',
		'onkeypress',
		'onkeyup',
		'onlayoutcomplete',
		'onload',
		'onlosecapture',
		'onmousedown',
		'onmouseenter',
		'onmouseleave',
		'onmousemove',
		'onmoveout',
		'onmouseover',
		'onmouseup',
		'onmousewheel',
		'onmove',
		'onmoveend',
		'onmovestart',
		'onpaste',
		'onpropertychange',
		'onreadystatechange',
		'onreset',
		'onresize',
		'onresizeend',
		'onresizestart',
		'onrowexit',
		'onrowsdelete',
		'onrowsinserted',
		'onscroll',
		'onselect',
		'onselectionchange',
		'onselectstart',
		'onstart',
		'onstop',
		'onsubmit',
		'onunload',
		'style',
		'width',
		'height')){
		if (empty($aDisabledAttributes)) return strip_tags($sSource, implode('', $aAllowedTags));
		return preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . implode('|', $aDisabledAttributes) . ")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", strip_tags($sSource, implode('', $aAllowedTags)));
	}
	/**
	 * Transform array as data[][field]
	 * @deprecated dont't use this method
	 */
	static function formarray($array){
		if (!is_array($array))return false;
		$outarray=array();
		$counter=0;
		foreach ($array as $key=>$value){
			foreach ($value as $key2=>$value2){
				if (isset($outarray[$counter][$key2]))$counter++;
				$outarray[$counter][$key2]=$value2;
			}
		}
		return $outarray;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function get_link($without='',$nullsIsEmpty=true,$onlyParams=false){
		return self::getLink($without,$nullsIsEmpty,$onlyParams);
	}
	static function getLinkParams($without=null,$nullsIsEmpty=true){
		return self::getLink($without,$nullsIsEmpty,true);
	}
	/**
	 * Modify GET input string, remove or replace $without parameters witn it
	 * @param array $without array within replace GET input parameters
	 * @param boolean $nullsIsEmpty remove or not GET variables if $without has 0 or '' value
	 * @return string total GET string with replace $without
	 */
	static function getLink($without=null,$nullsIsEmpty=true,$onlyParams=false){
		$out=array();
		$flatten=array();
		$removes=array();
		if ($without){
			foreach ($without as $key=>$val){
				if ($val===false)$removes[$key]=true;
			}
			foreach (explode('&', http_build_query($without)) as $get){
				$keyval= explode('=', $get);
				$flatten[$keyval[0]]=$keyval[1];
			}
		}
		$uri=explode('?',$_SERVER['REQUEST_URI']);
		$gets=substr($_SERVER['REQUEST_URI'], strlen($uri[0])+1);
		if ($gets){
			foreach (explode('&', $gets) as $get){
				$keyval= explode('=', $get);
				if (isset($removes[$keyval[0]]))continue;
				if (isset($flatten[$keyval[0]]))$keyval[1]=$flatten[$keyval[0]];
				if ($keyval[1]===false)continue;
				if ($nullsIsEmpty && $keyval[1]==='')continue;
				$out[]=$keyval[0].($keyval[1]===true?'':'='.$keyval[1]);
			}
		}
		//add new parameters
		$out2=array();
		if (is_array($without)){
			foreach ($without as $key=>$val){
				if (!isset($_GET[$key])){
					if (isset($removes[$key]))continue;
					$out2[]=urlencode($key).'='.urlencode($val);
				}
			}
		}
		$out=implode( '&',$out);
		$out2=implode( '&',$out2);
		if ($out2 && $out){
			$out.='&'.$out2;
		}elseif ($out2){
			$out=$out2;
		}
		return ($onlyParams?'':$uri[0]).($out?'?'.$out:'');
	}

	/**
	 * Check active link generated with params
	 * @param array $params
	 * @return boolean is active
	 */
	static function isLinkActive($params=array()){
		foreach ($params as $param=>$value){
			if ($value===false){
				if (isset($_GET[$param]))return false;
			}else{
				if ($_GET[$param]!=$value)return false;
			}
		}
		return true;
	}

	/**
	 * Crypt string with method
	 * @deprecated because hardcode
	 * @param string $string
	 * @param string $method MD5|MD5MD5
	 * @return string
	 */
	static function crypt($string,$method='MD5'){
		$method=trim(mb_strtoupper($method));
		if ($method=='MD5'){
			return md5($string);
		}elseif ($method=='MD5MD5'){
			return md5(md5($string));
		}
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function mail_test($email,$showError=true){
		return self::mailTest($email,$showError);
	}
	static function mailTest($email,$showError=true){
		if (!preg_match('/^[\.\-_A-Za-z0-9]+?@[\.\-A-Za-z0-9]+?\.[A-Za-z0-9]{2,6}$/', $email)){
			if ($showError)error::add(translate::t('Email {email} is incorrect',array('email'=>$email))); else return false;
		}
		return true;
	}
	static function lower($text){
		return mb_strtolower($text,core::$charset);
	}
	static function upper($text){
		return mb_strtoupper($text,core::$charset);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function link($filename){
		if (file_exists($filename))return include($filename);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function text_transform($text){
		return self::textTransform($text);
	}
	static function textTransform($text){
		$text=strtr(trim($text),array(
		'ё'=>'е',
		'Ё'=>'Е',
		','=>' ',
		'.'=>' ',
		'?'=>' ',
		';'=>' ',
		':'=>' ',
		'-'=>' ',
		'='=>' ',
		"'"=>'"',
		"\\"=>' ',
		"\r"=>' ',
		"\n"=>' ',
		"\t"=>' ',
		'+'=>' ',
		'%'=>' ',
		'`'=>' ',
		'('=>' ( ',
		')'=>' ) ',
		'!'=>' ! ',
		'”'=>' ',
		));
		while (strpos($text,'  '))$text=str_replace('  ',' ',$text);
		return str_replace('""','',$text);
	}
	static function phoneDigits($number){
		$n=trim($number);
		if (substr($n,0,2)=='+7')$n='8'.substr($n,2);
        return preg_replace('/[^0-9]/', '', $n);
	}
	static function phone($number){
		$n=self::phoneDigits($number);
        $len=strlen($n);
		if (substr($n,0,1)=='9' && $len==10){
			$n='8'.$n;
			$len++;
		}
		return $len>=11?substr($n,0,$len-10).'('.substr($n,-10,3).')'.substr($n,-7,3).'-'.substr($n,-4,2).'-'.substr($n,-2):$n;
	}
	/**
	 * Transform numbers in text field with delimeters "-" and "," in array
	 */
	static function numbers($numbers){
		// todo:: regexp replace mulsisigns like ',,,'
		$docs=str_replace(";",'',str_replace(',,',',',trim($numbers,", \t\n\r\0\x0B")));
		$docarc=explode(',',$docs);
		$tirearray=array();
		foreach ($docarc as $key=>$value){
			if (!strstr($value,'-'))  continue;
			$subdoc=explode('-',$value);
			$min=min($subdoc[1],$subdoc[0]);
			$max=max($subdoc[1],$subdoc[0]);
			for ($a=$min;$a<$max+1;$a++)$tirearray[]=$a;
			unset($docarc[$key]);
		}
		return array_merge($docarc,$tirearray);
	}
	static function bb($text, $isHtml=false){
		$text=trim($text);
		$strSearch = array(
		'#\n#is',
		'#\[p\](.+?)\[\/p\]#is',
		'#\[b\](.+?)\[\/b\]#is',
		'#\[i\](.+?)\[\/i\]#is',
		'#\[s\](.+?)\[\/s\]#is',
		'#\[u\](.+?)\[\/u\]#is',
		'#\[code\](.+?)\[\/code\]#is',
		'#\[quote\](.+?)\[\/quote\]#is',
		'#\[url=(.+?)\](.+?)\[\/url\]#is',
		'#\[url\](.+?)\[\/url\]#is',
		'#\[img\](.+?)\[\/img\]#is',
		'#\[size=(.+?)\](.+?)\[\/size\]#is',
		'#\[color=(.+?)\](.+?)\[\/color\]#is',
		'#\[list\](.+?)\[\/list\]#is',
		'#\[list=(1|a|I)\](.+?)\[\/list\]#is',
		'#\[\*\](.*)#',
		'#\[h(2|3|4|5|6)\](.+?)\[/h\\1\]#is',
		'#\[video\]([a-zA-Z0-9\-_]+?)\[\/video\]#is');
		$strReplace = array(
		'<br />',
		'</p><p>\\1</p>',
		'<strong>\\1</strong>',
		'<em>\\1</em>',
		'<span style="text-decoration:line-through">\\1</span>',
		'<span style="text-decoration:underline">\\1</span>',
		'<code>\\1</code>',
		'<blockquote>\\1</blockquote>',
		"<a href='\\1'>\\2</a>",
		"<a href='\\1'>\\1</a>",
		"<img src='\\1' />",
		"<span style='font-size:\\1pt'>\\2</span>",
		"<span style='color:\\1'>\\2</span>",
		'<ul>\\1</ul>',
		"<ol type='\\1'>\\2</ol>",
		'<li>\\1</li>',
		'<h \\1>\\2</h>',
		"<iframe width='420' height='315' src='//www.youtube.com/embed/\\1' frameborder='0' allowfullscreen></iframe>");
		if ($isHtml){
			$text=str_replace('[video]http://youtu.be/','[video]',$text);
			return preg_replace($strSearch, $strReplace, htmlspecialchars($text));
		}else{
			return htmlspecialchars_decode(preg_replace($strReplace, $strSearch, $text));
		}
	}

	static function iconvBack($var){
		return self::iconv($var,true);
	}
	static function iconv($var,$backDirection=false){
		if (core::$charset==core::UTF8)return $var;
		if (is_array($var)){
			$new = array();
			foreach ($var as $k => $v) {
				$new[self::iconv($k,$backDirection)] = self::iconv($v,$backDirection);
			}
			$var = $new;
		}elseif (is_string($var)){
			$var = ($backDirection?iconv(core::$charset,'utf-8', $var):iconv('utf-8',core::$charset, $var));
		}elseif (is_object($var)){
			$vars = get_object_vars($var);
			foreach ($vars as $m => $v) {
				$var->$m = self::iconv($v,$backDirection);
			}
		}
		return $var;
	}

	/**
	 * JSON encode string with charset conversion
	 * @param array $var
	 * @return string
	 */
	static function json_encode($var,$charset=null){
		return self::jsonEncode($var,$charset);
	}

	static function json_decode($var,$charset=null){
		return self::jsonDecode($var,$charset);
	}

	static function jsonDecode($var,$charset=null){
		if ($charset==null)$charset=core::$charset;
		$var=json_decode($var,1);
		if ($charset==core::UTF8)return $var;
		return self::jsonFixCharset($var,$charset,false);
	}

	/**
	 * JSON encode string with charset conversion
	 * @param array $var
	 * @return string
	 */
	static function jsonEncode($var,$charset=null){
		if ($charset==null)$charset=core::$charset;
		if ($charset==core::UTF8)return @json_encode($var,JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | (PHP_VERSION_ID > 70200 ? JSON_INVALID_UTF8_IGNORE : 0) );
		return json_encode(self::jsonFixCharset($var,$charset),JSON_UNESCAPED_UNICODE);
	}
	private static function jsonFixCharset($var,$charset,$fromUtf=true){
		if (is_array($var)){
			$new = array();
			foreach ($var as $k => $v) {
				$new[self::jsonFixCharset($k,$charset,$fromUtf)] = self::jsonFixCharset($v,$charset,$fromUtf);
			}
			$var = $new;
		}elseif (is_string($var)){
			if ($fromUtf){
				$var = iconv($charset, 'utf-8', $var);
			}else{
				$var = iconv('utf-8',$charset, $var);
			}
		}elseif (is_object($var)){
			$vars = get_object_vars($var);
			foreach ($vars as $m => $v) {
				$var->$m = self::jsonFixCharset($v,$charset,$fromUtf);
			}
		}
		return $var;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function js_var($value){
		return self::jsVar($value);
	}
	static private function jsReplace($value){
		return str_replace(array("\r", "\n", "'","</script>"),array('', "\\n", "\'","<\/script>"),$value);
	}
	static function jsVar($value){
		switch(gettype($value)){
			case 'string':return "'".self::jsReplace($value)."'";
			case 'boolean':return $value?'true':'false';
			case 'array':
			case 'object':
				$out=array();
				foreach ($value as $key=>$item){
					$out[]="'".self::jsReplace($key)."':".self::jsVar($item);
				}
				return '{'.implode(',',$out).'}';
			case 'integer':
			case 'double':
				return $value;
			case 'NULL': return "''";
		}
	}

	static function jsPrepare($string){
		return str_replace(array("\r",'\\',"'","\n"),array('','\\\\','\\\'','\n'),$string);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function js_prepare($string){
		return self::jsPrepare($string);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function js_var_array($value){
		return self::jsVarArray($value);
	}
	static function jsVarArray($value){
		switch(gettype($value)){
			case 'string':return "'".self::jsPrepare($value)."'";
			case 'boolean':return $value?'true':'false';
			case 'array':
			case 'object':
				$out=array();
				$is_array=true;
				$last_key=0;
				foreach ($value as $key=>$item){
					if ($is_array && $key!==$last_key++){
						$is_array=false;
						break;
					}
				}
				if ($is_array){
					foreach ($value as $key=>$item){
						$out[]=self::jsVarArray($item);
					}
					return '['.implode(',',$out).']';
				}
				// is object
				foreach ($value as $key=>$item){
					$out[]="'".self::jsPrepare($key)."':".self::jsVarArray($item);
				}
				return '{'.implode(',',$out).'}';
			case 'resource':
				return "'".$value."'";
			case 'integer':
			case 'double':
				return $value;
			case 'NULL': return "''";
			default: die(gettype($value));
		}
	}

	/**
	 * htmlspecialchars for any charset
	 * @param string $html
	 * @param string $charset
	 * @return string
	 */
	static function htmlspecialchars($html,$charset=null){
		if ($charset==null)$charset=core::$charset;
		return htmlspecialchars($html,2,$charset);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function curl_get_content($URL,$post=null,$cookieFile=''){
		return curl::getContent($URL,$post,$cookieFile);
	}
	/**
	 * @deprecated since version 3.5
	 */
	static function curlGetContent($URL,$post=null,$cookieFile=''){
		return curl::getContent($URL,$post,$cookieFile);
	}
	
	static function strtotime($time){
		$matches=array();
		if (preg_match('/([12]\d\d\d)[\.\-]([0-2]\d)/', $time,$matches)){
			return mktime(0, 0, 0, $matches[2], 1, $matches[1]);
		}
		if (is_numeric($time) && date('d.m.Y H:i:s',$time)==$time){
			return $time;
		}
		return strtotime($time);
	}
	static function number_format($number,$decimals=0,$dec_point='.',$thousands_sep=','){
		return strtr(number_format($number,$decimals,'d','t'),array('d'=>$dec_point,'t'=>$thousands_sep));
	}
	static function roundFirstNumber($number,$precision=0){
		if ($number>1)return round($number,$precision);
		return round($number,-floor(log10($number)));
	}
	static function file_get_content($filename){
		if (substr($filename,0,7)=='http://' or substr($filename,0,8)=='https://')return curl::getContent($filename);
		return file_get_contents($filename);
	}
}