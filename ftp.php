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
	* @return bool
	*/
	static function rmdir($dir,$conn_id){
		$ar_files = ftp_nlist($conn_id, $dir);
		if (is_array($ar_files)){
			foreach ($ar_files as $file){
				$st_file = basename($file);
				if($st_file == '.' || $st_file == '..') continue;
				if (ftp_size($conn_id, $dir.'/'.$st_file) == -1){
					self::rmdir( $dir.'/'.$st_file,$conn_id);
				} else {
					ftp_delete($conn_id,  $dir.'/'.$st_file);
				}
			}
		}
		ftp_rmdir($conn_id, $dir);
	}
	/**
	* Same as rmdir
	* @param string $dir
	* @return bool
	*/
	static function remove($dir,$conn_id){
	  return self::rmdir($dir,$conn_id);
	}
	/**
	* Empty directory
	* @param string $dir
	*/
	static function empt($dir,$conn_id){
	  $ar_files = ftp_nlist($conn_id, $dir);
		if (!is_array($ar_files))return;
		foreach ($ar_files as $file){
			$st_file = basename($file);
			if($st_file == '.' || $st_file == '..') continue;
			if (ftp_size($conn_id, $dir.'/'.$st_file) == -1){
				self::rmdir( $dir.'/'.$st_file,$conn_id);
			} else {
				ftp_delete($conn_id,  $dir.'/'.$st_file);
			}
		}
	}
	/**
	* @deprecated since version 3.4
	*/
	static function copy_to_ftp($srcDir, $dstDir,$connId) {
	  return self::copyToFtp($srcDir,$dstDir,$connId);
	}
	/**
	* Recursivety copy directory
	* @param string $srcDir source directory
	* @param string $dstDir destitation directory
	* @param object $connId ftp connect handle
	*/
	static function copyToFtp($srcDir, $dstDir,$connId) {
		@ftp_mkdir($connId, dirname($dstDir));
		$d = dir($srcDir);
		if (!$d){
			ftp_put($connId, $dstDir, $srcDir, FTP_BINARY);
			return true;
		}
		while($file = $d->read()) {
			if ($file == '.' || $file == '..') continue;
			if (is_dir($srcDir.'/'.$file)) {
				if (!@ftp_chdir($connId, $dstDir.'/'.$file))ftp_mkdir($connId, $dstDir.'/'.$file);
				self::copyToFtp($srcDir.'/'.$file, $dstDir.'/'.$file,$connId);
			} else {
				ftp_put($connId, $dstDir.'/'.$file, $srcDir.'/'.$file, FTP_BINARY);
			}
		}
		$d->close();
	}
}