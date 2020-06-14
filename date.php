<?php
namespace c;

/**
 * Date transform and render class
 * @author Kosmom <Kosmom.ru>
 */
class date{
	private static $format='d.m.Y';
	/**
	 * @deprecated since version 3.4
	 */
	static function set_format($format){
		return self::setFormat($format);
	}
	static function setFormat($format){
		if ($format!='')self::$format=$format;
	}
	static function smartdate($ddmmyy_hhmi){
		if (date('Y-m-d')==substr($ddmmyy_hhmi,0,10))return input::iconv('Сегодня').' '.substr($ddmmyy_hhmi,11,5);
		if (date('Y-m-d',time()-60*60*24)==substr($ddmmyy_hhmi,0,10))return input::iconv('Вчера').' '.substr($ddmmyy_hhmi,11,5);
		return substr($ddmmyy_hhmi,8,2).'.'.substr($ddmmyy_hhmi,5,2).'.'.substr($ddmmyy_hhmi,0,4);
	}
	static function showdate($parameter='now',$from="now"){
		return date(self::$format,strtotime($parameter,strtotime($from)));
	}
	static function weekday($from='now'){
		$date=strtotime($from);
		if ($date===false)throw new \Exception('date recognize error');
		return date('N',$date);
	}
	static function is_weekend($from='now'){
		return self::weekday($from)>5;
	}
	static function to_date($val){
		return new \DateTime($val);
	}
	/**
	 * Pretty russian date formatter support Д,д,л,Ф,ф,М output formats
	 * @param string $outFormat output format support Д,д,л,Ф,ф,М
	 * @param string|int|null $time timestamp or format string, if input_format isset
	 * @param null|string $inputFormat input format of datetime
	 * @return string
	 */
	static function date($outFormat='d.m.Y',$time=null,$inputFormat=null){
		if (core::$charset!=core::UTF8)$outFormat=iconv(core::$charset,core::UTF8,$outFormat);
		if ($inputFormat===null){
			if ($time===null)$time=time();
		}else{
			$h=(($pos=strpos($inputFormat,'HH'))!==false)?(int)substr($time,$pos,2):date('H');
			$i=date('i');
			if (($pos=strpos($inputFormat,'II'))!==false)$i=(int)substr($time,$pos,2);
			if (($pos=strpos($inputFormat,'MI'))!==false)$i=(int)substr($time,$pos,2);
			$s=(($pos=strpos($inputFormat,'SS'))!==false)?(int)substr($time,$pos,2):date('s');
			$d=(($pos=strpos($inputFormat,'DD'))!==false)?(int)substr($time,$pos,2):date('j');
			$m=(($pos=strpos($inputFormat,'MM'))!==false)?(int)substr($time,$pos,2):date('n');
			$Y=(($pos=strpos($inputFormat,'YYYY'))!==false)?(int)substr($time,$pos,4):date('Y');
			if ($m){
				$days=cal_days_in_month(CAL_GREGORIAN,$m,$Y);
				if ($d>$days)$d=$days;
			}
			$time=mktime($h,$i,$s,$m,$d,$Y);
		}
		$out=$outFormat;
		if (empty($outFormat))return $time;
		//д
		$out=self::dateReplaceBlock($out,'д',array(
			1=>'Пн',
			2=>'Вт',
			3=>'Ср',
			4=>'Чт',
			5=>'Пт',
			6=>'Сб',
			7=>'Вс'
		),'N',$time);

		//Д
		$out=self::dateReplaceBlock($out,'Д',array(
			1=>'Пон',
			2=>'Втр',
			3=>'Срд',
			4=>'Чтр',
			5=>'Птн',
			6=>'Суб',
			7=>'Вос'
		),'N',$time);

		//л
		$out=self::dateReplaceBlock($out,'л',array(
			1=>'понедельник',
			2=>'вторник',
			3=>'среда',
			4=>'четверг',
			5=>'пятница',
			6=>'суббота',
			7=>'воскресение'
		),'N',$time);

		//Ф
		$out=self::dateReplaceBlock($out,'Ф',array(
			1=>'Январь',
			2=>'Февраль',
			3=>'~арт',
			4=>'Апрель',
			5=>'~ай',
			6=>'Июнь',
			7=>'Июль',
			8=>'Август',
			9=>'Сентябрь',
			10=>'Октябрь',
			11=>'Ноябрь',
			12=>'Декабрь',
		),'n',$time);

		//ф
		$out=self::dateReplaceBlock($out,'ф',array(
			1=>'Января',
			2=>'Февраля',
			3=>'~арта',
			4=>'Апреля',
			5=>'~ая',
			6=>'Июня',
			7=>'Июля',
			8=>'Августа',
			9=>'Сентября',
			10=>'Октября',
			11=>'Ноября',
			12=>'Декабря',
		),'n',$time);

		//М
		$out=self::dateReplaceBlock($out,'М',array(
			1=>'Янв',
			2=>'Фев',
			3=>'Мрт',
			4=>'Апр',
			5=>'Май',
			6=>'Июн',
			7=>'Июл',
			8=>'Авг',
			9=>'Сен',
			10=>'Окт',
			11=>'Ноя',
			12=>'Дек',
		),'n',$time);
		$out=str_replace('~','М',$out);
		if (core::$charset!=core::UTF8)$out=iconv(core::UTF8,core::$charset,$out);
		return date($out,$time);
	}
	private static function dateReplaceBlock($out,$letter,$array,$date_letter,$time){
		$count=0;
		if (mb_strpos($out,$letter,0,core::$charset)===false)return $out;
		$out=str_replace('\\'.$letter,'{{````}}',$out,$count);
		$out=str_replace($letter,$array[date($date_letter,$time)],$out);
		if ($count)return str_replace('{{````}}','\\'.$letter,$out,$count);
		return $out;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function rus_month($number=null){
		return self::rusMonth($number);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function rusMonth($number=null){
		if ($number===null)$number=date('n');
		$number=(int)$number;
		if ($number==1)return 'января';
		if ($number==2)return 'февраля';
		if ($number==3)return 'марта';
		if ($number==4)return 'апреля';
		if ($number==5)return 'мая';
		if ($number==6)return 'июня';
		if ($number==7)return 'июля';
		if ($number==8)return 'августа';
		if ($number==9)return 'сентября';
		if ($number==10)return 'октября';
		if ($number==11)return 'ноября';
		if ($number==12)return 'декабря';
		return $number;
	}
	static function to_timestamp($value){
		if (is_numeric($value))return $value;
		if ($value instanceof \DateTime)return $value->getTimestamp();
		return strtotime($value);
	}
	static function to_datetime($value){
		if ($value instanceof \DateTime)return $value;
		if (is_numeric($value))return new \DateTime(date('d.m.Y H:i:s',$value));
		return new \DateTime($value);
	}
}