<?php
namespace c;

/**
 * MVC main class
 * @author Kosmom <Kosmom.ru>
 */
class mvc{
	static $basefolder='';
	static $story=array(array('link'=>'')); //for breadcrumbs
	static $links;
	static $file_links;
	static $canonical_links;
	static $url_links;
	static $links_string='';
	static $noHeader=false;
	static $noBody=false;
	static $page_counter=1;
	static $params_counter=0;
	private static $layoutCounter=0;
	static $url=array();
	static $page_url=array();
	static $partical_content_folder=false;
	static $indexfile=array();
	static $bodyAttributes=array();
	private static $isConfig;
	static $slashAtEnd='/';
	static $appFolder='app';
	/**
	 * counter - __DIR__
	 * @var array
	 */
	static $__DIR__counter=array();
	static $base__DIR__='';
	static $current__DIR__='';
	/**
	 * array of next dir from __DIR__
	 * @var array
	 */
	static $next_dir=array();
	/**
	 * make mvc component autoinclude js css in each module. may be slower
	 * @var boolean
	 */
	static $search_css_js=true;
	/**
	 * make mvc component autoinclude config.php in each module. may be slower
	 * @var boolean
	 */
	static $search_config=true;
	/**
	 * make mvc component autoinclude translate_lang.php in each module. may be slower
	 * @var boolean
	 */
	static $search_translate=true;
	/**
	 * make mvc component autoinclude middleware.php in each module. may be slower
	 * @var boolean
	 */
	static $search_middleware=true;
	/**
	 * find folder as regexp in controllerPage
	 * @var boolean
	 */
	static $foldersRegexp=false;
	static $titleSeparator='->';

	/* seo elements */
	static $title=array();
	static $title_inverse=true;
	static $description;
	static $keywords;
	static $noindex=false;
	static $meta=array('viewport'=>'width=device-width, initial-scale=1.0');

	/* decoration elements */
	private static $debugMessages=array();
	/**
	 * link to favicon.ico
	 * @var string
	 */
	static $favicon='';
	static $css_dict=array();
	static $js_dict=array(); // imported dictionary of scripts
	static $css=array();
	static $js_first=false; // insert javascripts at beginning of page
	static $required_js=array(); // total array required scripts
	
	private static $js=array();
	
	private static $jsHard=false;
	private static $isRoute=false;
	private static $lastDir;

	private static $routes=array();
	private static $routeMatch=false;
	private static $routeAs;
	private static $routeLazyCallback;
	private static $virtualDir;
	private static $virtualFolder;
	static $route_placeholder;
	/**
	 *
	 * @var string|boolean content for included pages (if it have)
	 */
	static $content=false;
	private static $folder;
	private static $nextFolder;
	//private static $exception_folder;

	static function isAjax(){
		self::addJs('core_ajax');
		return core::$ajax;
	}

	static function virtualRoute($folder,$baseDir=null,$startFromBaseDir=true){
		self::$virtualDir=$baseDir===null?self::$base__DIR__.DIRECTORY_SEPARATOR.self::$appFolder:$baseDir;
		self::$virtualFolder=$folder;
		if ($startFromBaseDir)self::controllerRoute(self::getRemaningParams());
	}

	static function check(){
		return (!(empty(self::$folder) && empty(self::$nextFolder)));
	}

	static function contentAction($data){
		if ($data===false || $data===1)return true;
		// draw output content with scripts
		echo $data;
		if (core::$debug){
			debug::stat();
			debug::groupEnd();
		}
		die(self::drawJs(true));
	}

	static function content(){
		self::$jsHard=true;
		if (self::$content===false){
			// find next folder after that
			// autosearch next folder when no controllerPage
			if (empty(self::$nextFolder))self::controllerPageInstant(rtrim (self::$folder,'\\/'));
			self::$folder=self::$nextFolder;
			self::$nextFolder=null;
			if (!is_dir(self::$folder)){
				self::$folder=null;
				return false;
			}
			self::$content='true';
			$dir=self::getRealDir(self::$folder);
			if (self::$search_css_js && !file_exists($dir.'index.php')){
				if (file_exists($dir.'index.css'))self::addCss($dir.'index.css');
				if (file_exists($dir.'index.js'))self::addJs($dir.'index.js');
			}
			self::$current__DIR__=realpath($dir);
		}
		if (!self::$folder)return false; // same folder drive to the end

		switch (self::$content){
		case 'true':
			if (self::$search_config && file_exists(self::$folder.'config.php')){
				self::$content='config.php';
				return self::$folder.self::$content;
			}
		case 'config.php':
			if (self::$search_middleware && file_exists(self::$folder.'middleware.php')){
				self::$content='middleware.php';
				return self::$folder.self::$content;
			}
		case 'middleware.php':
			if (core::$lang && self::$search_translate && file_exists(self::$folder.'translate_'.core::$lang.'.php')){
				self::$content='translate_'.core::$lang.'.php';
				return self::$folder.self::$content;
			}
		case 'translate_'.core::$lang.'.php':
			if ((core::$ajax || empty(self::$required_js['core_ajax'])) && file_exists(self::$folder.'ajax.php')){
				self::isAjax();
				if (core::$ajax){
					self::$content='ajax.php';
					return self::$folder.self::$content;
				}
			}
		case 'ajax.php':
			if ((core::$debug) && file_exists(self::$folder.'debug.php')){
                            self::$content='debug.php';
                            return self::$folder.self::$content;
			}
		case 'debug.php':
			self::$content='index.php';
			return self::$folder.self::$content;
		case 'index.php':
                        if ($_POST && file_exists(self::$folder.'post.php')){
                            self::$content='post.php';
                            return self::$folder.self::$content;
                        }
		case 'post.php':
			if (self::$search_css_js){
				$dir=self::getRealDir(self::$folder);
				if (file_exists($dir.'index.css'))self::addCss($dir.'index.css');
				if (file_exists($dir.'index.js'))self::addJs($dir.'index.js');
			}
			return self::$isRoute=self::$content=false;
		default:
				self::$content='index.php';
				return self::$folder.self::$content;
		}
	}

	/**
	 * Is found route from current page
	 * @return boolean
	 */
	static function isRoute(){
		return self::$isRoute;
	}

	/**
	 * Is found route from r functions
	 * @return boolean
	 */
	static function isRouteMatch(){
		return self::$routeMatch;
	}

	static function r($routeParams,$defaultParams=array()){
		$route=array('dir'=>self::$base__DIR__.DIRECTORY_SEPARATOR.self::$appFolder);
		if (isset(core::$data['r']) && is_array(core::$data['r']))$route=core::$data['r']+$route;
		if (is_array($defaultParams))$route=$defaultParams+$route;
		if (is_array($routeParams))$route=$routeParams+$route;

		// save possible routes in buffer
		if (isset($route['as']))self::$routes[$route['as']]=array('url'=>self::url($route['dir']).$route['url'],'prepared'=>0);

		// if route is found - new route ignored
		if (self::$routeMatch)return false;
		// compore with url against folder

		$match_vars=array();
		$order=0;
		if (isset($route['is_ajax']) && $route['is_ajax']!==self::isAjax())return false;
		if (isset($route['scheme']) && $route['scheme']!=$_SERVER['REQUEST_SCHEME'])return false;
		if (isset($route['method']) && !in_array(strtolower($_SERVER['REQUEST_METHOD']), $route['method']))return false;
		if (isset($route['domain']) && $route['domain']!=$_SERVER['HTTP_HOST'])return false;
		//middleware
		$url=self::getParamAsString();
		if (strpos($route['url'],'{')===false && $route['url']!==$url)return false;

		$regexp=preg_replace_callback('/\{(.*)\}/sU',function($matches) use(&$match_vars,&$order){
			$params=explode('|',$matches[1]);
			$attributes=array();
			//if (isset(mvc::$route_placeholder[$params[0]]))$attributes=$temp[$params[0]];
			$attributes['position']=$order++;
			if (isset($params[1]))$attributes['default']=$params[1];
			if (empty($attributes['regexp']))$attributes['regexp']='[^/]'.(empty($attributes['default'])?'+':'*');
			$match_vars[$params[0]]=$attributes;
			return '('.$attributes['regexp'].')';
		},$route['url']);
		$matches=array();
		if (!preg_match('|^'.$regexp.'$|',$url,$matches))return false;
		self::$routeMatch=true;
		$return=array();
		if (isset($route['as']))self::$routeAs=$route['as'];
		$order=0;
		foreach ($match_vars as $key=>&$item){
			$order++;
			$item['value']=$matches[$order];
			$return[$key]=self::routeFill($key,$matches[$order]?$matches[$order]:$item['default']);
		}
		if ($return)self::$routeMatch=$return;
		if ($route['page'] && $route['callback']){
			self::$routeLazyCallback=array($route['callback'],$return);
		}elseif ($route['callback']){
			call_user_func_array($route['callback'],$return);
		}elseif (!$route['page'] && !$route['callback']){
			return $return;
		}
		if ($route['page'])return self::controllerPageForce($route['dir'],$route['page']);
	}

	static function getParamFromRoute($routeAs=null){
		if ($routeAs!==null)return self::$routeMatch[$routeAs];
		return self::$routeMatch;
	}

	/**
	 * Fill variable to route placeholder callback
	 * @param string $routeAs router name
	 * @param string $value
	 * @return type
	 */
	static function routeFill($routeAs,$value){
		if (empty(self::$route_placeholder[$routeAs]))return $value;
		$props=self::$route_placeholder[$routeAs];
		if (isset($props['filter']))input::filter($value,$props['filter']);
		if ($value=='' && isset($props['default']))$value=$props['default'];
		if (isset($props['fill']))return $props['fill']($value);
	}

	static function getRoute(){
		return self::$routeAs;
	}

	/*
	private static function getUrlFromRoute($route_name,$params=array()){
		if (empty(self::$routes[$route_name]))throw new Exception('Route not exist');
		return '/'.self::$basefolder.self::$routes[$route_name];
	}
	 */
	
	/**
	 * @deprecated since version 3.4
	*/
	static function get_real_dir($__DIR__=null,$withApp=true){
		return self::getRealDir($__DIR__,$withApp);
	}
	
	/**
	 * Path to current folder via http
	 * @param string $__DIR__
	 * @param bool|string $withApp with app path|from __DIR__
	 * @return string
	 */
	static function getRealDir($__DIR__=null,$withApp=true){ 
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		if ($withApp===true)return str_replace('\\','/',substr($__DIR__,strlen(self::$base__DIR__)+1));
		if ($withApp===false)return str_replace('\\','/',substr($__DIR__,strlen(self::$base__DIR__)+5));
		return str_replace('\\','/',substr($__DIR__,strlen($withApp)-  strlen($__DIR__)+1));
	}

	/**
	 * Inverse title
	 * @deprecated use $title_inverse variable
	 * @deprecated since version 3.4
	 */
	static function title_inverse(){
		self::$title=array_reverse(self::$title);
	}

	/**
	 * @deprecated since version 3.4
	*/
	static function module_exists( $path='',$__DIR__=null){
		return self::moduleExists($path,$__DIR__);
	}
	/**
	 * Exists module or not with path
	 * @param string $path
	 * @param string|null $__DIR__
	 * @return boolean
	*/
	static function moduleExists( $path='',$__DIR__=null){
		if (empty($__DIR__))$__DIR__=self::$base__DIR__.'/'.self::$appFolder.'/';
		if (substr($path,0,1)=='/'){
			$path=substr($path,1);
			$__DIR__=self::$base__DIR__.'/'.self::$appFolder.'/';
		}
		return (file_exists($__DIR__.'/'.$path.'/index.php'));
	}

	/**
	 * Redirect relative current dir of mvc component
	 * @param string $path relative path
	 * @param string $__DIR__ current dir
	 * @param int $header
	 */
	static function redirect($path='',$__DIR__=null,$header=302){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		if (substr($path,0,1)=='/')error::redirect('/'.self::$basefolder.substr($path,1),$header);
		if (!empty($__DIR__))$path='/'.self::$basefolder.self::$url[$__DIR__].(self::$url[$__DIR__]?'/':'').$path;
		if (core::$charset!=core::UTF8)$path=iconv(core::$charset,'utf-8',$path);
		error::redirect($path,$header);
	}

	static function redirectToRoute($routeAs,$params=array(),$header=302){
		if (empty(self::$routes[$routeAs]))throw new \Exception('Route not exist');
		$path='/'.self::$basefolder.self::routePrepare($routeAs,$params);
		error::redirect($path,$header);
	}

	/**
	 * Prepare full links array from $_GET['link'] param
	 * @param string|null $__DIR__
	 */
	static function init($__DIR__=null){
		chdir($__DIR__);
		if (core::$debug){
			debug::consoleLog(date('H:i:s').' Core MVC debug started',error::SUCCESS);
			debug::timer();
		}
		header('Content-Type: text/html; charset='.core::$charset);
                mb_internal_encoding(core::$charset);
		if (request::isAjax())ajax::init();
		$dir=trim(dirname($_SERVER['SCRIPT_NAME']),'\\/');
		if ($dir)self::$basefolder=$dir.'/';
		if ($__DIR__!==null){
			self::$base__DIR__=$__DIR__;
			self::$next_dir=array($__DIR__.DIRECTORY_SEPARATOR.self::$appFolder);
		}
		if (request::isCmd()){
			global $argv;
			if (@strpos($argv[1], '?')!==false){ //parse get params
				parse_str(substr($argv[1],strpos($argv[1], '?')+1), $_GET);
				$_SERVER['REDIRECT_URL']=substr($argv[1],0,strpos($argv[1], '?'));
			}else{
				@$_SERVER['REDIRECT_URL']=$argv[1];
			}
			self::$links_string=ltrim($_SERVER['REDIRECT_URL'],'\\/');
		}else{
			self::$links_string=substr(ltrim(@$_SERVER['REDIRECT_URL'],'\\/'), strlen(self::$basefolder));
		}
		if (core::$charset!=core::UTF8)self::$links_string=iconv('utf-8',core::$charset,self::$links_string);
		self::$links=explode('/',self::$links_string);
		self::getConfig();
		self::$nextFolder=$__DIR__.DIRECTORY_SEPARATOR.self::$appFolder.DIRECTORY_SEPARATOR;
	}
	
	private static function getConfig(){
		if (self::$isConfig)return ;
		if (file_exists(__DIR__.'/global-config/mvc.php'))include __DIR__.'/global-config/mvc.php';
		if (file_exists('config/mvc.php'))include 'config/mvc.php';
		self::$isConfig=true;
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_title($title){
		return self::addTitle($title);
	}
	/**
	 * Add title peace into title array
	 * @param string $title
	 * @return super
	 */
	static function addTitle($title){
		self::$title[]=$title;
		self::$story[self::$page_counter]['title']=$title;
		return new super();
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function draw_js($inlineOnly=false){
		return self::drawJs($inlineOnly);
	}
	static function drawJs($inlineOnly=false){
		$out='';
		if (core::$debug && core::$ajax)debug::groupEnd();
		$scriptMode=false;
		foreach (self::$js as $script){
			if ($script[2]==3){
				if (!$scriptMode){
					$out.='<script>';
					$scriptMode=true;
				}
				$out.=$script[0].';';
			}else{
				if ($inlineOnly)continue;
				if ($scriptMode){
					$out.='</script>';
					$scriptMode=false;
				}
				$out.=($script[3]?'<!--[if '.$script[3].']>':'').'<script src="'.$script[0].'"></script>'.($script[3]?'<![endif]-->':'');
			}
		}
		if ($scriptMode)$out.='</script>';
		return $out;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function draw_css(){
		return self::drawCss();
	}
	static function drawCss(){
		if (!self::$css) return false;
		$out='';
		foreach (self::$css as $key=>$item){
			$out.='<link href="'.$item[0].(!isset(self::$css_dict[$key]) && core::$version?'?v='.core::$version:'').'" rel=stylesheet>';
		}
		self::$css=array();
		return $out;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function this_page_url($__DIR__=null){
		return self::getUrl($__DIR__);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function this_page_link($__DIR__=null){
		return self::getUrl($__DIR__);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function url($__DIR__=null){
		return self::getUrl($__DIR__);
	}

	/**
	 * Get link to current page of __DIR__
	 *
	 * @param string $__DIR__
	 * @return string URL
	 * @deprecated since version 3.4
	 */
	static function get_url($__DIR__=null){
		return self::getUrl($__DIR__);
	}
	/**
	 * Get link to current page of __DIR__
	 *
	 * @param string $__DIR__
	 * @return string URL
	 */
	static function getUrl($__DIR__=null){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		if (empty($__DIR__))return self::$basefolder.'/';
		return @self::$url[$__DIR__]?self::$url[$__DIR__].'/':'';
	}

	static function getUrlFromRoute($routeAs,$params=array()){
		if (empty(self::$routes[$routeAs]))throw new \Exception('Route not exist');
		return '/'.self::$basefolder.self::routePrepare($routeAs,$params);
	}

	private static function routePrepare($routeName,$params=null){
		if (empty(self::$routes[$routeName]))throw new \Exception('Route not exist');
		$route=&self::$routes[$routeName];
		switch ($route['prepared']){
			case false:
		$order=0;
		$route['url']=preg_replace_callback('/\{(.*)\}/sU',function($matches) use(&$route,&$order){
			$params=explode('|',$matches[1]);
			if (isset($params[1])){
				$route['vars'][$params[0]]=array('position'=>$order++,'default'=>$params[1]);
			}else{
				$route['vars'][$params[0]]=array('position'=>$order++);
			}
			return '{'.$params[0].'}';
		},$route['url']);
			$route['prepared']=true;
			case true:
			// preparing row for fast cache
			$out=$route['url'];
			foreach ($route['vars'] as $param=>$item){
				if (!isset($params[$param]) && !isset($item['default']))  throw new \Exception('Required params '.$param.' mistmach');
				$out=str_replace('{'.$param.'}',isset($params[$param])?$params[$param]:$item['default'],$out);
			}
			return $out;
		}
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function get_absolute_url($__DIR__=null){
		return self::getAbsoluteUrl($__DIR__);
	}
	static function getAbsoluteUrl($__DIR__=null){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		return request::protocol().$_SERVER['HTTP_HOST'].'/'.self::$basefolder.self::getUrl($__DIR__);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function add_css($css){
		return self::addCss($css);
	}
	/**
	 * Add css style from mvc dictionary or create css link to file
	 * @param string $css
         * @return super
	 */
	static function addCss($css){
		self::getConfig();
		if (empty(self::$css_dict[$css])){
			if (substr($css,0,4)!='http' && substr($css,0,2)!='//' && !file_exists($css)){
				if (core::$debug)debug::trace('Css '.$css.' not exists',error::ERROR);
				return new super();
			}
			self::$css[$css]=array($css,self::$jsHard);
			return new super(); // not in dictonary
		}
		if (isset(self::$css[$css]))return true;
		if (is_array(self::$css_dict[$css])){
			self::$css[$css]=array(self::$css_dict[$css]['url'].(isset(self::$css_dict[$css]['version'])?'?v='.self::$css_dict[$css]['version']:''),self::$jsHard);
		}else{
			self::$css[$css]=array(self::$css_dict[$css],self::$jsHard);
		}
		return new super();
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function add_js($js,$isComponent=false){
		return self::addJs($js,$isComponent);
	}
	/**
	 * Add script from mvc dictionary or create script link to file
	 * @param string $js
	 * @return super
	 */
	static function addJs($js,$isComponent=false){
		if (core::$ajax)return new super();
		self::getConfig();
		if (empty(self::$js_dict[$js])){
			// not in dictionary
			if (isset(self::$required_js[$js]))return new super(); //already in use
			if (substr($js,0,4)!='http' && substr($js,0,2)!='//' && !file_exists($js)){
				if (core::$debug && !isset(self::$debugMessages[$js])){
					self::$debugMessages[$js]=true;
					debug::trace('Js '.$js.' not exists',error::ERROR);
				}
				return new super();
			}
			self::$required_js[$js]=self::$jsHard;
			self::$js[]=array($js.(core::$version?'?v='.core::$version:''),self::$jsHard,1,false);
		}elseif (is_array(self::$js_dict[$js])){
			$jslink=self::$js_dict[$js]['url'];
			if (isset(self::$required_js[$jslink]))return new super(); //already in use
			if (isset(self::$js_dict[$js]['requires'])){
				if (!is_array(self::$js_dict[$js]['requires']))self::$js_dict[$js]['requires']=array(self::$js_dict[$js]['requires']);
				foreach (self::$js_dict[$js]['requires'] as $item){
					$isComponent?self::addComponent($item):self::addJs($item);;
				}
			}
			self::$js[]=array($jslink.(isset(self::$js_dict[$js]['version'])?'?v='.self::$js_dict[$js]['version']:''),self::$jsHard,2,isset(self::$js_dict[$js]['if'])?self::$js_dict[$js]['if']:false);
			self::$required_js[$jslink]=self::$jsHard;
		}else{
			$jslink=self::$js_dict[$js];
			if (isset(self::$required_js[$jslink]))return new super(); //already in use
			self::$js[]=array($jslink,self::$jsHard,2,false);
			self::$required_js[$jslink]=self::$jsHard;
		}
		return new super();
	}

	/**
	 * Add js scripts and css styles on page from mvc dictionary
	 * @param string $component
	 * @return boolean
	 * @deprecated since version 3.4
	 */
	static function add_component($component){
		return self::addComponent($component);
	}
	/**
	 * Add js scripts and css styles on page from mvc dictionary
	 * @param string $component
	 * @return boolean
	 */
	static function addComponent($component){
		if (core::$ajax)return false;
		if (empty(self::$js_dict) && empty(self::$css_dict))include __DIR__.'/global-config/mvc.php';
		if (isset(self::$js_dict[$component]))self::addJs($component,true);
		if (isset(self::$css_dict[$component]))self::addCss($component);
		return new super();
	}
	/**
	 * Add script context to scripts after includion all scripts
	 * @param string $js
	 * @deprecated since version 3.4
	 */
	static function add_script($js){
		return self::addScript($js);
	}
	/**
	 * Add script context to scripts after includion all scripts
	 * @param string $js
	 */
	static function addScript($js){
		self::$js[]=array($js,self::$jsHard,3);
		return new super();
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function add_js_var($var,$value){
		return self::addJsVar($var,$value);
	}
	/**
	 * Add Javascript var to javascript (as object)
	 * @param string|array $var name of var, or array key-value
	 * @param any $value
	 * @return super
	 */
	static function addJsVar($var,$value=null){
		if (is_array($var)){
			foreach ($var as $key=>$item){
				self::addJsVar($key,$item);
			}
			return new super();
		}
		self::addScript('var '.$var.'='.input::jsVar($value));
		if (core::$debug){
			debug::consoleLog('var '. $var);
			self::addScript('console.log('.$var.')');
		}
		return new super();
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function add_js_var_as_array($var,$value){
		return self::addJsVarAsArray($var,$value);
	}
	/**
	 * Add Javascript var to javascript as array (not object)
	 * @param string|array $var name of var, or array key-value
	 * @param any $value
	 * @return super
	 */
	static function addJsVarAsArray($var,$value=null){
		if (is_array($var)){
			foreach ($var as $key=>$item){
				self::addJsVarAsArray($key,$item);
			}
			return new super();
		}
		self::addScript('var '.$var.'='.input::jsVarArray($value));
		if (core::$debug){
			debug::consoleLog('var '. $var);
			self::addScript('console.log('.$var.')');
		}
		return new super();
	}

	static function drawTitle(){
		return input::htmlspecialchars(implode(' '.self::$titleSeparator.' ',self::$title_inverse?array_reverse(self::$title):self::$title));
	}

	static function header(){
		if (self::$routeLazyCallback){
			call_user_func_array(self::$routeLazyCallback[0],self::routeFill(self::$routeLazyCallback[0],self::$routeLazyCallback[1]));
			self::$routeLazyCallback=null;
		}
		self::$page_counter=0;
		self::$params_counter=0;
		if (request::isCmd())return false;
		if (!self::$noHeader){
			if (self::$partical_content_folder!==false)return false;
			if (core::$ajax)ajax::render();
			if (self::$description)self::$meta['description']=self::$description;
			if (self::$keywords)self::$meta['keywords']=self::$keywords;
			if (self::$noindex)self::$meta['noindex']=self::$noindex===true?'noindex,follow':self::$noindex;
//print_r(self::$url_links);
//print_r(self::$url);
//print_r(self::$story);
	?><!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="<?=core::$charset?>">
		<link rel="canonical" href='//<?=$_SERVER['HTTP_HOST']?>/<?=self::$basefolder.htmlspecialchars(rtrim(@implode('/',self::$url_links).'/'.@self::$canonical_links,'/')).self::$slashAtEnd?>'>
	<title><?=self::drawTitle();?></title><?
foreach (self::$meta as $name=>$content){?>
<meta name="<?=$name?>" content="<?=input::htmlspecialchars($content)?>">
<?php }?>	
		<base href="//<?=$_SERVER['HTTP_HOST']?>/<?=self::$basefolder?>" />
		<?=self::drawCss().(self::$js_first?self::drawJs():'');if (self::$favicon){?><link href="<?=self::$favicon?><?=core::$version?'?v='.core::$version:''?>" rel="icon" type="image/x-icon" /><?php }?>
	</head>
		<?php }
		if (!self::$noBody){?><body<?if (self::$bodyAttributes){
			foreach (self::$bodyAttributes as $attribute=>$v){
				echo ' '.input::htmlspecialchars($attribute).'="'.input::htmlspecialchars($v).'"';
			}
		}?>><?php }
	}
	static function footer(){
		if (request::isCmd())return false;
		if (core::$debug)debug::stat();
		echo self::drawCss();
		if (self::$partical_content_folder!==false)return true;
		// until addJs addCss with __DIR__ not working
		echo self::drawJs();
		if (!self::$noBody){?></body><?php }
		if (!self::$noHeader){?></html><?php }
	}

	/**
	 * Get addition route vars from current directory
	 * @deprecated Use getParamsAsArray method
	 * @return array
	 * @deprecated since version 3.4
	 */
	static function get_vars(){
		return self::getVars();
	}
	/**
	 * Get addition route vars from current directory
	 * @deprecated Use getParamsAsArray method
	 * @return array
	 */
	static function getVars(){
		return array_slice(self::$links,self::$page_counter-self::$params_counter-1);
	}
	static function getParamsAsArray(){
		return self::getVars();
	}

	/**
	 * Get addition route strinng from current directory
	 * @deprecated Use getParamsAsString method
	 * @return string
	 * @deprecated since version 3.4
	 */
	static function get_params(){
		return self::getParams();
	}
	/**
	 * Get addition route strinng from current directory
	 * @deprecated Use getParamsAsString method
	 * @return string
	 */
	static function getParams(){
		return self::getParamAsString();
	}
	static function getParamAsString(){
		return implode('/', self::getVars());
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function get_file_prop($__FILE__){
		return self::getFileProp($__FILE__);
	}
	/**
	 * Get file prop in comments //prop:value in beginning of file as array
	 * @param string $__FILE__ source filename
	 * @return fileprop
	 */
	static function getFileProp($__FILE__){
		$data = file($__FILE__); // may be long in largest files?
		$module=array();
		foreach ($data as $key=>$item) {
			$item=trim($item);
			if ($item=='<?php')continue;
			if (substr($item,0,2)!='//')break;
			$delimeter=strpos($item,':');
			if ($delimeter<0)continue;
			$module[trim(mb_substr($item,2,$delimeter-2,core::$charset))]=trim(mb_substr($item,$delimeter+1,999,core::$charset));
		}
		if (self::$search_config && substr($__FILE__,-10)!='config.php' && file_exists($f=dirname($__FILE__).'/config.php')){
			$data = file($f);  // may be long in largest files?
			foreach ($data as $key=>$item) {
				$item=trim($item);
				if ($item=='<?php')continue;
				if (substr($item,0,2)!='//')break;
				$delimeter=strpos($item,':');
				if ($delimeter<0)continue;
				$module[trim(mb_substr($item,2,$delimeter-2,core::$charset))]=trim(mb_substr($item,$delimeter+1,999,core::$charset));
			}
		}
		return new fileprop($module,$__FILE__);
	}


	/**
	 * @deprecated since version 3.4
	 */
	static function get_menu($__DIR__=null,$addition_menu=null,$level=1){
	   return self::getMenu($__DIR__,$addition_menu,$level);
	}
	/**
	 * Show menu array from subpages. Get data from comments in begin of file
	 * @param string $__DIR__ current __DIR__
	 * @param array|null $additionMenu array of addition menu
	 * @param number|null $level Number menu levels as submenu
	 * @return array|fileprop[]|boolean Struct of subpages or false if no array
	 */
	static function getMenu($__DIR__=null,$additionMenu=null,$level=1){
	   if ($__DIR__===null)$__DIR__=self::$current__DIR__;
	   return self::getMenuPart($__DIR__,$__DIR__,'',$additionMenu,$level);
	}

	private static function getMenuPart($__DIR__,$base__DIR__,$href,$additionMenu,$level){
		$modules=array();
		if (!($handle = opendir($__DIR__)))return false;
		while (false !== ($file = readdir($handle))) {
			if ($file=='.' || $file=='..' || substr($file,0,1)=='_')continue;
			if (is_file($__DIR__.DIRECTORY_SEPARATOR.$file))continue;
			$nextfile=$__DIR__.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.'index.php';
			if (!file_exists($nextfile))continue;
			$module=self::getFileProp($nextfile);
                        
			if (empty($module->name)) continue;
			if (!isset($module->link))$module->href=$module->link=@(self::$url[$base__DIR__]?self::$url[$base__DIR__].'/':'').$href.$file.self::$slashAtEnd;
			$module->__DIR__=$__DIR__.DIRECTORY_SEPARATOR.$file;
			$modules[$file]=$module;
		}
		if ($additionMenu){
			if (is_array($additionMenu)){
				foreach ($additionMenu as $key=>$item){
					if (is_string($item)){
						$modules[$key]=new fileprop(array('name'=>$item));
					}elseif (is_array($item)){
						$modules[$key]=new fileprop($item);
					}elseif ($item instanceof c\arrayaccess){
						$modules[$key]=clone($item);
					}
				}
			}
		}
		$nextpage=@self::$url_links[self::$__DIR__counter[$__DIR__]];
		if ($nextpage && isset($modules[$nextpage]))$modules[$nextpage]->active=true;
		uasort($modules,array('c\\datawork','positionCompare'));
		if ($level>1)foreach ($modules as &$module){
			$submenu=self::getMenuPart($module->__DIR__,self::$base__DIR__,$module->href,null,$level-1);
			if ($submenu)$module->submenu=$submenu;
		}
		return $modules;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function controller_page_instant($__DIR__=null){
		return self::controllerPageInstant($__DIR__);
	}
	/**
	 * Do controller page if page is exists or return true
	 * @param string $__DIR__
	 * @return boolean
	 */
	static function controllerPageInstant($__DIR__=null){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		$next_part=@self::$links[self::$page_counter-1];
		if (!$next_part)return false;
		if (substr($next_part,0,1)=='_')return false;
		if (!is_dir($__DIR__.DIRECTORY_SEPARATOR.$next_part))return false;
		self::controllerPage($__DIR__);
		return true;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function controller_page($__DIR__=null,$default='_default',$exception=null,$routes=null){
		return self::controllerPage($__DIR__,$default,$exception,$routes);
	}
	static function nextPage($default=null,$exception=null,$__DIR__=null){
		if ($default===null){
			return self::controllerPageInstant($__DIR__);
		}else{
			return self::controllerPage($__DIR__,$default,$exception);
		}
	}
	static function nextPageForce($page,$__DIR__){
		return self::controllerPageForce($__DIR__,$page);
	}
	static function addStory($link,$title){
		self::$story[]=array('link'=>$link,'title'=>$title);
		self::$canonical_links=$link;
	}
	/**
	 * Use page as wrapper. Find default, exception subpage from current
	 * @param string $__DIR__ __DIR__
	 * @param string $default default page if subpage route not send
	 * @param string|array $exception exception page if subpage not exist
	 * @param array $routes routes array of dynamic page
	 * @return string var of next dynamic page
	 */
	static function controllerPage($__DIR__=null,$default='_default',$exception=null,$routes=null){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		if (self::$lastDir==$__DIR__){
			self::$page_counter--;
			unset(self::$url_links[self::$page_counter]);
		}
		$result=null;
		if ($exception===null)$exception=$default;
		if (is_array($exception) && $routes===null){
			$routes=$exception;
			$exception=$default;
		}
		$nextPart=@self::$links[self::$page_counter-1].(self::$virtualDir==$__DIR__?'/'.self::$virtualFolder:'');
		self::$url[$__DIR__]=self::$url_links?implode('/',self::$url_links):'';
		self::$indexfile[$__DIR__]=self::$file_links?implode('/',self::$file_links):'';
		self::$__DIR__counter[$__DIR__]=self::$page_counter;

		// _ - private pages. closest access for direct routings
		$temp=self::$file_links[self::$page_counter]=empty($nextPart)?$default:$nextPart;
		if (substr($temp,0,1)!='_')self::$url_links[self::$page_counter]=$temp;
	//		if (!$next_part)unset(self::$url_links[self::$page_counter]);
		if (substr($nextPart,0,1)=='_' || !is_dir($__DIR__.DIRECTORY_SEPARATOR.$nextPart)){

			if (self::$foldersRegexp){
				//try find regexp folder
				foreach (glob($__DIR__.'/`*',GLOB_ONLYDIR) as $folder){
					// transform to regexp
					$folder=basename($folder);
					$regexp=strtr($folder,array('-'=>'\\-','..'=>'-'));
					$matches=array();
					if (!preg_match($regexp,$temp,$matches))continue;
					// route match
					$return=$result=$matches;
					self::$file_links[self::$page_counter]=$folder;
					self::$url_links[self::$page_counter]=$temp;
					break;
				}
			}
			self::$params_counter++;
			if (!$result){
				if (core::$debug)debug::trace('ControllerPage '.$nextPart.' link not found. Exception is worked',error::WARNING);
				// if route params exists - find match of route validators
				if (!empty($routes)){
					foreach ($routes as $route){
						if (empty($route['validate']) && core::$debug)debug::trace('ControllerPage route not contain validate key',error::WARNING);
						if (input::validate($nextPart,$route['validate'])){
							// route match
							$result=$nextPart;
							self::$file_links[self::$page_counter]=$route['page'];
							if (isset($route['filters']))input::filter($result,$route['filters']);
							self::$url_links[self::$page_counter]=$result;
							break;
						}
					}
				}
				if ($result===null){
					// if page not exists - not set links
					if (is_dir($__DIR__.DIRECTORY_SEPARATOR.$exception)){
						self::$file_links[self::$page_counter]=$exception;
					}else{
						unset(self::$url_links[self::$page_counter]);
					}
				}
			}
		}
		self::$url[$__DIR__.DIRECTORY_SEPARATOR.self::$file_links[self::$page_counter]]=(empty($nextPart) or substr($nextPart,0,1)=='_')?self::$url[$__DIR__]:implode('/',self::$url_links);
		self::$next_dir[self::$page_counter]=$temp=$__DIR__.DIRECTORY_SEPARATOR.self::$file_links[self::$page_counter];
		if (self::$lastDir==$__DIR__)array_pop(self::$next_dir);
		self::$indexfile[$temp]=implode('/',self::$file_links);
		self::$nextFolder=$temp.DIRECTORY_SEPARATOR;
	//		self::$story[self::$page_counter]['link']=self::$url[$__DIR__];
		//self::$story[self::$page_counter]['link']=self::$indexfile[$temp];
		self::$page_counter++;
		self::$isRoute=true;
		self::$lastDir=$__DIR__;
		return isset($return)?$return:($nextPart?$nextPart:$default);
	}

	

	/**
	 * @deprecated since version 3.4
	 */
	static function controller_page_force($__DIR__,$default='_default'){
		return self::controllerPageForce($__DIR__,$default);
	}
	static function controllerPageForce($__DIR__,$default='_default'){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		if (self::$lastDir==$__DIR__){
			self::$page_counter--;
			unset(self::$url_links[self::$page_counter]);
		}
		self::$url[$__DIR__]=implode('/',self::$url_links);
		self::$indexfile[$__DIR__]=implode('/',self::$file_links);
		if (!file_exists($__DIR__.'/'.$default.'/index.php'))return self::$nextFolder=false;
		self::$file_links[self::$page_counter]=$default;
		//self::$url[$__DIR__]=implode(DIRECTORY_SEPARATOR,self::$indexfile);
		self::$url[$__DIR__.DIRECTORY_SEPARATOR.self::$file_links[self::$page_counter]]=implode('/',self::$url_links);
		self::$next_dir[self::$page_counter]=$temp=$__DIR__.DIRECTORY_SEPARATOR.self::$file_links[self::$page_counter];
		//if (self::$lastDir==$__DIR__)array_pop(self::$next_dir);
		self::$indexfile[$temp]=implode('/',self::$file_links);
		self::$nextFolder=$temp.DIRECTORY_SEPARATOR;
		self::$page_counter++;
		self::$isRoute=true;
		self::$lastDir=$__DIR__;
		return true;
	}

		/**
		 * @deprecated since version 3.4
		 */
	static function controller_route($routePath){
		return self::controllerRoute($routePath);
	}
	
	static function clearScriptsFull(){
		foreach (self::$required_js as $key=>$hard){
			unset(self::$required_js[$key]);
		}
		foreach (self::$css as $key=>$script){
			unset(self::$css[$key]);
		}
		foreach (self::$js as $key=>$script){
			unset(self::$js[$key]);
		}
	}
	static function clearScripts(){
		foreach (self::$required_js as $key=>$hard){
			if ($hard)unset(self::$required_js[$key]);
		}
		foreach (self::$css as $key=>$script){
			if ($script[1])unset(self::$css[$key]);
		}
		foreach (self::$js as $key=>$script){
			if ($script[1])unset(self::$js[$key]);
		}
	}
		/**
		 * Replace current path of new path and display page from start
		 * @param string $routePath
		 */
	static function controllerRoute($routePath){//,$startDir=''){
		self::$next_dir=array(self::$next_dir[0]);
		self::$links_string=$routePath;
		self::$links=explode('/',self::$links_string);
		self::$url=array();
		self::$indexfile=array();
		self::$url_links=null;
		self::$file_links=array();
		self::$page_counter=1;
		self::$title=array();
		self::$folder=null;
		self::$content=false;
		self::$story=array(array('link'=>''));
		self::$routeMatch=false;
		self::$routeAs=null;
		self::$routeLazyCallback=null;
		self::$nextFolder=self::$base__DIR__.DIRECTORY_SEPARATOR.self::$appFolder.DIRECTORY_SEPARATOR;//.($startDir?$startDir.'/':'');
		self::$isRoute=true;
		self::$lastDir=false;
		self::clearScripts();
	}

	static function getRemaningParams(){
		return implode('/',self::getParamsAsArray());
	}
        /**
	 * Get route vars from stack /_/_/ up to current page
	 * @param array $routes
	 * @return array key=>val
	 * @todo redirect bool val, if params diff from defaults
	 */
	static function route($routes){
		foreach ($routes as $name=>$route){
			$iteration=self::$links[self::$page_counter-1];
			if (core::$debug){
				debug::group('Route '.$name);
				debug::dir($route);
				debug::consoleLog('Input route var: '.$iteration);
			}
			self::$page_counter++;
			if (($iteration==='' or $iteration===null) && isset($route['default'])){
				$out[$name]=$route['default'];
				if (core::$debug){
					debug::consoleLog('Route was choose by default: '.$route['default'],error::WARNING);
					debug::groupEnd();
				}
				continue;
			}
			if (isset($route['filters']))input::filter($iteration,$route['filters']);

			if (isset($route['validate']) && !input::validate($iteration,$route['validate'])){
				if (core::$debug){
					debug::consoleLog('Route not validate ans set by default: '.$route['default'],error::WARNING);
					debug::groupEnd();
				}
				$out[$name]=isset($route['default'])?$route['default']:false;
				continue;
			}
			if (core::$debug){
				debug::consoleLog('Route set as: '.$iteration,error::WARNING);
				debug::groupEnd();
			}
			$out[$name]=$iteration;
			if ($iteration!='')self::$url_links[self::$page_counter-1]=$iteration;
			
		}
		return $out;
	}

	/**
	 * Get route next var from stack /_/_/ up to current page
	 * @param array $route
	 * @return string
	 */
	static function routeNext($route=array()){
		$route=self::route(array('next'=>$route));
		return $route['next'];
	}

	/*layout work*/

	/**
	 * @deprecated since version 3.4
	 */
	static function layout_start_with($__DIR__=null){
		return self::layoutStartWith($__DIR__);
	}
	/**
	 * Start layout from __DIR__
	 * @param string $__DIR__
	 */
	static function layoutStartWith($__DIR__=null){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		$found=array_search($__DIR__,self::$next_dir);
		if ($found===false)return self::$next_dir=array($__DIR__);
		$out=array();
		foreach (self::$next_dir as $key=>$val){
			if ($key<$found)continue;
			$out[$key]=$val;
		}
		return self::$next_dir=$out;
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function layout_start_after($__DIR__=null){
		return self::layoutStartAfter($__DIR__);
	}
	static function layoutStartAfter($__DIR__=null){
		if ($__DIR__===null)$__DIR__=self::$current__DIR__;
		$__DIR__= str_replace('/', DIRECTORY_SEPARATOR, $__DIR__);
		$found=array_search($__DIR__,self::$next_dir);
		if ($found!==false){
			$out=array();
			foreach (self::$next_dir as $key=>$val){
				if ($key<$found+1)continue;
				$out[$key]=$val;
			}
			return self::$next_dir=$out;
		}
		if (core::$debug){
			debug::group('mvc::layoutStartAfter mistmatch', error::ERROR);
			debug::consoleLog('try find "'.$__DIR__.'" in ');
			debug::dir(self::$next_dir);
			debug::groupEnd();
		}
		self::$next_dir=array($__DIR__);
	}

	/**
	 * @deprecated since version 3.4
	 */
	static function layout_parent_remove(){
		return self::layoutParentRemove();
	}
	/**
	 *  Remove parent layout of current page
	 */
	static function layoutParentRemove(){
		$rs=array_pop(self::$next_dir);
		array_pop(self::$next_dir);
		self::$next_dir[]=$rs;
	}

	/**
	 * @deprecated since version 3.4 use layoutRemove
	 */
	static function layout_remove(){
		array_pop(self::$next_dir);
	}
	/**
	 * Remove layout of current page
	 */
	static function layoutRemove(){
		array_pop(self::$next_dir);
	}

	/**
	 * @deprecated since version 3.4 use viewPage
	 */
	static function view_page($showError=null){
		return self::viewPage($showError);
	}
	/**
	* Show subpage link from current page if is wrapper page
	* @param boolean $showError auto show error in beginnig of next layout
	* @return string|boolean filename of found subpage layout
	 */
	static function viewPage($showError=null){
		if ($showError==null){
			if (self::$layoutCounter){
				$showError=true;
			}else{
				if (self::$partical_content_folder===false){
					$showError=false;
				}else{
					self::layoutStartAfter(self::$base__DIR__.DIRECTORY_SEPARATOR.self::$appFolder.self::$partical_content_folder);
					$showError=true;
				}
			}
		}
		self::$layoutCounter++;
		if ($showError!==false)render::showAlerts();
		do {
			$dir=array_shift(self::$next_dir);
			if (!$dir)return false;
			$nextPage=$dir.DIRECTORY_SEPARATOR.(request::isCmd()?'cli':'index').".phtml";
		} while (!is_readable($nextPage));
		return $nextPage;
	}
	/**
	 * @deprecated since version 3.4 use viewPageDefault
	 */
	static function view_page_default($defaultCallable, $showError=true){
		return self::viewPageDefault($defaultCallable,$showError);
	}
	/**
	 * Show subpage as view_page, but if subpage not found shown callable function
	 * @param callable $defaultCallable
	 * @param boolean $showError
	 * @return string|boolean
	 */
	static function viewPageDefault($defaultCallable, $showError=true){
		$page=self::viewPage($showError);
		if ($page===false)return $defaultCallable();
		return $page;
	}
	/**
	 * @deprecated since version 3.4 use viewDefault
	 */
	static function view_default($default_callable, $show_error=true){
		return self::viewPageDefault($default_callable,$show_error);
	}
	/**
	 * Same view page default
	 * @param callable $default_callable callcable function that shown as default
	 * @param boolean $show_error
	 * @return string|boolean
	 */
	static function viewDefault($default_callable, $show_error=true){
		return self::viewPageDefault($default_callable,$show_error);
	}
	/**
	 * @deprecated since version 3.4
	 */
	static function view_layout($layout){
		return self::viewLayout($layout);
	}
	static function viewLayout($layout){
		$next=array_reverse(self::$next_dir);
		foreach ($next as $dir){
			if (file_exists($way=$dir.'/'.$layout.'.phtml'))return $way;
		}
	}
}