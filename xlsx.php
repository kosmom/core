<?php
namespace c;

/*
// export sample
$sql="select * from table";
$rs=c\db::ea($sql);
$columns=array('id'=>'id',
	'tree_id'=>'column 1',
	'description'=>'column 2'
		);
array_unshift($rs,$columns);
c\xlsx::$columns=array_keys($columns);
c\xlsx::writesheet($rs);
c\xlsx::generate('export_'.time());

//export sample from table class
c\tables::$header=array('id'=>'id',
	'tree_id'=>'column 1',
	'description'=>'column 2'
);
$sql="SELECT * FROM table";
c\tables::$data=c\db::ea($sql);
c\xlsx::writesheet_from_table($table_params);
c\xlsx::generate('export_'.time());
}

//import sample
$filedata=c\xlsx::get($_FILES['upload']['tmp_name']);
*/

/**
 * Work with Excel. Need source.xlsx file to generate excel
 * @author Kosmom <Kosmom.ru>
 */
class xlsx{
	const DEFAULT_FORMATS=array(0=>'text',9=>'percent',10=>'percent', 14=>'date',15=>'date',16=>'date',17=>'date',18=>'date',19=>'date',20=>'date',21=>'date',22=>'date');
	private static $unic=array();
	private static $colnumber=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU');
	static $outbuffer='';
	private static $unicCount=-1;
	private static $count=-1;
	private static $xlsxPages=array();
	static $sst=array();
	static $formats=self::DEFAULT_FORMATS;
	static $columns=array();
	static $header=array();

	static function newfile(){
		self::$unic=array();
		self::$unicCount=-1;
		self::$outbuffer='';
		self::$sst=array();
		self::$formats=self::DEFAULT_FORMATS;
	}

	private static function writestring($rownum,$row,$callback=array()){
		self::$outbuffer.='<row r="'.($rownum + 1).'">';
		foreach(self::$columns as $number=> $column){
			if (isset($callback[$column])){
				$row[$column]=$callback[$column]($row,$column);
			}elseif (isset(tables::$header[$column]['values'])){
				if (isset(tables::$header[$column]['values'][$row[$column]]))$row[$column]=tables::$header[$column]['values'][$row[$column]];
			}
			if (!isset($row[$column])) continue;
			if ($row[$column] === '') continue;

			if ($row[$column] instanceof \DateTime){
				self::$outbuffer.='<c r="'.self::$colnumber[$number].($rownum + 1).'" t="n" s="1"><v>'.(($row[$column]->getTimestamp()+10800)/86400+25569).'</v></c>';
			}elseif (\gettype($row[$column])!='string'){ // && substr($row[$column],0,1) != '0'
				self::$outbuffer.='<c r="'.self::$colnumber[$number].($rownum + 1).'" t="n"><v>'.$row[$column].'</v></c>';
			}else{
				self::$count++;
				if (!isset(self::$unic[$row[$column]])) self::$unic[$row[$column]]=++self::$unicCount;
				self::$outbuffer.='<c r="'.self::$colnumber[$number].($rownum + 1).'" t="s"><v>'.self::$unic[$row[$column]].'</v></c>';
			}
		}
		self::$outbuffer.='</row>';
	}

	/**
	 * generate buffer with data
	 * @param array|int $data input data
	 * @param array $callback array of callback functions for cells
	 */
	static function writesheet($data,$callback=array()){
		self::$outbuffer='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
	<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/><sheetData>';
		$rownum=0;
		if (self::$header){
			self::writestring($rownum,self::$header);
			$rownum=1;
		}
		if (\is_array($data)){
			foreach($data as $row){
				self::writestring($rownum,$row,$callback);
				$rownum++;
			}
		}else{
			while ($row=db::fa($data)){
				self::writestring($rownum,$row,$callback);
				$rownum++;
			}
		}
		self::$outbuffer.='</sheetData><pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';
	}
	static function writeStrings(){
		$buffer='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
	<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.self::$count.'" uniqueCount="'.self::$unicCount.'">';
		foreach(self::$unic as $key=> $v){
		   $buffer.='<si><t>'.input::htmlspecialchars($key).'</t></si>';
		}
		return $buffer.'</sst>';
	}

	/**
	 * Generate xlsx file for output with headers from sample.xlsx
	 * @param string $filename generated filename
	 */
	static function generate($filename='',$out=\true){
		return self::generateXlsx('',$filename,$out);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function generate_xlsx($source='',$filename='',$out=\true){
		return self::generateXlsx($source,$filename,$out);
	}
	static function generateXlsx($source='',$filename='',$out=\true){
		$tempresult=\ini_get('upload_tmp_dir').(\ini_get('upload_tmp_dir')==''?'':'/').'temp.xlsx';
		if (empty(self::$outbuffer)) throw new \Exception('Before generate xlsx need prepare data');
		if (empty($source))$source=__DIR__.'/sample.xlsx';
		if (!\file_exists($source)) throw new \Exception('Source sample file not found');
		if (\file_exists($tempresult))\unlink($tempresult);
		\copy($source,$tempresult);
		$zip=new \ZipArchive();
		if (!$zip->open($tempresult)) throw new \Exception('Source sample file cannot be open');
		$zip->deleteName('xl/worksheets/sheet1.xml');
		$zip->deleteName('xl/sharedStrings.xml');
		$zip->addFromString('xl/worksheets/sheet1.xml',\iconv(core::$charset,'UTF-8',self::$outbuffer));
		$zip->addFromString('xl/sharedStrings.xml',\iconv(core::$charset,'UTF-8',self::writeStrings()));
		$zip->close();
		if ($out)self::display($tempresult,$filename);
	return $tempresult;
	}

	static function generateXlsxByTemplate($source,$data,$filename='',$out=\true){
		$tempresult=\ini_get('upload_tmp_dir').(\ini_get('upload_tmp_dir')==''?'':'/').'temp.xlsx';
		if (\file_exists($tempresult))\unlink($tempresult);
		if (!\file_exists($source)) throw new \Exception('Source sample file not found');
		\copy($source,$tempresult);
		$zip=self::checkFile($tempresult);
		$file=$zip->getFromName('xl/sharedStrings.xml');
		$data=datawork::flatten($data);
		$file=\preg_replace_callback('/\$\{(.*)\}/sU',function($matches) use ($data){
			return input::iconv($data[$matches[1]],\true);
		},$file);
		$zip->deleteName('xl/sharedStrings.xml');
		$zip->addFromString('xl/sharedStrings.xml',$file);
		$zip->close();
		if ($out)self::display($tempresult,$filename);
		return $tempresult;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function writesheet_from_table($input=array(),$tableElement=\null){
		return self::writesheetFromTable($input,$tableElement);
	}
	static function writesheetFromTable($input=array(),$tableElement=\null){
		if ($tableElement===\null)$tableElement=tables::getTableObject();
		if (!isset($input['show_header']))$input['show_header']=$tableElement->show_header;
		if (!isset($input['fill']))$input['fill']=$tableElement->fill;
		if (!isset($input['header'])){
			$input['header']=$tableElement->header;
			foreach ($input['header'] as $key=>$val){
				if (\is_array($val)){
					$input['header'][$key]=$val['name'];
					if (isset($val['fill'])){
						$input['fill'][$key]=$val['fill'];
					}elseif (isset($val['values'])){
						$input['fill'][$key]=function($row,$column) use ($val){
							return isset($val['values'][$row[$column]]) ? $val['values'][$row[$column]] : $row[$column];
						};
					}
				}
			}
		}
		self::$columns=array_keys($input['header']);
		if ($input['show_header'])self::$header=$input['header'];
		self::writesheet($tableElement->data,$input['fill']);
		self::$header=array();
	}
	static function display($source,$filename=''){
		if (empty($filename)) $filename=$source;
		if (!\file_exists($source)) throw new \Exception('Source sample file not found');
		\header("Last-Modified: ".\gmdate("D,d M YH:i:s")." GMT");
		\header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		\header("Pragma: no-cache");
		\header("Content-Type: application/force-download");
		\header("Content-Type: application/octet-stream");
		\header("Content-Type: application/vnd.ms-excel");
		\header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
		\header("Content-Transfer-Encoding: binary");
		while (@\ob_end_flush());
		\readfile($source);
	//header("Content-Length: ".strlen($source));
		die();
	}
	static function getPages($source){
	if (isset(self::$xlsxPages[$source]))return self::$xlsxPages[$source];
		$zip=self::checkFile($source);
		$file=$zip->getFromName('xl/workbook.xml');
		if (!$file)throw new \Exception('Cant get content of Xlsx file');
		$xml=\simplexml_load_string($file);
		$pages=array();
		$bookview=$xml->bookViews->workbookView->attributes();
		$activeTab=(int)$bookview['activeTab'];
		$key=0;
		foreach($xml->sheets->sheet as $val){
			$attrs=$val->attributes();
			//$page_id=(string)$attrs['sheetId'];
			$pages[$key+1]['state']=(string)$attrs['state'];
			if (core::$charset==core::UTF8){
				$pages[$key+1]['name']=(string)$attrs['name'];
			}else{
				$pages[$key+1]['name']=\iconv('UTF-8',core::$charset,(string)$attrs['name']);
			}
			if ($key==$activeTab)$pages[$key+1]['active']=\true;
			$key++;
		}
		self::$xlsxPages[$source]=$pages;
		return $pages;
	}
	private static function checkFile($source){
		$zip = new \ZipArchive;
		if ($zip->open($source) !== \true)throw new \Exception('Wrong file extension. You may set only XLSX files');
		return $zip;
	}
	private static function getXLSX($source,$sheet=1){
	$zip=self::checkFile($source);
		$file=$zip->getFromName('xl/sharedStrings.xml');
		if ($file){
			$xml=(array)\simplexml_load_string($file);
			if (!\is_array($xml['si'])){
				if (core::$charset==core::UTF8){
					self::$sst[]=(string)$xml['si']->t;
				}else{
					self::$sst[]=\iconv('UTF-8',core::$charset,(string)$xml['si']->t);
				}
			}else{
				if (core::$charset==core::UTF8){
					foreach($xml['si'] as $val)self::$sst[]=\str_replace('_x000D_', "\n",(string)$val->t);
				}else{
					foreach($xml['si'] as $val)self::$sst[]=\str_replace('_x000D_', "\n", \iconv('UTF-8',core::$charset,(string)$val->t));
				}
			}
		}
		// read styles
		$file=$zip->getFromName('xl/styles.xml');
		$styles=array();
		$xml=\simplexml_load_string($file);
		// formats
		if ($xml->numFmts->numFmt){
		foreach($xml->numFmts->numFmt as $st){
			$attrs=$st->attributes();
			$format=(string)$attrs['numFmtId'];
			$formatCode=(string)$attrs['formatCode'];
			if ($formatCode=='GENERAL'){
				self::$formats[$format]='text';
			}elseif ($formatCode=='General'){
				self::$formats[$format]='text';
			}elseif ($formatCode=='@'){
				self::$formats[$format]='text';
			}elseif ($formatCode=='0'){
				self::$formats[$format]='number';
			}elseif (\substr_count($formatCode,'#')>0 or \substr_count($formatCode,'$')>0){
				self::$formats[$format]='number';
			}elseif (\substr($formatCode,-1)=='%'){
				self::$formats[$format]='percent';
			}else{
				self::$formats[$format]='date';
			}
		}
		}

		foreach($xml->cellXfs->xf as $st){
			$attrs=$st->attributes();
			$styles[]=(string)$attrs['numFmtId'];
		}
		//print_r(self::$formats);
		//print_r($styles);
		$data=array();
		if (\is_null($sheet)){
			$sheets = self::getPages($source);
			foreach ($sheets as $key=>$i){
				$data[$key]=self::readSheet($key,$zip,$styles);
			}
		}else{
			$data=self::readSheet($sheet,$zip,$styles);
		}
		$zip->close();

		if (core::$debug){
			debug::group('Xlsx class get Xlsx '.$source);
			debug::dir(array('Formats:'=>self::$formats,'Styles:'=>$styles,'Data: '=>$data));
			debug::groupEnd();
		}
		return $data;
	}
	private static function readSheet($sheet=1,$zip,$styles){
		$file=$zip->getFromName('xl/worksheets/sheet'.$sheet.'.xml');
		$xml=\simplexml_load_string($file);
		$data=array();
		$colname=\array_flip(self::$colnumber);

		foreach($xml->sheetData->row as $row){
			$currow=array();
			$lastpos=-1;
			$is_add=\false;
			foreach($row->c as $c){
				$value=(string)$c->v;
				$attrs=$c->attributes();
				\preg_match_all('/([A-Z]+)/',$attrs['r'],$ok);
				$pos=$colname[$ok[1][0]];

				// fill missed cells
				if ($lastpos+1!=$pos){
					for ($i=$lastpos+1;$i<$pos;$i++)$currow[$i]=\null;
				}

				if ($attrs['t'] == 's'){
					$currow[$pos]=self::$sst[$value];
				}else{
					$par=\explode('E',$value); if (isset($par[1]))$value=\floatval($par[0])*\pow(10,\intval($par[1]));
					switch (@self::$formats[$styles[(int)$attrs['s']]]){
						case 'date': 
						    if ($value==''){
							$currow[$pos]=null;
						    }else{
							$d = \floor($value); $t = $value - $d; $currow[$pos]=$value==''?'': \date('d.m.Y H:i:s', ($d>0?($d-25569)*86400:0)+\round($t*86400)-10800); break;
						    }
						case 'percent': if ($value=='')$currow[$pos]=null; else $currow[$pos]=\floatval($value)*100;break;
						default: $currow[$pos]=$value;
					}
				}
				$lastpos=$pos;
				if ($currow[$pos]!='')$is_add=\true;
			}
			if ($is_add)$data[]=$currow;
		}
		//first string is header
		$i=0;
		if ($xml->cols->col){
			foreach($xml->cols->col as $col){
				if (!isset($data[0][$i]))$data[0][$i++]='';
			}
		}
		return $data;
	}
	private static function getCSV($source){
		//$file=iconv('windows-1251','utf-8',file_get_contents($source));
		$file=\file_get_contents($source);
		if (core::$charset!=core::UTF8)$file=\iconv(core::$charset,'utf-8',$file);
		$delimeter=',';
		if (\substr_count($file,';')>\substr_count($file,','))$delimeter=';'; // auto detect delimeter
		$strings=\explode("\r",$file);
		$data=array();
		foreach ($strings as $string){
			$data[]=\str_getcsv($string, $delimeter);
//			$str=explode($delimeter,trim($string));
//			foreach ($str as $key=>$cell){
//				if (substr($cell,0,1)=='"' && substr($cell,-1,1)=='"'){
//					$str[$key]=str_replace('""','"',substr($cell,1,-1));
//				}
//			}
//			$data[]=$str;
		}
		if (core::$debug){
			debug::group('Xlsx class get CSV '.$source);
			debug::dir(array('Data: '=>$data));
			debug::groupEnd();
		}
		return $data;
	}
	static function get($source,$page=1){
		if (!\file_exists($source))  throw new \Exception('file not exists');
		$fileHandle = \fopen($source, "r");
		$string = \fgets($fileHandle, 4);
		\fclose($fileHandle);
		if (\ord($string[0])==208 && \ord($string[1])==207) throw new \Exception('Possible file has XLS extension. Convert it to XLSX or CSV extension'); //РП
		if (\ord($string[0])==80 && \ord($string[1])==75){ // PK
			return self::getXLSX($source,$page);
		}else{
			return self::getCSV($source);
		}
	}
}