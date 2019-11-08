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

	const EMOJI_THUMBS_UP="\xF0\x9F\x91\x8D";
	const EMOJI_FIREWORKS="\xF0\x9F\x8E\x86"; 
	
	const PARSE_MODE_MARKDOWN='Markdown';
	const PARSE_MODE_HTML='HTML';
	const PARSE_MODE_NONE=null;
	
	static $parse_mode;
	
	static function getMe(){
		self::check();
		return self::request('getMe');
	}
	static function sendLocation($chat_id,$latitude,$longitude,$reply_markup=null){
		self::check();
		$params=array('chat_id'=>$chat_id,'latitude'=>$latitude,'longitude'=>$longitude);
		if ($reply_markup)$params['reply_markup']=$reply_markup;
		return self::request('sendLocation',$params);
	}
	static function sendMessageWithInlineKey($chat_id,$text,$keyboardArray){
		$resp = array("inline_keyboard" => $keyboardArray);
		$reply = json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function editMessageWithInlineKey($chat_id,$message_id,$text,$keyboardArray){
		$resp = array("inline_keyboard" => $keyboardArray);
		$reply = json_encode($resp);
		return self::editMessage($chat_id,$message_id, $text, $reply);
	}
	static function sendMessageWithKey($chat_id,$text,$keyboardArray,$resizable=true,$one_time=true){
		$resp = array("keyboard" => $keyboardArray,"resize_keyboard" => $resizable,"one_time_keyboard" => $one_time);
		$reply = json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function sendMessageWithRemoveKey($chat_id,$text){
		$resp = array("remove_keyboard" => true);
		$reply = json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function sendMessageWithForceReply($chat_id,$text){
		$resp = array("force_reply" => true);
		$reply = json_encode($resp);
		return self::sendMessage($chat_id, $text, $reply);
	}
	static function sendMessage($chat_id,$text,$reply_markup=null){
		self::check();
		$params=array('chat_id'=>$chat_id,'text'=>input::iconv($text,true));
		if (self::$parse_mode)$params['parse_mode']=self::$parse_mode;
		if ($reply_markup)$params['reply_markup']=$reply_markup;
		return self::request('sendMessage',$params);
	}
	static function editMessage($chat_id,$message_id,$text,$reply_markup=null){
		self::check();
		$params=array('chat_id'=>$chat_id,'message_id'=>$message_id,'text'=>input::iconv($text,true));
		if (self::$parse_mode)$params['parse_mode']=self::$parse_mode;
		if ($reply_markup)$params['reply_markup']=$reply_markup;
		return self::request('editMessageText',$params);
	}
	static function getUpdates(){
		self::check();
		return input::iconv(self::request('getUpdates'));
	}
	static function sendChatAction($chat_id,$action){
		self::check();
		return input::iconv(self::request('sendChatAction',array('chat_id'=>$chat_id,'action'=>$action)));
	}
	static function sendPhoto($chat_id,$photoLink,$caption=null){
		self::check();
		$data=array('chat_id'=>$chat_id);
		if ($caption)$data['caption']=input::iconv($caption,true);
		$data['photo']=$photoLink;
		return input::iconv(self::request('sendPhoto',$data));
	}
	static function sendMessageOrFault($chat_id,$text,$reply_markup=null){
		$rs=self::sendMessage($chat_id,$text,$reply_markup);
		if (!$rs['ok'])throw new \Exception($rs['description'],$rs['error_code']);
		return $rs;
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
	/**
	 * get Webhook data
	 * @return array
	 */
	function getData() {
		return json_decode(input::iconv(file_get_contents("php://input")),true);
	}
	static function check(){
		if (!isset(core::$data['telegram']))throw new \Exception('need set c\\core::$data[\'telegram key\'] from BotFather');
	}
	private static function request($api,$post=null){
		if (is_callable(core::$data['telegram_request'])){
			$a=core::$data['telegram_request'];
			return $a($api,$post);
		}else{
			$url='https://api.telegram.org/bot'.core::$data['telegram'].'/'.$api;
			$proxy=array();
			if (core::$data['telegram_proxy']){
				$proxy=array(
					CURLOPT_PROXY=>core::$data['telegram_proxy']['host'],
					CURLOPT_PROXYUSERPWD=>core::$data['telegram_proxy']['auth'],
					CURLOPT_PROXYTYPE=>core::$data['telegram_proxy']['type']
				);
			}
			$rs=curl::getContent ($url,$post,null,$proxy);
			return json_decode($rs, true);
		}
	}
}