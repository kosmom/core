<?php
namespace c;

class max_api {
    const UPLOAD_TYPE_IMAGE = 'image';
    const UPLOAD_TYPE_VIDEO = 'video';
    const UPLOAD_TYPE_AUDIO = 'audio';
    const UPLOAD_TYPE_FILE = 'file';
    
    const ATTACHMENT_TYPE_IMAGE = 'image';
    const ATTACHMENT_TYPE_VIDEO = 'video';
    const ATTACHMENT_TYPE_AUDIO = 'audio';
    const ATTACHMENT_TYPE_FILE = 'file';
    const ATTACHMENT_TYPE_INLINE_KEYBOARD = 'inline_keyboard';
    
    const SEND_CHAT_ACTION_TYPING = 'typing_on';
    const SEND_CHAT_ACTION_UPLOAD_PHOTO = 'sending_photo';
    const SEND_CHAT_ACTION_UPLOAD_VIDEO = 'sending_video';
    const SEND_CHAT_ACTION_UPLOAD_AUDIO = 'sending_audio';
    const SEND_CHAT_ACTION_UPLOAD_DOCUMENT = 'sending_file';
    
    const UPDATE_MESSAGE_CREATED = 'message_created';
    const UPDATE_MESSAGE_CALLBACK = 'message_callback';
    const UPDATE_MESSAGE_EDITED = 'message_edited';
    const UPDATE_MESSAGE_DELETED = 'message_deleted';
    const UPDATE_CHAT_MEMBER = 'chat_member';
    const UPDATE_BOT_ADDED = 'bot_added';
    const UPDATE_BOT_REMOVED = 'bot_removed';
    
    const FORMAT_MARKDOWN = 'markdown';
    const FORMAT_HTML = 'html';
    
    const BUTTON_TYPE_LINK = 'link';
    const BUTTON_TYPE_CALLBACK = 'callback';
    const BUTTON_TYPE_REQUEST_USER = 'request_user';
    const BUTTON_TYPE_REQUEST_CHAT = 'request_chat';
    
    static $parse_mode;
    
    /**
	 * get Webhook data
	 * @return array
	 */
	static function getData(){
		return \json_decode(input::iconv(\file_get_contents("php://input")),true);
	}
    
    /**
     * @param string $method (GET, POST, PUT, DELETE)
     * @param string $api_method method API ('messages', 'me')
     * @param array $params GET params
     * @param array|null $data POST/PUT data
     * @return array
     * @throws \Exception
     */
        private static function request($method, $api_method, $params = array(), $data = null) {
            if (!isset(core::$data['max'])) {
                throw new \Exception('need set c\\core::$data[\'max\'] token');
            }
            $access_token=core::$data['max'];
            $url='https://platform-api2.max.ru/'.$api_method;

            if (!empty($params)) {
                $url.='?'.http_build_query($params);
            }
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
            $headers=array(
                'Authorization: '.$access_token,
                'Accept: application/json'
            );

            switch (strtoupper($method)){
                case 'POST':
                    curl_setopt($ch,CURLOPT_POST,true);
                    $headers[]='Content-Type: application/json';
                    if ($data!==null) {
                        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
                    }
                    break;

                case 'PATCH':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    $headers[] = 'Content-Type: application/json';
                    if ($data !== null) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                    break;
            
                case 'PUT':
                    curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'PUT');
                    $headers[]='Content-Type: application/json';
                    if ($data!==null) {
                        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
                    }
                    break;

                case 'DELETE':
                    curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'DELETE');
                    break;
            }

            curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);

            $response=curl_exec($ch);
            $http_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
            $error=curl_error($ch);

            curl_close($ch);

            if ($error){
                throw new \Exception('CURL Error: '.$error);
            }
            if ($http_code>=400) {
                $response_array=json_decode($response, true);
                $error_message=isset($response_array['error'])?$response_array['error']:'HTTP Error '.$http_code;
                throw new \Exception($error_message,$http_code);
            }

            if ($method==='DELETE' && empty($response)){
                return array('ok'=>true,'result'=>true);
            }

            return json_decode($response,true);
        }
    
	/**
	 * @param string $fileOrToken path or token
	 * @param string $type (image, video, audio, file)
	 * @throws \Exception
	 */
	static function upload($fileOrToken,$type=self::UPLOAD_TYPE_FILE) {
		if (!is_file($fileOrToken)){
			return array(
				'type'=>$type,
				'payload'=>array('token'=>$fileOrToken)
			);
		}
            
		$access_token=core::$data['max'];
		$url='https://platform-api2.max.ru/uploads?type='.urlencode($type);

		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array(
		    'Authorization: '.$access_token,
		    'Accept: application/json'
		));

		$response=curl_exec($ch);
		$http_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
		$error=curl_error($ch);
		curl_close($ch);

		if ($error){
		    throw new \Exception('CURL Error getting upload URL: '.$error);
		}

		if ($http_code>=400) {
		    $response_array=json_decode($response,true);
		    $error_message=isset($response_array['error'])?$response_array['error']:'HTTP Error '.$http_code;
		    throw new \Exception('Error getting upload URL: '.$error_message, $http_code);
		}

		$result=json_decode($response,true);

		if (!isset($result['url'])){
		    throw new \Exception('Invalid response: missing upload URL');
		}

		$uploadUrl=$result['url'];
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$uploadUrl);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array(
		    'Authorization: '.$access_token,
		    'Content-Type: multipart/form-data'
		));

		$finfo=finfo_open(FILEINFO_MIME_TYPE);
		$mime_type=finfo_file($finfo,$fileOrToken);
		finfo_close($finfo);

		$curl_file=new \CURLFile($fileOrToken,$mime_type,basename($fileOrToken));
		curl_setopt($ch,CURLOPT_POSTFIELDS,array('data'=>$curl_file));

		$response=curl_exec($ch);
		$http_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
		$error=curl_error($ch);
		curl_close($ch);

		if ($error){
		    throw new \Exception('CURL Error uploading file: '.$error);
		}

		if ($http_code>=400){
		    $response_array=json_decode($response,true);
		    $error_message=isset($response_array['error'])?$response_array['error']:'HTTP Error '.$http_code;
		    throw new \Exception('Error uploading file: '.$error_message, $http_code);
		}

		$uploadResult=json_decode($response,true);

		if (!$uploadResult){
		    throw new \Exception('Invalid response from upload: empty result');
		}

		$token = null;
		// photo format: ["photos" => ["hash" => ["token" => "..."]]]
		if ($type===self::UPLOAD_TYPE_IMAGE && isset($uploadResult['photos']) && is_array($uploadResult['photos'])){
		    $firstPhoto=reset($uploadResult['photos']);
		    if (isset($firstPhoto['token']))$token=$firstPhoto['token'];
		}
		// video format: ["token" => "..."]
		elseif (isset($uploadResult['token'])){
		    $token=$uploadResult['token'];
		}

		if (!$token){
		    throw new \Exception('Invalid response from upload: missing token. Response: '.json_encode($uploadResult));
		}

		return array(
		    'type'=>$type,
		    'payload'=>array('token'=>$token)
		);
    }
    
    static function patchBot($patch) {
        return self::request('PATCH', 'me', array(), $patch);
    }

    /**
     * set commands
     * @param array $commands [['name' => 'start', 'description' => 'Start'], ...]
     */
    static function setMyCommands($commands) {
        return self::patchBot(array('commands' => $commands));
    }

    static function deleteMyCommands() {
        return self::patchBot(array('commands' => array()));
    }
    
    /**
     * @param int $chat_id
     * @param string $text
     * @param array|null $attachments
     * @param bool $disable_link_preview
     * @param bool $notify
     * @param int|null $reply_to_message_id
     * @return array
     */
    static function sendMessage($chat_id,$text,$attachments=null,$disable_link_preview=false,$notify=true,$reply_to_message_id=null){
        $params=array('chat_id'=>$chat_id);
        $data=array('text'=>input::iconv($text,true));
        if (self::$parse_mode)$data['format']=self::$parse_mode;
        if ($disable_link_preview)$data['disable_link_preview']=true;
        if (!$notify)$data['notify']=false;
        if ($attachments)$data['attachments']=$attachments;
        if ($reply_to_message_id)$data['link']=array('message_id'=>$reply_to_message_id);
        return self::request('POST','messages',$params,$data);
    }
    
    /**
     * @param int $message_id
     * @param string $text
     * @param array|null $attachments (null - no changes, [] - clean all)
     * @param bool $disable_link_preview
     * @return array
     */
    static function editMessage($message_id,$text,$attachments=null,$disable_link_preview=false){
        $params=array('message_id'=>$message_id);
        $data=array('text'=>input::iconv($text, true));
        if (self::$parse_mode)$data['format']=self::$parse_mode;
        if ($disable_link_preview)$data['disable_link_preview']=true;
        if ($attachments!==null)$data['attachments']=$attachments;
        return self::request('PUT','messages',$params,$data);
    }
    
    /**
     * @param int $message_id
     * @param string $text
     * @param string|null $reply_markup
     * @return array
     */
    static function editMessageText($message_id,$text,$reply_markup=null){
        $attachments=null;
        if ($reply_markup){
            $keyboardData=json_decode($reply_markup,true);
            if (isset($keyboardData['inline_keyboard'])){
                $attachments=array(array(
                    'type'=>self::ATTACHMENT_TYPE_INLINE_KEYBOARD,
                    'payload'=>array('buttons'=>$keyboardData['inline_keyboard'])
                ));
            }
        }
        
        return self::editMessage($message_id,$text,$attachments);
    }
    
    static function sendMessageWithInlineKey($chat_id,$text,$keyboardArray){
        $attachment=array(
            'type'=>self::ATTACHMENT_TYPE_INLINE_KEYBOARD,
            'payload'=>array('buttons'=>$keyboardArray)
        );
        return self::sendMessage($chat_id,$text,array($attachment));
    }
    
    static function sendPhoto($chat_id,$image,$caption='',$notify=true){
        $attachment=self::upload($image,self::UPLOAD_TYPE_IMAGE);
        return self::sendMessage($chat_id,$caption,array($attachment),false,$notify);
    }
    static function sendDocument($chat_id,$file,$caption='',$notify=true){
        $attachment=self::upload($file,self::UPLOAD_TYPE_FILE);
        return self::sendMessage($chat_id,$caption,array($attachment),false,$notify);
    }
    static function sendVideo($chat_id,$video,$caption='',$notify=true){
        $attachment=self::upload($video,self::UPLOAD_TYPE_VIDEO);
        return self::sendMessage($chat_id,$caption,array($attachment),false,$notify);
    }
    static function sendAudio($chat_id,$audio,$caption='',$notify=true){
        $attachment = self::upload($audio,self::UPLOAD_TYPE_AUDIO);
        return self::sendMessage($chat_id,$caption,array($attachment),false,$notify);
    }

    static function sendMediaGroup($chat_id,$attachmentsData,$caption='',$notify=true){
        $attachments=array();
        foreach ($attachmentsData as $data){
            if (!isset($data['file']) || !isset($data['type'])){
                throw new \Exception('Each attachment must have "file" and "type" keys');
            }
            $attachments[]=self::upload($data['file'],$data['type']);
        }
        return self::sendMessage($chat_id,$caption,$attachments,false,$notify);
    }
    
    static function getMe(){
        return self::request('GET','me');
    }
    
    static function sendChatAction($chat_id,$action){
        return self::request('POST','chats/'.$chat_id.'/actions',array(),array('action'=>$action));
    }
    
    /**
     * Get long polling updates
     */
    static function getUpdates($options=array()){
        $params=array();
        
        if (isset($options['limit'])){
            $params['limit']=min(max($options['limit'], 1),1000);
        }
        
        if (isset($options['timeout'])){
            $params['timeout']=min(max($options['timeout'],0),90);
        }
        
        if (isset($options['marker'])){
            $params['marker']=$options['marker'];
        }
        
        if (isset($options['types']) && is_array($options['types'])){
            $params['types']=implode(',',$options['types']);
        }
        return self::request('GET','updates',$params);
    }
    
    static function deleteMessage($chat_id,$message_id){
        $params=array('message_id'=>$message_id);
        return self::request('DELETE','messages',$params);
    }
    
    static function getSubscriptions(){
        return self::request('GET','subscriptions');
    }
    
    /**
     * Установка webhook (подписка на обновления)
     * @param string $url URL для получения webhook
     * @param array $update_types Типы обновлений (например, ['message_created', 'bot_started'])
     * @param string|null $secret Секретный ключ для проверки заголовка X-Max-Bot-Api-Secret
     * @return array Ответ API
     */
    static function setWebhook($url, $update_types = null, $secret = null) {
        $data = array(
            'url' => $url
        );
        
        if ($update_types !== null && is_array($update_types)) {
            $data['update_types'] = $update_types;
        }
        
        if ($secret !== null) {
            $data['secret'] = $secret;
        }
        
        return self::request('POST', 'subscriptions', array(), $data);
    }
    
    /**
     * Удаление webhook (отписка от обновлений)
     * @param string $url URL webhook для удаления
     * @return array Ответ API с полями success и message
     */
    static function deleteWebhook($url) {
        $params = array(
            'url' => $url
        );
        return self::request('DELETE', 'subscriptions', $params);
    }
    
    /**
 * Добавление участников в групповой чат
 * @param int $chat_id ID чата
 * @param array|int $user_ids ID пользователя или массив ID пользователей для добавления
 * @return array Ответ API
 */
static function addChatMembers($chat_id, $user_ids) {
    // Если передан не массив, преобразуем в массив
    if (!is_array($user_ids)) {
        $user_ids = array($user_ids);
    }
    
    $data = array(
        'user_ids' => $user_ids
    );
    
    return self::request('POST', 'chats/' . $chat_id . '/members', array(), $data);
}

static function getChatMembers($chat_id, $params = array()) {
    return self::request('GET', 'chats/' . $chat_id . '/members', $params);
}
}