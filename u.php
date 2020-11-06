<?php
namespace c;

// zip sample
/*
if (c\u::files('filename')){
c\u::extenses('doc');
c\u::maxfilesize('5000000');
if (c\u::test(true)){
if (!c\u::zip())c\error::redirect();
c\u::rename('file.zip');
c\u::copy('filepath');
c\error::add('success',c\error::SUCCESS,'');
}
}
*/
// multiupload sample
/*
<input type="file" name="filename[]" multiple="true">

c\u::many_files('filename');
while (c\u::each_file('filename')){
if (c\u::files(false)){
if (c\u::test(true)){
c\u::copy('filepath');
}
}
}
*/
/**
 * Upload class
 * @author Kosmom <Kosmom.ru>
 */
class u{
	private static $file='';
	static $extenses=array('gif','jpeg','jpg','png');
	static $maxsize=5000000;
	static $important=\true;
	static $rename_if_exist='';
	static $signs=0;
	private static $counter=0;
	private static $folder='.';

	static function filename(){
		return self::$folder.(self::$folder?'/':'').$_FILES[self::$file]['name'];
	}
	static function files($filename=''){
		if ($filename===\false){
			return (\sizeof(($_FILES[self::$file])));
		}elseif ($filename==''){
			return (\sizeof(($_FILES)));
		}else{
			if (!empty($_FILES[$filename]['size']))self::$file=$filename;
			return(!empty($_FILES[$filename]['size']));
		}
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function many_files($file){
	   return self::manyFiles($file);
	}
	static function manyFiles($file){
		$tmpfiles=array();
		if (empty($_FILES[$file]['tmp_name'][0])){
			if (isset($_FILES[$file]['tmp_name'])){
				$_FILES[$file]=array(0=>$_FILES[$file]);
			}
			return \false;
		}
		foreach ($_FILES[$file]['tmp_name'] as $key=>$val){
			$tmpfiles[$key]=array('name'=>$_FILES[$file]['name'][$key],'type'=>$_FILES[$file]['type'][$key],'tmp_name'=>$_FILES[$file]['tmp_name'][$key],'error'=>$_FILES[$file]['error'][$key],'size'=>$_FILES[$file]['size'][$key]);
		}
		$_FILES[$file]=$tmpfiles;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function each_file($file){
		return self::eachFile($file);
	}
	static function eachFile($file){
		if (self::$counter>=sizeof($_FILES[$file])){
			self::$counter=0;
			unset($_FILES['core_uplad_class']);
			self::$file='';
			return \false;
		}
		$_FILES['core_upload_class']=$_FILES[$file][self::$counter++];
		self::$file='core_upload_class';
		return \true;
	}
	/**
	 * Set extensions to validator
	 * @param string extensions... multi
	 */
	static function extenses(){
		$arr1=\func_get_args();
		$arr2=array();
		foreach ($arr1 as $value){
			if (\is_array($value)){
				foreach ($value as $val){
					$arr2[]=\strtolower($val);
				}
			}else{
				$arr2[]=\strtolower($value);
			}
		}
		self::$extenses=$arr2;
	}
	static function maxfilesize($filesize){
		self::$maxsize=intval($filesize);
	}
	static function important($value){
		self::$important=(bool)$value;
	}
	static function getExtense($name){
		return \strtolower(\substr($_FILES[$name]['name'],\strripos($_FILES[$name]['name'],'.')+1));
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function get_extense($name){
		return \strtolower(\substr($_FILES[$name]['name'],\strripos($_FILES[$name]['name'],'.')+1));
	}
	static function test($important=\false){
		self::$important=(bool)$important;
		$file=$_FILES[self::$file];
		if($file['error']==1){
			error::add(translate::t('Error file upload, file {name} was not filly load. Maybe filesize more then limit',array('name'=>$file['name'])),(self::$important?error::ERROR:error::WARNING));
			unset($_FILES[self::$file]);
			return \false;
		}elseif($file['error']!=4){
			if ($file['error']!=0){
				error::add(translate::t('Error file upload {name}',array('name'=>$file['name'])),(self::$important?error::ERROR:error::WARNING));
				unset($_FILES[self::$file]);
				return \false;
			}elseif(!\in_array(\strtolower(\substr($file['name'],\strripos($file['name'],'.')+1)),self::$extenses)){
				error::add(translate::t('Wrong file extension! ({extension})',array('extension'=>\substr($file['name'],\strripos($file['name'],'.')+1))),(self::$important?error::ERROR:error::WARNING));
				unset($_FILES[self::$file]);
				return \false;
			}elseif($file['size']>self::$maxsize){
				error::add(translate::t('File size more than max limit {name}, {current_filesize_kb} kb. Max limit is: {max_filesize_kb} kb',array(
					'name'=>$file['name'],
					'current_filesize_kb'=>intval($file['size']/1000),
					'max_filesize_kb'=>intval(self::$maxsize/1000)
					)),(self::$important?error::ERROR:error::WARNING));
				unset($_FILES[self::$file]);
				return \false;
			}
		}else{
			error::add(translate::t('Error file upload, file was not include'),(self::$important?error::ERROR:error::WARNING));
			return \false;
		}
		return \true;
	}
	static function rename($name){
		$_FILES[self::$file]['name']=$name;
	}
	static function getname(){
		return $_FILES[self::$file]['name'];
	}
	/**
	 * Get tmp name of file
	 * @return string
	 */
	static function get_tmp_name(){
		return $_FILES[self::$file]['tmp_name'];
	}
	/**
	 * Get tmp name of file
	 * @return string
	 */
	static function getTmpName(){
		return $_FILES[self::$file]['tmp_name'];
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function rename_if_exist($replace){
		self::$rename_if_exist=$replace;
	}
	/**
	 * Replace method if file already exists in folder
	 * @param string $replace 'number','random',''
	 */
	static function renameIfExist($replace){
		self::$rename_if_exist=$replace;
	}
	/**
	 * Copy active file in folder and rename with optinos
	 */
	static function copy($folder=''){
		if (self::$rename_if_exist=='number'){
			$_FILES[self::$file]['name']=self::freenumber($_FILES[self::$file]['name'],$folder,self::$signs);
		}elseif (self::$rename_if_exist=='random'){
			$_FILES[self::$file]['name']=self::freerandom($_FILES[self::$file]['name'],$folder,self::$signs);
		}
		if (!(copy($_FILES[self::$file]['tmp_name'],$folder.'/'.$_FILES[self::$file]['name']))){
			echo $folder.'/'.$_FILES[self::$file]['name'];
		}
		self::$folder=$folder;
		return $_FILES[self::$file]['name'];
	}
	/**
	 * Get extension of file
	 */
	private static function filetype($url){
		$path=\pathinfo($url);
		return $path['extension'];
	}
	/**
	 * Get free next name with add numbers
	 */
	static function freenumber($filename='',$folder='',$signs=0){
		$filetype=self::filetype($filename);
		if ($filetype!='')$filetype='.'.$filetype;
		$newname=self::filename();
		$i=0;
		$starttrim=0;
		while (\file_exists($folder.'/'.$newname.$filetype)){
			$i++;
			$newname=self::filename().$i;
			if ($signs>0)$starttrim=\strlen($newname.$filetype)-$signs;
			if ($starttrim>0)$newname=substr($newname,$starttrim);
		}
		return $newname.$filetype;
	}

	/**
	 * Get free random name with add sings
	 * @return string
	 */
	static function freerandom($filename='',$folder='',$signs=10){
		$filetype=self::filetype($filename);
		if ($filetype!='')$filetype='.'.$filetype;
		$newname=self::filename($filename);
		while (\file_exists($folder.'/'.$newname.$filetype)){
			$newname=self::randomchar($signs);
		}
		return $filename.$filetype;
	}
	static function randomchar($length){
		list($usec, $sec) = \explode(' ', \microtime());
		\srand(((float)$usec + (float)$sec));
		$index = 1;
		$string = '';
		while ($index <= $length){
			$temp_char = \mt_rand(97,122);
			$string .= \chr($temp_char);
			$index++;
		}
		return $string;
	}
	static function signs($count){
		self::$signs=\abs(\intval($count));
	}
	static function zip($name){
		$tmp=\dirname($_FILES[self::$file]['tmp_name']).'/'.\md5(\uniqid(\rand(),\TRUE));
		$zip = new \ZipArchive();
		if ($zip->open($tmp, \ZipArchive::CREATE)!==\TRUE)throw new \Exception('Error while creating of zip file');
		$zip->addFile(self::get_tmp_name(),iconv('windows-1251', 'cp866', self::getname()));
		$zip->close();
		$_FILES[self::$file]['tmp_name']=$tmp;
		return \true;
	}
}