<?php
namespace c;
class mail_apache{
	var $From='';
	var $Reply='';
	var $Cc='';
	var $BCc='';
	var $Host='';
	var $UserName='';
	var $Password='';
	var $ErrorInfo='Error';
	private $body='';
	var $Subject;
	function MsgHTML($text){
		$this->body=$text;
	}
	private $addresslist=array();
	private $replylist=array();
	function AddReplyTo($address){
            $this->Reply[]=trim($address);
        }
    function AddAddress($address){
		$this->addresslist[]=trim($address);
	}
	function AddCC($address){
        $this->Cc[]=trim($address);
        }
	function AddBCC($address){
        $this->BCc[]=trim($address);
        }
	function ClearAddresses(){
		$this->addresslist=array();
	}
	function ClearReplyTos(){
		$this->addresslist=array();
	}

	function Send(){
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		if ($this->Cc)$headers .= 'Cc: '.implode(',',$this->Cc) . "\r\n";
		if ($this->BCc)$headers .= 'BCc: '.implode(',',$this->BCc) . "\r\n";
		$headers .= 'From: '.$this->From . "\r\n" .
		'Reply-To: ' .($this->Reply?implode(',',$this->Reply):$this->From). "\r\n" .
		'Return-Path: ' .$this->From. "\r\n" .
		'X-Mailer: PHP/' . phpversion();

		$success=0;
		foreach ($this->addresslist as $value){
			$result=mail($value, $this->Subject, $this->body, $headers);
			if ($result)$success++;
		}
		return $success;
	}
}