<?php
namespace c;

/**
 * Mail send class
 * @author Kosmom <Kosmom.ru>
 */
class mail{
	private static $time;
	private static $mails=array();
	private static $mail_config;
	private static $testAdress=array();
	static function test($adress=array()){
		if ($adress===false){
			self::$testAdress=array();
		}elseif (\is_string($adress)){
			self::$testAdress=\explode(',',$adress);
		}else{
			self::$testAdress=$adress;
		}
	}

	private static function toMailArray($string,$db=''){
		if (\is_array($string))return $string;
		if (\is_string($string) || \is_numeric($string)){
			$string=\trim($string);
			if ($string==''){
				error::add('Error on main send: Not set email address',error::WARNING);
				self::$mails[$db]->ClearAddresses();
				return false;
			}
			return \explode(';',$string);
		}
		if ($string===false)return false;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function set_cc($adress=array(),$db=''){
		return self::setCc($adress,$db);
	}
	static function setCc($adress=array(),$db=''){
		if (empty($adress))return false;
		if ($db=='')$db=core::$data['mail'];
		if (self::$testAdress)return false;
		if (empty(self::$mails[$db]))self::connect($db);
		if (\is_string($adress))$adress=explode(',',$adress);
		if (isset(core::$data['mail_modify_callback']))$adress=core::$data['mail_modify_callback']($adress);
		foreach ($adress as $value){
			self::$mails[$db]->AddCC($value);
		}
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function set_bcc($adress=array(),$db=''){
		return self::setBcc($adress,$db);
	}
	static function setBcc($adress=array(),$db=''){
		if (empty($adress))return false;
		if ($db=='')$db=core::$data['mail'];
		if (self::$testAdress)return false;
		if (empty(self::$mails[$db]))self::connect($db);
		if (\is_string($adress))$adress=\explode(',',$adress);
		if (isset(core::$data['mail_modify_callback']))$adress=core::$data['mail_modify_callback']($adress);
		foreach ($adress as $value){
			self::$mails[$db]->AddBCC($value);
		}
	}
	static function ClearAttachments($db=''){
		if ($db=='')$db=core::$data['mail'];
		if (empty(self::$mails[$db]))self::connect($db);
		self::$mails[$db]->ClearAttachments();
	}

	static function AddStringAttachment($text,$totalFilename,$cid='',$db=''){
		if ($db=='')$db=core::$data['mail'];
		if (empty(self::$mails[$db]))self::connect($db);
		self::$mails[$db]->AddStringAttachment($text,$totalFilename,$cid);
	}

	static function AddAttachment($sourceFilename,$totalFilename='', $cid='',$db=''){
		if ($db=='')$db=core::$data['mail'];
		if (empty(self::$mails[$db]))self::connect($db);
		if ($totalFilename=='')$totalFilename=\basename($sourceFilename);
		if (!\file_exists($sourceFilename))  throw new \Exception('file '.$sourceFilename.' not found');
		self::$mails[$db]->AddAttachment($sourceFilename,$totalFilename,$cid);
	}

	static function embedAttachment($text,$cacheFolder=null,$db=''){
		$files=array();
		if (!self::$time)self::$time=\time();
		$text=\preg_replace_callback('|<img src="([^"]*\.([^"]*))"|s',function($phrase) use(&$files,$db,$cacheFolder){
		if (empty($files[$phrase[1]])){
			$counter=$files[$phrase[1]]=\count($files);
			if ($cacheFolder){
				if (!\file_exists($cacheFolder.'/'.\md5($phrase[1])))\file_put_contents($cacheFolder.'/'.\md5($phrase[1]),input::file_get_content($phrase[1]));
				self::AddAttachment($cacheFolder.'/'.\md5($phrase[1]),'file'.$counter.'.'.$phrase[2],'file'.$counter.self::$time,$db);
			}else{
				self::AddStringAttachment(input::file_get_content($phrase[1]),'file'.$counter.'.'.$phrase[2],'file'.$counter.self::$time,$db);
			}
		}else{
			$counter=$files[$phrase[1]];
		}
		return '<img src="cid:file'.($counter.self::$time).'"';
	},$text);
		return $text;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function set_reply_to($adress=array(),$db=''){
		return self::setReplyTo($adress,$db);
	}
	static function setReplyTo($adress=array(),$db=''){
		if (empty($adress))return false;
		if ($db=='')$db=core::$data['mail'];
		if (empty(self::$mails[$db]))self::connect($db);
		if (\is_string($adress))$adress=\explode(',',$adress);
		if (isset(core::$data['mail_modify_callback']))$adress=core::$data['mail_modify_callback']($adress);
		foreach ($adress as $value){
			self::$mails[$db]->AddReplyTo($value);
		}
	}
	static function sendOrFail($text,$adress=array(),$subject='System mail',$db=''){
		if (empty($adress))return false;
		if ($db=='')$db=core::$data['mail'];
		if (empty(self::$mails[$db]))self::connect($db);

		if (self::$testAdress){
			if (\is_array($adress))$adress=\implode(';',$adress);
			$subject=$subject."(".$adress.")";
			$adress=self::$testAdress;
		}
		$adress=\array_filter(self::toMailArray($adress),function($item){
			return $item!==null;
		});
		if (isset(core::$data['mail_modify_callback']))$adress= \call_user_func(core::$data['mail_modify_callback'],$adress);
		foreach ($adress as $value){
			self::$mails[$db]->AddAddress($value);
		}
		// todo: check charset in product server
		if (core::$charset!=core::UTF8){
			$subject=\iconv(core::$charset,'utf-8',$subject);
			$text=\iconv(core::$charset,'utf-8',$text);
		}
		self::$mails[$db]->MsgHTML($text);
		self::$mails[$db]->Subject=$subject;
		try{
			self::$mails[$db]->Send();
			if (core::$debug){
				debug::trace('Message successfull send: '.$subject.' - '.\implode(';',$adress),error::SUCCESS);
			}
		}catch (\Exception $exc){
			throw new \Exception($exc->getMessage());
		}finally{
			self::$mails[$db]->ClearAddresses();
			self::$mails[$db]->ClearReplyTos();
		}
	}
	static function send($text,$adress=array(),$subject='System mail',$db=''){
		try{
			return self::sendOrFail($text,$adress,$subject,$db);
		}catch(\Exception $exc){
			return $exc->getMessage();
		}
	}
	static function sendToQueue($text,$adress=array(),$subject='System mail',$db='',$order=100,$date_must='now'){
		if (!isset(core::$data['mail_send_to_queue']))throw new \Exception('not exists c\\core::$data[\'mail_send_to_queue\'] method');
		if (empty($adress))return false;
		if ($db=='')$db=core::$data['mail'];
		if (self::$testAdress){
			if (\is_array($adress))$adress=\implode(';',$adress);
			$subject=$subject."(".$adress.")";
			$adress=self::$testAdress;
		}
		$adress=self::toMailArray($adress);
		if (isset(core::$data['mail_modify_callback']))$adress=\call_user_func(core::$data['mail_modify_callback'],$adress);
		return \call_user_func(core::$data['mail_send_to_queue'],$text,$adress,$subject,$db,$order,$date_must);
	}
	private static function connect($db){
		if (empty(self::$mail_config)){
			if (\is_array(core::$data['mail']))self::$mail_config['my']=core::$data['mail'];
			if (\file_exists(__DIR__.'/global-config/mail.php'))include __DIR__.'/global-config/mail.php';
			if (\file_exists('config/mail.php'))include 'config/mail.php';
		}
		if (!$db)throw new \Exception('not set mail connection c\\core::$data["mail"]');
		if (!self::$mail_config[$db])throw new \Exception('mail connection "'.$db.'" not exists');

		$class='mail_'.self::$mail_config[$db]['type'];
		if (!\file_exists(__DIR__.'/factory/'.$class.'.php'))throw new \Exception('Connection mail type of '.$db.' dont recognized');
		$class='c\\factory\\'.$class;
		self::$mails[$db]=new $class();
		self::$mails[$db]->From=self::$mail_config[$db]['from'];
		self::$mails[$db]->Host=self::$mail_config[$db]['host'];
		self::$mails[$db]->UserName=self::$mail_config[$db]['username'];
		self::$mails[$db]->Password=self::$mail_config[$db]['password'];
		if (isset(self::$mail_config[$db]['port']))self::$mails[$db]->port=self::$mail_config[$db]['port'];
		if (isset(self::$mail_config[$db]['auth']))self::$mails[$db]->auth=self::$mail_config[$db]['auth'];
	}
}