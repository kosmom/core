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
	const PARSE_MODE_NONE=null;
	
	const OPTION_PROTECT_CONTENT='protect_content';
	
	static $parse_mode;
	
	/**
	 * Universal method for any Telegram API request
	 * @param string $method - Telegram API method name
	 * @param int|string $chat_id - chat id
	 * @param array $data - additional request parameters
	 * @return array
	 */
	static function send($method, $chat_id, $data = array()){
		self::check();
		$data['chat_id']=$chat_id;
		if (isset($data['text']) && is_string($data['text'])){
			$data['text']=input::iconv($data['text'],true);
		}
		if (isset($data['caption']) && is_string($data['caption'])){
			$data['caption']=input::iconv($data['caption'],true);
		}
		if (self::$parse_mode && !isset($data['parse_mode'])){
			$data['parse_mode']=self::$parse_mode;
		}
		
		$options=array();
		$hasLocalFiles=false;
		
		foreach (array('photo','audio','document','video','voice','video_note') as $field){
			if (isset($data[$field]) && is_string($data[$field]) && is_file($data[$field])){
				$data[$field]=curl_file_create(realpath($data[$field]));
				$hasLocalFiles=true;
			}
		}
		
		if (isset($data['media']) && is_array($data['media'])){
			foreach ($data['media'] as $index=>&$mediaItem){
				if (isset($mediaItem['media']) && is_string($mediaItem['media']) && is_file($mediaItem['media'])){
					$attachName='file_'.$index;
					$originalFile=$mediaItem['media'];
					$mediaItem['media']='attach://'.$attachName;
					$data[$attachName]=curl_file_create(realpath($originalFile));
					$hasLocalFiles=true;
				}
			}
			unset($mediaItem);
		}
		if (isset($data['reply_markup']) && is_array($data['reply_markup'])){
			$data['reply_markup']=json_encode($data['reply_markup']);
		}
		if (isset($data['inline_keyboard'])){
			$data['reply_markup']=json_encode(array('inline_keyboard'=>$data['inline_keyboard']));
			unset($data['inline_keyboard']);
		}elseif (isset($data['keyboard'])){
			$keyboardOptions=array('keyboard'=>$data['keyboard']);
			if (isset($data['resize_keyboard']))$keyboardOptions['resize_keyboard']=$data['resize_keyboard'];
			if (isset($data['one_time_keyboard']))$keyboardOptions['one_time_keyboard']=$data['one_time_keyboard'];
			$data['reply_markup']=json_encode($keyboardOptions);
			unset($data['keyboard'],$data['resize_keyboard'],$data['one_time_keyboard']);
		}elseif (isset($data['remove_keyboard']) && $data['remove_keyboard']){
			$data['reply_markup']=json_encode(array('remove_keyboard'=>true));
			unset($data['remove_keyboard']);
		}elseif (isset($data['force_reply']) && $data['force_reply']){
			$data['reply_markup']=json_encode(array('force_reply'=>true));
			unset($data['force_reply']);
		}
		if ($hasLocalFiles){
			$options[CURLOPT_HTTPHEADER]=array("Content-Type"=>"multipart/form-data");
			$options[CURLOPT_SAFE_UPLOAD]=true;
		}
		return input::iconv(self::request($method,$data,$options));
	}
	
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
		return self::request($rs['result']['file_path'],null,array(),true);
	}
	static function sendLocation($chat_id,$latitude,$longitude,$reply_markup=null){
		$data=array('latitude'=>$latitude,'longitude'=>$longitude);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		return self::send('sendLocation',$chat_id,$data);
	}
	static function sendMessageWithInlineKey($chat_id,$text,$keyboardArray){
		return self::send('sendMessage',$chat_id,array(
			'text'=>$text,
			'inline_keyboard'=>$keyboardArray
		));
	}
	static function editMessageWithInlineKey($chat_id,$message_id,$text,$keyboardArray){
		return self::send('editMessageText',$chat_id,array(
			'message_id'=>$message_id,
			'text'=>$text,
			'inline_keyboard'=>$keyboardArray
		));
	}
	static function sendMessageWithKey($chat_id,$text,$keyboardArray,$resizable=true,$one_time=true){
		return self::send('sendMessage',$chat_id,array(
			'text'=>$text,
			'keyboard'=>$keyboardArray,
			'resize_keyboard'=>$resizable,
			'one_time_keyboard'=>$one_time
		));
	}
	static function sendMessageWithRemoveKey($chat_id,$text){
		return self::send('sendMessage',$chat_id,array(
			'text'=>$text,
			'remove_keyboard'=>true
		));
	}
	static function sendMessageWithForceReply($chat_id,$text){
		return self::send('sendMessage',$chat_id,array(
			'text'=>$text,
			'force_reply'=>true
		));
	}
	static function leaveChat($chat_id){
		return self::send('leaveChat',$chat_id);
	}
	static function sendMessage($chat_id,$text,$reply_markup=null,$protect_content=false,$reply_to_message_id=null){
		$data=array('text'=>$text);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if ($protect_content)$data['protect_content']=true;
		if ($reply_to_message_id)$data['reply_to_message_id']=$reply_to_message_id;
		
		return self::send('sendMessage',$chat_id,$data);
	}
	static function editMessage($chat_id,$message_id,$text,$reply_markup=null){
		$data=array('message_id'=>$message_id,'text'=>$text);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		return self::send('editMessageText',$chat_id,$data);
	}
	static function deleteMessage($chat_id,$message_id){
		return self::send('deleteMessage',$chat_id,array('message_id'=>$message_id));
	}
	static function editDocument($chat_id,$message_id,$link,$caption=null,$reply_markup=null){
		$data=array('message_id'=>$message_id,'document'=>$link);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if ($caption)$data['caption']=$caption;
		return self::send('editMessageMedia',$chat_id,$data);
	}
	static function sendDocument($chat_id,$link,$caption=null,$reply_markup=null,$protect_content=false){
		$data=array('document'=>$link);
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if ($protect_content)$data['protect_content']=true;
		if ($caption)$data['caption']=$caption;
		return self::send('sendDocument',$chat_id,$data);
	}
	static function getUpdates($options=array()){
		self::check();
		return input::iconv(self::request('getUpdates',$options));
	}
	static function sendChatAction($chat_id,$action){
		return self::send('sendChatAction',$chat_id,array('action'=>$action));
	}
	static function banChatMember($chat_id,$user_id,$until_date=null,$revoke_messages=null){
		$data=array('user_id'=>$user_id);
		if ($until_date)$data['until_date']=input::strtotime($until_date);
		if ($revoke_messages!==null)$data['revoke_messages']=$revoke_messages;
		return self::send('banChatMember',$chat_id,$data);
	}
	static function sendMediaGroup($chat_id,$mediaArray,$caption=null,$protect_content=false){
		$data=array('media'=>$mediaArray);
		if ($caption && isset($data['media'][0])){
			$data['media'][0]['caption']=$caption;
			if (self::$parse_mode)$data['media'][0]['parse_mode']=self::$parse_mode;
		}
		if ($protect_content)$data['protect_content']=true;
		return self::send('sendMediaGroup',$chat_id,$data);
	}
	static function pinChatMessage($chat_id,$message_id,$disable_notification=false){
		$data=array('message_id'=>$message_id);
		if ($disable_notification)$data['disable_notification']=true;
		return self::send('pinChatMessage',$chat_id,$data);
	}
	static function sendPhoto($chat_id,$photoLink,$caption=null,$reply_markup=null,$protect_content=false){
		$data=array('photo'=>$photoLink);
		if ($caption)$data['caption']=$caption;
		if ($reply_markup)$data['reply_markup']=$reply_markup;
		if ($protect_content)$data['protect_content']=true;
		return self::send('sendPhoto',$chat_id,$data);
	}
	static function unpinChatMessage($chat_id,$message_id=null){
		$data=$message_id===null?array():array('message_id'=>$message_id);
		return self::send('unpinChatMessage',$chat_id,$data);
	}
	static function unpinAllChatMessages($chat_id){
		return self::send('unpinAllChatMessages',$chat_id);
	}
	static function checkResponse($rs){
		if (!$rs['ok'])throw new \Exception($rs['description'],$rs['error_code']);
		return $rs;
	}
	static function sendMessageOrFault($chat_id,$text,$reply_markup=null){
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
		return self::send('getChat',$chat_id);
	}
	static function getChatAdministrators($chat_id){
		return self::send('getChatAdministrators',$chat_id);
	}
	/**
	 * get Webhook data
	 * @return array
	 */
	static function getData(){
		return json_decode(input::iconv(file_get_contents("php://input")),true);
	}
	static function check(){
		if (!isset(core::$data['telegram']))throw new \Exception('need set c\\core::$data[\'telegram\'] key from BotFather');
	}
	private static function request($api,$post=null,$options=array(),$is_file=false){
		if (is_callable(@core::$data['telegram_request'])){
			$a=core::$data['telegram_request'];
			return $a($api,$post,$is_file);
		}
		$url='https://api.telegram.org/'.($is_file?'file/':'').'bot'.core::$data['telegram'].'/'.$api;
		if (@core::$data['telegram_proxy']['host'])$options[CURLOPT_PROXY]=core::$data['telegram_proxy']['host'];
		if (@core::$data['telegram_proxy']['auth'])$options[CURLOPT_PROXYUSERPWD]=core::$data['telegram_proxy']['auth'];
		if (@core::$data['telegram_proxy']['type'])$options[CURLOPT_PROXYTYPE]=core::$data['telegram_proxy']['type'];
		$rs=curl::getContentSafely($url,$post,null,$options);
		return $is_file?$rs:json_decode($rs,true);
	}
	static function checkAuth($auth_data){
		$check_hash=$auth_data['hash'];
		unset($auth_data['hash']);
		$data_check_arr=array();
		foreach ($auth_data as $key=>$value){
			$data_check_arr[]=$key.'='.$value;
		}
		sort($data_check_arr);
		$data_check_string=implode("\n",$data_check_arr);
		$secret_key=hash('sha256',core::$data['telegram'],true);
		$hash=hash_hmac('sha256',$data_check_string,$secret_key);
		if (strcmp($hash,$check_hash)!==0)return false;
		return $auth_data['auth_date']>=time()-86400;
	}
}