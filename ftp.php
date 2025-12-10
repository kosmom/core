<?php
namespace c;

/**
 * Work with FTP
 * @author Kosmom <Kosmom.ru>
 */
class ftp{

	/**
	* Recursive remove directory
	* @param string $dir
	* @param object $connId ftp connect handle
	* @return int removed files count
	*/
	static function rmdir($dir,$connId){
		$files=0;
		$ar_files=\ftp_nlist($connId,$dir);
		if (\is_array($ar_files)){
			foreach ($ar_files as $file){
				$st_file=\basename($file);
				if($st_file=='.' || $st_file=='..')continue;
				if (\ftp_size($connId, $dir.'/'.$st_file) == -1){
					$files+=self::rmdir($dir.'/'.$st_file,$connId);
				}else{
					\ftp_delete($connId,$dir.'/'.$st_file);
					$files++;
				}
			}
		}
		\ftp_rmdir($connId,$dir);
		return $files;
	}
	/**
	* Same as rmdir
	* @param string $dir
	* @param object $connId ftp connect handle
	* @return int removed files count
	*/
	static function remove($dir,$connId){
	  return self::rmdir($dir,$connId);
	}
	/**
	* Empty directory
	* @param string $dir
	* @param object $connId ftp connect handle
	* @return int removed files count
	*/
	static function empt($dir,$connId){
		$files=0;
		$ar_files=\ftp_nlist($connId,$dir);
		if (!\is_array($ar_files))return;
		foreach ($ar_files as $file){
			$st_file=\basename($file);
			if($st_file=='.' || $st_file=='..')continue;
			if (\ftp_size($connId,$dir.'/'.$st_file)==-1){
				$files+=self::rmdir($dir.'/'.$st_file,$connId);
			}else {
				\ftp_delete($connId,$dir.'/'.$st_file);
				$files++;
			}
		}
		return $files;
	}
	/**
	* @deprecated since version 3.4
	*/
	static function copy_to_ftp($srcDir, $dstDir,$connId){
	  return self::copyToFtp($srcDir,$dstDir,$connId);
	}
	/**
	* Recursivety copy directory
	* @param string $srcDir source directory
	* @param string $dstDir destitation directory
	* @param object $connId ftp connect handle
	*/
	static function copyToFtp($srcDir,$dstDir,$connId){
		@\ftp_mkdir($connId,\dirname($dstDir));
		$d=@\dir($srcDir);
		if (!$d){
			return \ftp_put($connId,$dstDir,$srcDir,\FTP_BINARY);
		}
		while($file=$d->read()){
			if ($file=='.' || $file=='..')continue;
			if (\is_dir($srcDir.'/'.$file)){
				if (!@ftp_chdir($connId, $dstDir.'/'.$file))\ftp_mkdir($connId,$dstDir.'/'.$file);
				self::copyToFtp($srcDir.'/'.$file,$dstDir.'/'.$file,$connId);
			}else{
				\ftp_put($connId,$dstDir.'/'.$file,$srcDir.'/'.$file,\FTP_BINARY);
			}
		}
		$d->close();
	}
	
	static function mkdir($dstDir,$connId){
		$dstDir=\str_replace('\\','/',$dstDir);
		$segments=\explode('/',$dstDir);
		$dir='';
		foreach ($segments as $segment){
			$dir.='/'.$segment;
			@\ftp_mkdir($connId,$dir);
		}
	}
}