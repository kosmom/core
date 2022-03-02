<?php
namespace c;

/**
 * Curl class
 * @author Kosmom <Kosmom.ru>
 */
class curl{
	private static $tasks=array();
	private static $stack=array();
	private static $tasks_link=array();
	private static $pipe=0;
	private static $running=0;
	private static $master;
	private static $contents;

	static function getContent($URL,$post=\null,$cookieFile=\null,$options=array()){
		$c = \curl_init();
		\curl_setopt($c, \CURLOPT_RETURNTRANSFER, 1);
		\curl_setopt($c, \CURLOPT_URL, $URL);
		\curl_setopt($c, \CURLOPT_REFERER, $URL);
		if ($post){
			\curl_setopt($c, \CURLOPT_POST, 1);
			\curl_setopt($c, \CURLOPT_POSTFIELDS, $post);
		}
		if ($cookieFile){
			\curl_setopt($c, \CURLOPT_COOKIEJAR, $cookieFile);
			\curl_setopt($c, \CURLOPT_COOKIEFILE, $cookieFile);
		}
		\curl_setopt($c, \CURLOPT_SSL_VERIFYHOST, 0);
		\curl_setopt($c, \CURLOPT_SSL_VERIFYPEER, 0); 
		\curl_setopt($c, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
		\curl_setopt($c, \CURLOPT_FOLLOWLOCATION, 1);
		if ($options)\curl_setopt_array($c, $options);
		$rs = \curl_exec($c);
		if ($rs===\false)throw new \Exception(\curl_error($c));
		\curl_close($c);
		return $rs;
	}

	static function addTasks($url,$position=0,$callback=\null){
		$task=array('url'=>\trim($url),'position'=>$position);
		if (\is_callable($callback))$task['callback']=$callback;
		self::$tasks[]=$task;
		//sort array and form stack

		$fil=\array_filter(self::$tasks,function($item){
			return !isset($item['status']);
		});
		datawork::stable_uasort($fil,function($a,$b){
			if ((float)@$a['position'] == (float)@$b['position'])return 0;
			return ((float)$a['position'] > (float)$b['position']) ? -1 : 1;
		});
		self::$stack=\array_keys($fil);
	}

	static private function addCh($task_id){
		$ch = \curl_init();
		$options = array(
			\CURLOPT_RETURNTRANSFER => \true,
			//CURLOPT_HEADERFUNCTION=>'self::done',
		);
		//if (isset(self::$tasks[$task_id]['callback']))$options[CURLOPT_HEADERFUNCTION]=self::$tasks[$task_id]['callback'];
		$options[\CURLOPT_URL]=self::$tasks[$task_id]['url'];
		\curl_setopt_array($ch, $options);
		self::$tasks[$task_id]['handler']=(int)$ch;
		self::$tasks_link[(int)$ch]=$task_id;
		self::$tasks[$task_id]['status']=0; //0-send 1-done
		\curl_multi_add_handle(self::$master, $ch);
		unset($ch);
	//return $ch;
	}
	/**
	 * send requests
	 */
	static function push(){
		// make sure the rolling window isn't greater than the # of urls
		$rolling_window = \min(\sizeof(self::$stack),5);
		if (!self::$master or \gettype(self::$master)=='unknown type')self::$master = \curl_multi_init();
		// $curl_arr = array();
		// add additional curl options here

		// start the first batch of requests
		for ($i = self::$pipe; $i < $rolling_window; $i++) {
			self::addCh(\array_shift(self::$stack));
			self::$pipe++;
		}
		self::check(0);
		self::check();
	}
	static function check($pause=100){
	\usleep($pause);
		\curl_multi_exec(self::$master, self::$running);
		//if ($execrun != CURLM_OK)return false;
		while ($done = \curl_multi_info_read(self::$master)) {
			self::$pipe--;
			$task_id=self::$tasks_link[(int)$done['handle']];
			self::$tasks[$task_id]['status']=1;
			$content=\curl_multi_getcontent($done['handle']);
			if (isset(self::$tasks[$task_id]['callback']))self::$tasks[$task_id]['callback']($done['handle'],$content);

			//var_dump(curl_multi_getcontent($done['handle']));
			if (self::$stack){
				$rolling_window = \min(\sizeof(self::$stack),5);
				for ($i = self::$pipe; $i < $rolling_window; $i++) {
					self::addCh(\array_shift(self::$stack));
					self::$pipe++;
				}
				self::$running=1;
			}
			\curl_multi_remove_handle(self::$master, $done['handle']);
		}
	}
	static function waitAll(){
		self::push();
		do {
			$rs=self::check();
			if ($rs===\false)break;
		} while (self::$running);
		\curl_multi_close(self::$master);
		\var_dump(self::$contents);
	}

	static function ping($host,$port=-1,$protocol='tcp',$timeout=1){
		$fp = \fsockopen(($protocol!='tcp'?$protocol.'://':'').$host,$port,$errCode,$errStr,$timeout);
		$out=(bool)$fp;
		\fclose($fp);
		return $out;
	}
	static function pingICMP($host, $timeout = 1) {
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket  = \socket_create(\AF_INET, \SOCK_RAW, 1);
		\socket_set_option($socket, \SOL_SOCKET, \SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
		\socket_connect($socket, $host, \null);

		$ts = \microtime(\true);
		\socket_send($socket, $package, \strlen($package), 0);
		if (\socket_read($socket, 255))
		$result = \microtime(\true) - $ts;
		else $result = \false;
		\socket_close($socket);
		return $result;
	}
}