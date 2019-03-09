<?php
namespace c\factory;

class mail_smtp{
	var $From='';
	var $Host='';
	var $UserName='';
	var $Password='';
	var $port=25;
	var $timeout=30;
	var $ErrorInfo='unknown error';
	private $body='';
	var $Subject;
	var $auth=true;
	function MsgHTML($text){
		$this->body=$text;
	}
	private $filelist=array();
	private $separator='------------A4D921C2D10D7DB';
	private $addresslist=array();
	private $replylist=array();
	function AddAddress($address){
		$this->addresslist[]="<".$address.">";
	}
	function AddReplyTo($address){
		$this->replylist[]="<".$address.">";
	}
	function ClearAddresses(){
		$this->addresslist=array();
	}
	function ClearReplyTos(){
		$this->replylist=array();
	}

	function AddStringAttachment($text,$total_filename,$cid){
		$this->filelist[$total_filename]=array('name'=>$text,'cid'=>$cid);
	}
	function AddAttachment($source_filename,$total_filename,$cid){
		$this->filelist[$total_filename]=array('name'=>file_get_contents($source_filename),'cid'=>$cid);
	}
	function ClearAttachments(){
		$this->filelist=array();
	}

	function Send(){
		try {
			$smtp_conn = fsockopen($this->Host,$this->port,$errno,$errstr,$this->timeout);
			if (!$smtp_conn)throw new \Exception('Error connection to SMTP server: '.$errno.': '.$errstr);
			$this->get_data($smtp_conn);
			fputs($smtp_conn,"EHLO ".$this->UserName."\r\n");
			$code = substr(self::get_data($smtp_conn),0,3);
			//if($code != 250)throw new \Exception('Error EHLO');
			if ($this->auth){
				fputs($smtp_conn,"AUTH LOGIN\r\n");
				$code = substr($this->get_data($smtp_conn),0,3);
				if($code != 334)throw new \Exception('Error Auth');

				fputs($smtp_conn,base64_encode($this->UserName)."\r\n");
				$code = substr($this->get_data($smtp_conn),0,3);
				if($code != 334)throw new \Exception('Error Login');

				fputs($smtp_conn,base64_encode($this->Password)."\r\n");
				$code = substr($this->get_data($smtp_conn),0,3);
				if($code != 235)throw new \Exception('Error Password');
			}
			$header="Date: ".date("D, j M Y G:i:s")." +0300\r\n";
$header.="From: =?UTF-8?B?".base64_encode(\c\input::iconv($this->UserName,true))."?= <".$this->From.">\r\n";
$header.="X-Mailer: PHP Core mail\r\n";
if ($this->replylist)$header.="Reply-To: ".implode(',',$this->replylist)."\r\n";
$header.="X-Priority: 3 (Normal)\r\n";
//$header.="Message-ID: <172562218.".date("YmjHis")."@mail.ru>\r\n";
$header.="To: ".implode(',',$this->addresslist)."\r\n";
$header.="Subject: =?UTF-8?B?".base64_encode($this->Subject)."?=\r\n";
$header.="MIME-Version: 1.0\r\n";
if ($this->filelist){
	$header.='Content-Type: multipart/related; boundary="'.$this->separator.'"';
	$this->body='
--'.$this->separator.'
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: base64

'.chunk_split(base64_encode($this->body)).'
';
foreach ($this->filelist as $filename=>$content){
	$type=\c\filedata::mime_content_type($filename);
	$this->body.='
--'.$this->separator.'
Content-Type: '.$type.'; name="=?UTF-8?Q?'.str_replace("+","_",str_replace("%","=",urlencode($filename))).'?="
Content-transfer-encoding: base64'.($content['cid']?'
Content-ID: <'.$content['cid'].'>':'').'
Content-Disposition: '.($content['cid'] && substr($type,0,5)=='image'?'inline':'attachment').'; filename*="'.str_replace("+"," ",urlencode(\c\input::iconv($filename,true))).'"

'.chunk_split(base64_encode($content['name'])).'
';
}
$this->body.='--'.$this->separator.'--
';
}else{
	$header.="Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: base64\r\n";
   $this->body=chunk_split(base64_encode($this->body));
}

$size_msg=strlen($header."\r\n".$this->body);

fputs($smtp_conn,"MAIL FROM:<".$this->From."> SIZE=".$size_msg."\r\n");
$code = substr($this->get_data($smtp_conn),0,3);
if($code != 250)throw new \Exception('Error MAIL FROM');

			foreach($this->addresslist as $mail){
fputs($smtp_conn,"RCPT TO:".$mail."\r\n");
$code = substr($this->get_data($smtp_conn),0,3);
if($code != 250 && $code != 251)throw new \Exception('Error MAIL RCPT TO');
			}

fputs($smtp_conn,"DATA\r\n");
$code = substr($this->get_data($smtp_conn),0,3);
if($code != 354)throw new \Exception('Error DATA '.$code);

fputs($smtp_conn,$header."\r\n".$this->body."\r\n.\r\n");
$code = substr($this->get_data($smtp_conn),0,3);
if($code != 250)throw new \Exception('Error DATA2 '.$code);

fputs($smtp_conn,"QUIT\r\n");

		$success=1;
		} catch (\Exception $e) {
			fclose($smtp_conn);
			\c\error::add('SMTP Error:'.$e->getMessage());
			$success=0;
		}

		if (isset($smtp_conn))fclose($smtp_conn);
		return $success;
	}

	private function get_data($smtp_conn){
		$data="";
		while($str = fgets($smtp_conn,515)){
			$data .= $str;
			if(substr($str,3,1) == " ") break;
		}
		return $data;
	}
}