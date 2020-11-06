<?php
namespace c;

/**
 * Translation, localisation, language class
 * @author Kosmom <Kosmom.ru>
 */
class translate{
	static $charset=core::UTF8;
	static $tDict=array();
	private static $isdictLoaded=\false;

	static function reloadLocate($locate){
		core::$lang=$locate;
		self::$tDict=array();
		if (!core::$lang)return \true;
		if (\file_exists($file=__DIR__.'/global-config/translate_'.core::$lang.'.php'))include $file;
		if (\file_exists($file='config/translate_'.core::$lang.'.php'))include $file;
		self::$isdictLoaded=\true;
		foreach (mvc::$url as $url=>$v){
			if (\file_exists($file=$url.'/translate_'.core::$lang.'.php'))include $file;
		}
		return \true;
	}
	static function dictLP($source,$translate){
		return self::dict($source, $translate, \true);
	}
	static function dict($source,$translate,$lowerPriority=\false){
		if ($translate=='')return \true;
		if ($lowerPriority && isset(self::$tDict[$source]))return \false;
		if (self::$charset==core::UTF8){
			self::$tDict[$source]=$translate;
			return \true;
		}
		self::$tDict[$source]=\iconv(self::$charset,core::UTF8,$translate);
	}
	static function t($text,$vars=array()){
		if (core::$lang && !self::$isdictLoaded){
			if (\file_exists($file=__DIR__.'/global-config/translate_'.core::$lang.'.php'))include $file;
			if (\file_exists($file='config/translate_'.core::$lang.'.php'))include $file;
			self::$isdictLoaded=\true;
		}
		$magicvar=\null;
		if (!\is_array($vars))$magicvar=$vars;
		if (isset(self::$tDict[$text])){
			if (core::$charset==core::UTF8){
				$text= self::$tDict[$text];
			}else{
				$text=iconv(core::UTF8,core::$charset,self::$tDict[$text]);
			}
		}

		if (\strpos($text,'{')===\false)return $text;
		// vars and functions
		return \preg_replace_callback('/\{(.*)\}/sU',function($matches) use($vars,$magicvar){
			$params=\explode('|',$matches[1]);
			$var=\array_shift($params);
			$var=isset($magicvar)?$magicvar:$vars[$var];
			if (!$params)return $var;
			$func=\array_shift($params);
			switch ($func){
				case 'plural':
				case 'num_ending':
					return translate::plural((float)$var,$params[0],$params[1],$params[2]);
				case 'date':
					return date::date($params[0],$var,$params[1]);
				case 'numberformat':
					return smarty::smartnumber($var,$params[0]);
			}
			return $var;
		},$text);
	}
	private static $transArr=array(
		'?'=>'-vopros-',
		'•'=>'-',
		'–'=>'-',
		'>'=>'-',
		'<'=>'-',
		'“'=>'-',
		'”'=>'-',
		'%'=>'-procent-',
		'`'=>'-',
		'~'=>'-',
		'№'=>'-nomer-',
		'+'=>'-plus-',
		';'=>'-',
		'#'=>'-',
		'*'=>'-',
		'_'=>'-',
		'@'=>'-sobaka-',
		','=>'-',
		'.'=>'-',
		']'=>'-',
		'['=>'-',
		'»'=>'-',
		'«'=>'-',
		':'=>'-',
		'='=>'-equal-',
		'\t'=>'',
		'\r'=>'',
		'\n'=>'',
		'\\'=>'-',
		'&'=>'-and-',
		'/'=>'-',
		'"'=>'-',
		"'"=>'-',
		' '=>'-',
		'а'=>'a',
		'б'=>'b',
		'в'=>'v',
		'г'=>'g',
		'д'=>'d',
		'е'=>'e',
		'ё'=>'yo',
		'ж'=>'zh',
		'з'=>'z',
		'и'=>'i',
		'й'=>'j',
		'к'=>'k',
		'л'=>'l',
		'м'=>'m',
		'н'=>'n',
		'о'=>'o',
		'п'=>'p',
		'р'=>'r',
		'с'=>'s',
		'т'=>'t',
		'у'=>'u',
		'ф'=>'f',
		'х'=>'kh',
		'ц'=>'c',
		'ч'=>'ch',
		'ш'=>'sh',
		'щ'=>'sch',
		'ъ'=>'',
		'ы'=>'y',
		'ь'=>'',
		'э'=>'e',
		'ю'=>'yu',
		'я'=>'ja'
	);

	private static $specArr=array(
		'?'=>'-',
		'•'=>'-',
		'–'=>'-',
		'>'=>'-',
		'<'=>'-',
		'%'=>'-',
		'`'=>'-',
		'~'=>'-',
		'№'=>'-',
		'+'=>'-',
		';'=>'-',
		'#'=>'-',
		'_'=>'-',
		'@'=>'-',
		','=>'-',
		'.'=>'-',
		']'=>'-',
		'['=>'-',
		'»'=>'-',
		'«'=>'-',
		':'=>'-',
		'='=>'-',
		'\t'=>'',
		'\r'=>'',
		'\n'=>'',
		'\\'=>'-',
		'&'=>'-',
		'/'=>'-',
		"'"=>'-',
		'"'=>'-',
		' '=>'-',
		'“'=>'-',
		'”'=>'-'
	);

	/**
	 * @deprecated since version 3.4
	 */
	static function rus_eng($rustext){
		return self::rusEng($rustext);
	}
	static function rusEng($rustext){
		if (core::$charset!=core::UTF8)$rustext=iconv(core::$charset,core::UTF8,$rustext);
		$trans=array(
			'а'=>'a',
			'б'=>'b',
			'в'=>'v',
			'г'=>'g',
			'д'=>'d',
			'е'=>'e',
			'ё'=>'e',
			'ж'=>'zh',
			'з'=>'z',
			'и'=>'i',
			'й'=>'j',
			'к'=>'k',
			'л'=>'l',
			'м'=>'m',
			'н'=>'n',
			'о'=>'o',
			'п'=>'p',
			'р'=>'r',
			'с'=>'s',
			'т'=>'t',
			'у'=>'u',
			'ф'=>'f',
			'х'=>'x',
			'ц'=>'c',
			'ч'=>'ch',
			'ш'=>'sh',
			'щ'=>'sh',
			'ъ'=>'',
			'ы'=>'y',
			'ь'=>'',
			'э'=>'e',
			'ю'=>'yu',
			'я'=>'ya',
			'А'=>'a',
			'Б'=>'b',
			'В'=>'v',
			'Г'=>'g',
			'Д'=>'d',
			'Е'=>'e',
			'Ё'=>'e',
			'Ж'=>'j',
			'З'=>'z',
			'И'=>'i',
			'Й'=>'i',
			'К'=>'k',
			'Л'=>'l',
			'М'=>'m',
			'Н'=>'n',
			'О'=>'o',
			'П'=>'p',
			'Р'=>'r',
			'С'=>'s',
			'Т'=>'t',
			'У'=>'u',
			'Ф'=>'f',
			'Х'=>'x',
			'Ц'=>'c',
			'Ч'=>'ch',
			'Ш'=>'sh',
			'Щ'=>'sh',
			'Ъ'=>'',
			'Ы'=>'y',
			'Ь'=>'',
			'Э'=>'e',
			'Ю'=>'yu',
			'Я'=>'ya',
			' '=>'',
			'?'=>''
		);
		$eng=strtr($rustext,$trans);
		if (\strlen($eng)==0 && \strlen($rustext)!=0)$eng='-';
		if (core::$charset!=core::UTF8)$eng=iconv(core::UTF8,core::$charset,$eng);
		return $eng;
	}

	static function without($symbol){
		unset(self::$transArr[$symbol]);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function lower($text){
		if (core::$debug)debug::trace('translate lower deprecated. Use input::lower',error::ERROR);
		return input::lower($text);
	}

	/**
	 * Translit string
	 * @param string $text
	 * @return string translit string
	 * @todo add standarts of translit
	 */
	static function translit($text){
		$text=input::lower($text);
		if (core::$charset!=core::UTF8)$text=iconv(core::$charset,core::UTF8,$text);
		return \trim(\str_replace(array("\t","\r\n","\r","\n",'--'),array('-','-','-','-','-'),\strtr(\trim($text),self::$transArr)),'-');
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function rus_url($text){
		return self::rusUrl($text);
	}
	static function rusUrl($text){
		return \trim(\str_replace('--','-', \strtr(input::lower($text),self::$specArr)),'-');
	}

	static $seokeywords=array(); // word - page

	static $seopage=\false;

	static function seo_link($text){
		return self::seoLink($text);
	}
	/**
	 * found any of words and chenge to link phrase
	 * @param string $text
	 * @return string
	 */
	static function seoLink($text){
		if (self::$seopage)$text.='<p class="hide">Оригинал взят с сайта <a href="//'.self::$seopage.'">'.self::$seopage.'</a></p>';
		foreach (self::$seokeywords as $word=>$page){
			if ($found=\strpos($text,$word)){
				return \substr_replace($text,'<a style="color: inherit;text-decoration: none;" href="//'.$page.'">'.$word.'</a>',$found,\strlen($word));
			}
		}
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function change_keyboard($str, $to_rus=\true,$utf8=\false){
		return self::changeKeyboard($str,$to_rus,$utf8);
	}
	/**
	 * Change phrase with keyboard layout
	 * @param string $str input string
	 * @param boolean $to_rus true - translate to rus from eng, false - to eng from rus
	 * @paran boolean $utf8 - is your charset is utf8? only for class use parameter
	 * @return string
	 */
	static function changeKeyboard($str, $to_rus=\true,$utf8=\false){
		if (!$utf8 && core::$charset!=core::UTF8)$str=iconv(core::$charset,core::UTF8,$str);
		$trans=array('а'=>'f','б'=>',','в'=>'d','г'=>'u','д'=>'l','е'=>'t','ё'=>'`','ж'=>';','з'=>'p','и'=>'b','й'=>'q','к'=>'r','л'=>'k','м'=>'v','н'=>'y','о'=>'j','п'=>'g',
		'р'=>'h','с'=>'c','т'=>'n','у'=>'e','ф'=>'a','х'=>'[','ц'=>'w','ч'=>'x','ш'=>'i','щ'=>'o','ъ'=>']','ы'=>'s','ь'=>'m','э'=>"'",'ю'=>'.','я'=>'z',
		'А'=>'F','Б'=>'<','В'=>'D','Г'=>'U','Д'=>'L','Е'=>'T','Ё'=>'~','Ж'=>':','З'=>'P','И'=>'B','Й'=>'Q','К'=>'R','Л'=>'K','М'=>'V','Н'=>'Y','О'=>'J','П'=>'G','Р'=>'H','С'=>'C',
		'Т'=>'N','У'=>'E','Ф'=>'A','Х'=>'{','Ц'=>'W','Ч'=>'X','Ш'=>'I','Щ'=>'O','Ъ'=>'}','Ы'=>'S','Ь'=>'M','Э'=>'"','Ю'=>'>','Я'=>'Z',','=>'?');
		if ($to_rus)$trans=\array_flip($trans);
		$out=\strtr($str,$trans);
		if (!$utf8 && core::$charset!=core::UTF8)$out=iconv(core::UTF8,core::$charset,$out);
		return $out;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function smart_keyboard_word($str,$utf8=\false){
		self::smartKeyboardWord($str,$utf8);
	}
	/**
	 * Transform word to keyboard layout smarty
	 * @param string $str
	 * @return string transform string
	 */
	static function smartKeyboardWord($str,$utf8=\false){
		if (!$utf8 && core::$charset!=core::UTF8)$str=\iconv(core::$charset,core::UTF8,$str);
		$length=\mb_strlen($str,'utf-8');
		$weight_rus=0;
		$weight_eng=0;
		$eng_chars=0;
		$rus_chars=0;
		for ($i=0;$i<$length;$i++){
			$letter=\mb_substr($str,$i,1,'utf-8');
			$letter=\mb_strtolower($letter,'utf-8');
			switch($letter){
				case 'q':
				case 'w':
				case 'e':
				case 'r':
				case 'p':
				case 'd':
				case 'g':
				case 'h':
				case 'k':
				case 'l':
				case 'x':
				case 'c':
				case 'v':
				case 'n':
					$eng_chars++;
					break;
				case 't':
				case '[':
				case '{':
				case ']':
				case '}':
				case 's':
				case 'f':
				case 'j':
				case ';':
				case ':':
				case "'":
				case '"':
				case 'z':
				case 'b':
				case 'm':
				case ',':
				case '<':
				case '.':
				case '>':
				case '`':
				case '~':
					$eng_chars++;
					$weight_rus++;
					break;
				case 'y':
				case 'u':
				case 'i':
				case 'o':
				case 'a':
					$eng_chars++;
					$weight_eng++;
					break;

				case 'й':
				case 'ц':
				case 'у':
				case 'к':
				case 'з':
				case 'п':
				case 'р':
				case 'л':
				case 'д':
				case 'ч':
				case 'с':
				case 'м':
				case 'т':
					$rus_chars++;
					break;
				case 'е':
				case 'х':
				case 'ъ':
				case 'ы':
				case 'а':
				case 'о':
				case 'ж':
				case 'э':
				case 'я':
				case 'и':
				case 'ь':
				case 'б':
				case 'ю':
				case 'ё':
					$rus_chars++;
					$weight_rus++;
					break;
				case 'н':
				case 'г':
				case 'ш':
				case 'щ':
				case 'ф':
					$rus_chars++;
					$weight_eng++;
					break;
				case '0':
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
				case '6':
				case '7':
				case '8':
				case '9':
				case '0':
				case '-':
				case '(':
				case ')':
				case '+':
				case '=':
					return $str;
			}
		}
		$out=$str;
		if ($rus_chars>0 && $weight_eng>=$weight_rus)$out= self::change_keyboard($str,\false,\true);
		if ($eng_chars>0 && $weight_rus>=$weight_eng)$out= self::change_keyboard($str,\true,\true);
		if (!$utf8 && core::$charset!=core::UTF8)$out=\iconv(core::UTF8,core::$charset,$out);
		return $out;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function smart_keyboard($str){
		return self::smartKeyboard($str);
	}
	/**
	 * Transform phrase to keyboard layout
	 * @param string $str phrase
	 * @return string
	 */
	static function smartKeyboard($str){
		if (core::$charset!=core::UTF8)$str=\iconv(core::$charset,core::UTF8,$str);
		$words=\explode(' ',$str);
		$out=array();
		foreach ($words as $item){
			$out[]=self::smartKeyboardWord($item,\true);
		}
		$out= \implode(' ',$out);
		if (core::$charset!=core::UTF8)$out=\iconv(core::UTF8,core::$charset,$out);
		return $out;
	}

	static function mat($str,$trans=\false){
		if (core::$charset!=core::UTF8)$str=\iconv(core::$charset,core::UTF8,$str);
		$pos=0;
		if (\file_exists($file=__DIR__.'/global-config/mat.php'))include_once $file;
		if (\file_exists($file='config/mat.php'))include_once $file;
		foreach ($dict as $val){
			if ($pos=\preg_match('/'.$val['word'].'/imu', $str)>-1){
				if (!$trans)return $pos;
				if (isset($val[$trans]))$str=\preg_replace('/'.$val['word'].'/imu',$val[$trans],$str);
			}
		}
		if (!$trans)return \false;
		if (core::$charset!=core::UTF8)$str=\iconv(core::UTF8,core::$charset,$str);
		return $str;
	}

	/**
	 * @deprecated since version 3.4 use plural method
	 */
	static function num_ending($number, $ending1,$ending4,$ending5=\null){
		return self::plural($number,$ending1,$ending4,$ending5);
	}
	/**
	 * Ending of word for count of elements
	 * @param $number int count
	 * @param $ending1 1 count phrase,
	 * @param $ending4 4 counts phrase,
	 * @param $ending5 5 counts phrase,
	 * @return string
	 */
	static function plural($number, $ending1,$ending4,$ending5=\null){
		if (!\is_numeric($number))$number=\str_replace(',','.',$number);
		if (\intval($number)!=\floatval($number))return $ending4;
		if ($ending5===\null)$ending5=$ending4;
		$number %=100;
		if ($number>=11 && $number<=19)return $ending5;
		$number %= 10;
		switch ($number){
			case 1: return $ending1;
			case 2:
			case 3:
			case 4: return $ending4;
		}
		return $ending5;
	}

	static function grammar($str){
		if (core::$charset!=core::UTF8)$str=\iconv(core::$charset,core::UTF8,$str);
		$str=\strtr($str,array('ё'=>'е',' ,'=>', '));
		$p=array();$r=array();
		if (\file_exists($file=__DIR__.'/global-config/grammar.php'))include_once $file;
		if (\file_exists($file='config/grammar.php'))include_once $file;
		$out= \preg_replace($p,$r,$str);
		if (core::$charset!=core::UTF8)$out=\iconv(core::UTF8,core::$charset,$out);
		return $out;
	}
}