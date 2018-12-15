<?php
namespace c;

/**
 * Responce class to output
 * @author Kosmom <Kosmom.ru>
 */
class responce{
	static $http_status_codes = array(100 => "Continue", 101 => "Switching Protocols", 102 => "Processing", 200 => "OK", 201 => "Created", 202 => "Accepted", 203 => "Non-Authoritative Information", 204 => "No Content", 205 => "Reset Content", 206 => "Partial Content", 207 => "Multi-Status", 300 => "Multiple Choices", 301 => "Moved Permanently", 302 => "Found", 303 => "See Other", 304 => "Not Modified", 305 => "Use Proxy", 306 => "(Unused)", 307 => "Temporary Redirect", 308 => "Permanent Redirect", 400 => "Bad Request", 401 => "Unauthorized", 402 => "Payment Required", 403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed", 406 => "Not Acceptable", 407 => "Proxy Authentication Required", 408 => "Request Timeout", 409 => "Conflict", 410 => "Gone", 411 => "Length Required", 412 => "Precondition Failed", 413 => "Request Entity Too Large", 414 => "Request-URI Too Long", 415 => "Unsupported Media Type", 416 => "Requested Range Not Satisfiable", 417 => "Expectation Failed", 418 => "I'm a teapot", 419 => "Authentication Timeout", 420 => "Enhance Your Calm", 422 => "Unprocessable Entity", 423 => "Locked", 424 => "Failed Dependency", 424 => "Method Failure", 425 => "Unordered Collection", 426 => "Upgrade Required", 428 => "Precondition Required", 429 => "Too Many Requests", 431 => "Request Header Fields Too Large", 444 => "No Response", 449 => "Retry With", 450 => "Blocked by Windows Parental Controls", 451 => "Unavailable For Legal Reasons", 494 => "Request Header Too Large", 495 => "Cert Error", 496 => "No Cert", 497 => "HTTP to HTTPS", 499 => "Client Closed Request", 500 => "Internal Server Error", 501 => "Not Implemented", 502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Timeout", 505 => "HTTP Version Not Supported", 506 => "Variant Also Negotiates", 507 => "Insufficient Storage", 508 => "Loop Detected", 509 => "Bandwidth Limit Exceeded", 510 => "Not Extended", 511 => "Network Authentication Required", 598 => "Network read timeout error", 599 => "Network connect timeout error");
    /**
	 * Add page header
	 * @param integer|string $code
	 * @param string|null $value
	 * @return super|false
	 */
	static function header($code,$value=null){
		if (headers_sent())return false;
                if ($value){
                    header($code.': '.$value);
                    return new super();
                }
                if (isset(self::$http_status_codes[$code])){
                    header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.self::$http_status_codes[$code]);
                }elseif (core::$debug){
                    debug::trace('Redirect header mistmatch in dictionary',error::WARNING,$code);
                }
		return new super();
	}
        
        /**
	 * Redirect
	 * @param string $url redirect URL
	 * @param integer $header redirect with header
	 */
	static function redirect($url=null,$header=''){
		if (core::$ajax)ajax::redirect($url);
		if (core::$debug && empty(core::$data['miss_debug_redirect'])){
			debug::trace('Redirect debug mode',error::INFO,array('url'=>$url,'header'=>$header));
			if (headers_sent())debug::trace('Redirect. Headers already sended',error::WARNING);
			die('<a href="'.iconv('utf-8','windows-1251',$url).'"><h1>'.translate::t('Core Debug mode: Click to continue').'</h1></a><style>h1{position:relative;top:50%;}a{text-align:center;}a,body,html{font-family:arial;width:100%;position:absolute;top:0px;height:100%;padding:0;border:0;margin:0;outline:none;cursor:pointer;}</style>'.mvc::drawJs(true));
		}
		if (!headers_sent()){
			if ($header!='')self::header($header);
			if ($url=='')$url=$_SERVER['REQUEST_URI'];
			header('Location: '.$url);
		}
		$url=$url==''?'location.href':"'".$url."'";
		die('<script>location.replace('.$url.');</script><noscript><meta http-equiv="Refresh" content="0;URL='.input::htmlspecialchars($url).'"></noscript>');
	}
        static function redirectToHttps(){
            if (request::protocol()=='http://')self::redirect ('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].($_SERVER['QUERY_STRING']===''?'':$_SERVER['QUERY_STRING']));
        }
        static function redirectToHttp(){
            if (request::protocol()=='https://')self::redirect ('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].($_SERVER['QUERY_STRING']===''?'':$_SERVER['QUERY_STRING']));
        }
        static function setTimeout($seconds=0){
            set_time_limit($seconds);
        }
}