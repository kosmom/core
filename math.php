<?php
namespace c;

/**
 * Core abstract math class
 * @author Kosmom <Kosmom.ru>
 */
class math{
	static function mean($array){
		return \array_sum($array)/\count($array);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function round_mean($array, $digits = 1){
		return \round(self::mean($array), $digits);
	}
	static function roundMean($array, $digits = 1){
		return \round(self::mean($array), $digits);
	}
	private static function sdSquare($x, $mean){
		return \pow($x - $mean, 2);
	}
	static function sd($array){
		return \sqrt(\array_sum(\array_map('self::sd_square', $array, \array_fill(0, \count($array), (\array_sum($array)/\count($array)))))/(\count($array)-1));
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function round_sd($array, $digits = 1){
		return round(self::sd($array), $digits);
	}
	static function roundSd($array, $digits = 1){
		return round(self::sd($array), $digits);
	}
	static function factorial($n){
		if ($n <= 1)return 1;
		return $n * self::factorial($n - 1);
	}
	static function roundFirstNumber($number,$precision=0){
		$sign=1;
		if ($number<0){
			$sign=-1;
			$number=\abs($number);
		}
		if ($number>1)return $sign*\round($number,$precision);
		if ($number==0)return 0;
		return $sign*\round($number,-\floor(\log10($number)));
	}
}