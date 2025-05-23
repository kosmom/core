<?php
namespace c;

/**
 * Telegram api class
 * @author Kosmom <Kosmom.ru>
 */
class telegram{
	const SEND_CHAT_ACTION_TYPING='typing';
	const SEND_CHAT_ACTION_UPLOAD_PHOTO='upload_photo';
	const SEND_CHAT_ACTION_RECORD_VIDEO='record_video';
	const SEND_CHAT_ACTION_UPLOAD_VIDEO='upload_video';
	const SEND_CHAT_ACTION_RECORD_AUDIO='record_audio';
	const SEND_CHAT_ACTION_UPLOAD_AUDIO='upload_audio';
	const SEND_CHAT_ACTION_UPLOAD_DOCUMENT='upload_document';
	const SEND_CHAT_ACTION_FIND_LOCATION='find_location';

	const EMOJI_THUMBS_UP=emoji::THUMBS_UP;
	const EMOJI_FIREWORKS=emoji::FIREWORKS;
	const EMOJI_WARNING=emoji::WARNING;
	const EMOJI_BACK=emoji::BACK;
	const EMOJI_NO_ENTRY_SIGN=emoji::NO_ENTRY_SIGN;
	const EMOJI_WHITE_CHECK_MARK=emoji::WHITE_CHECK_MARK;
	const EMOJI_COP=emoji::COP;
	const EMOJI_MEN_DETECTIVE=emoji::MEN_DETECTIVE;
	
	const PARSE_MODE_MARKDOWN='Markdown';
	const PARSE_MODE_MARKDOWN2='MarkdownV2';
	const PARSE_MODE_HTML='HTML';
	const PARSE_MODE_NONE=\null;
	
	const OPTION_PROTECT_CONTENT='protect_content';
	
	static $parse_mode;
	
	static function getMe(){
		self::check();
		return self::request('getMe');
	}
	static function getFile($fileId){
		self::check();
		return self::request('getFile',array('file_id'=>$fileId));
	}
	static function getFileContent($fileId){
		$rs=self::getFile($fileId);
		if ($rs['ok']!=true)throw new Exception('file get error');
		return curl::getContent('https://api.telegram.org/file/bot'.core::$data['telegram'].'/'.$rs['result']['file_path']);
	}
	static function sendLocation($chat_id,$latitude,$longitude,$reply_markup=\null){
		self::check();
		$data=array('chat_id'=>$chat_id,'latitude'=>$latitude,'longitude'=>$longitude);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		return self::request('sendLocation',$data);
	}
	static function sendMessageWithInlineKey($chat_id,$text,$keyboardArray){
		$resp = array("inline_keyboard" => $keyboardArray);
		$reply = \json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function editMessageWithInlineKey($chat_id,$message_id,$text,$keyboardArray){
		$resp = array("inline_keyboard" => $keyboardArray);
		$reply = \json_encode($resp);
		return self::editMessage($chat_id,$message_id, $text, $reply);
	}
	static function sendMessageWithKey($chat_id,$text,$keyboardArray,$resizable=\true,$one_time=\true){
		$resp = array("keyboard" => $keyboardArray,"resize_keyboard" => $resizable,"one_time_keyboard" => $one_time);
		$reply = \json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function sendMessageWithRemoveKey($chat_id,$text){
		$resp = array("remove_keyboard" => \true);
		$reply = \json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function sendMessageWithForceReply($chat_id,$text){
		$resp = array("force_reply" => \true);
		$reply = \json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function leaveChat($chat_id){
		self::check();
		return self::request('leaveChat',array('chat_id'=>$chat_id));
	}
	static function sendMessage($chat_id,$text,$reply_markup=\null,$protect_content=\false,$reply_to_message_id=\null){
		self::check();
		$data=array('chat_id'=>$chat_id,'text'=>input::iconv($text,\true));
		if (self::$parse_mode)$data['parse_mode']=self::$parse_mode;
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if ($protect_content)$data['protect_content']=\true;
		if ($reply_to_message_id)$data['reply_to_message_id']=$reply_to_message_id;
		return self::request('sendMessage',$data);
	}
	static function editMessage($chat_id,$message_id,$text,$reply_markup=\null){
		self::check();
		$data=array('chat_id'=>$chat_id,'message_id'=>$message_id,'text'=>input::iconv($text,\true));
		if (self::$parse_mode)$data['parse_mode']=self::$parse_mode;
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		return self::request('editMessageText',$data);
	}
	static function deleteMessage($chat_id,$message_id){
		self::check();
		$data=array('chat_id'=>$chat_id,'message_id'=>$message_id);
		return self::request('deleteMessage',$data);
	}
	static function editDocument($chat_id,$message_id,$link,$caption=\null,$reply_markup=\null){
		self::check();
		$data=array('chat_id'=>$chat_id,'message_id'=>$message_id);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		$options=array();
		if (\is_file($link)){
			$data['document']=\curl_file_create(\realpath($link));
			$media['media']='attach://document';
			$options[\CURLOPT_HTTPHEADER]=array("Content-Type"=>"multipart/form-data");
			$options[\CURLOPT_SAFE_UPLOAD]=\true;
		}else{
			$media['media']=$link;
		}
		$media['type']='document';
		if ($caption)$media['caption']=$caption;
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		$data['media']=input::json_encode($media);
		return input::iconv(self::request('editMessageMedia',$data,$options));
	}
	static function getUpdates($options=array()){
		self::check();
		return input::iconv(self::request('getUpdates',$options));
	}
	static function sendChatAction($chat_id,$action){
		self::check();
		return input::iconv(self::request('sendChatAction',array('chat_id'=>$chat_id,'action'=>$action)));
	}
	static function banChatMember($chat_id,$user_id,$until_date=\null,$revoke_messages=\null){
		self::check();
		$data=array('chat_id'=>$chat_id,'user_id'=>$user_id);
		if ($until_date)$data['until_date']= input::strtotime($until_date);
		if ($revoke_messages!==null)$data['revoke_messages']=$revoke_messages;
		return input::iconv(self::request('banChatMember',$data));
	}
	static function sendPhoto($chat_id,$photoLink,$caption=\null,$reply_markup=\null,$protect_content=\false){
		self::check();
		$data=array('chat_id'=>$chat_id);
		$options=array();
		if ($caption)$data['caption']=input::iconv($caption,\true);
		if ($protect_content)$data['protect_content']=\true;
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if (\is_file($photoLink)){
			$data['photo']=\curl_file_create(\realpath($photoLink));
			$options[\CURLOPT_HTTPHEADER]=array("Content-Type"=>"multipart/form-data");
			$options[\CURLOPT_SAFE_UPLOAD]=\true;
		}else{
			$data['photo']=$photoLink;
		}
		return input::iconv(self::request('sendPhoto',$data,$options));
	}	
	static function sendDocument($chat_id,$link,$caption=\null,$reply_markup=\null,$protect_content=\false){
		self::check();
		$data=array('chat_id'=>$chat_id);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if ($protect_content)$data['protect_content']=\true;
		if (self::$parse_mode)$data['parse_mode']=self::$parse_mode;
		$options=array();
		if ($caption)$data['caption']=input::iconv($caption,\true);
		if (\is_file($link)){
			$data['document']=\curl_file_create(\realpath($link));
			$options[\CURLOPT_HTTPHEADER]=array("Content-Type"=>"multipart/form-data");
			$options[\CURLOPT_SAFE_UPLOAD]=\true;
		}else{
			$data['document']=$link;
		}
		return input::iconv(self::request('sendDocument',$data,$options));
	}
	static function checkResponse($rs){
		if (!$rs['ok'])throw new \Exception($rs['description'],$rs['error_code']);
		return $rs;
	}
	static function sendMessageOrFault($chat_id,$text,$reply_markup=\null){
		$rs=self::sendMessage($chat_id,$text,$reply_markup);
		return self::checkResponse($rs);
	}
	static function setWebhook($url,$certificateKey=''){
		self::check();
		$data['url']=$url;
		if ($certificateKey)$data['certificate']=$certificateKey;
		return self::request('setWebhook',$data);
	}
	static function getWebhookInfo(){
	    return self::request('getWebhookInfo');
	}
	static function getChat($chat_id){
	    return input::iconv(self::request('getChat',array('chat_id'=>$chat_id)));
	}
	static function getChatAdministrators($chat_id){
	    return input::iconv(self::request('getChatAdministrators',array('chat_id'=>$chat_id)));
	}
	/**
	 * get Webhook data
	 * @return array
	 */
	static function getData() {
		return \json_decode(input::iconv(\file_get_contents("php://input")),\true);
	}
	static function check(){
		if (!isset(core::$data['telegram']))throw new \Exception('need set c\\core::$data[\'telegram key\'] from BotFather');
	}
	private static function request($api,$post=\null,$options=array()){
		if (\is_callable(@core::$data['telegram_request'])){
			$a=core::$data['telegram_request'];
			return $a($api,$post);
		}
		$url='https://api.telegram.org/bot'.core::$data['telegram'].'/'.$api;
		if (@core::$data['telegram_proxy']){
			$options[\CURLOPT_PROXY]=core::$data['telegram_proxy']['host'];
			$options[\CURLOPT_PROXYUSERPWD]=core::$data['telegram_proxy']['auth'];
			$options[\CURLOPT_PROXYTYPE]=core::$data['telegram_proxy']['type'];
		}
		return \json_decode(curl::getContentSafely($url,$post,\null,$options), \true);
	}
	static function checkAuth($auth_data){
		$check_hash=$auth_data['hash'];
		unset($auth_data['hash']);
		$data_check_arr=[];
		foreach ($auth_data as $key=>$value){
			$data_check_arr[]=$key.'='.$value;
		}
		\sort($data_check_arr);
		$data_check_string=\implode("\n", $data_check_arr);
		$secret_key=\hash('sha256',core::$data['telegram'],true);
		$hash=\hash_hmac('sha256',$data_check_string,$secret_key);
		if (\strcmp($hash,$check_hash)!==0)return \false;
		if ((\time()-$auth_data['auth_date'])>86400)return \false;
		return \true;
	}
}