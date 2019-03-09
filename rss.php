<?php
namespace c;

class rss{
	static $title;
	static $link; // Link to main page
	// var $copyright=''; // copyright
	static $description; // canal description
	static $LastBuildDate='';  // date of last document
	static $language='ru'; // language
	static $generator;
	static $PubDate; // publication date
	static $ManagingEditor;  // E-mail
	//	var $Category;
	static $image_link;
	static $image_url;
	static $image_title;
	/**
	 * @deprecated since version 3.4
	 */
	static function get_out($data){
		return self::getOut($data);
	}
	static function getOut($data){
		//$Date=date("r");   // date in format Mon, 25 Dec 2006 10:23:37 +0400
		$out='<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
<channel>
<title>'.self::$title.'</title>
<link>'.self::$link.'</link>
<description><![CDATA['.self::$description.']]></description>
<lastBuildDate>'.self::$LastBuildDate.'</lastBuildDate>
<language>'.self::$language.'</language>
<generator>'.self::$generator.'</generator>
<pubDate>'.self::$PubDate.'</pubDate>
<managingEditor>'.self::$ManagingEditor.'</managingEditor>';
		if (self::$image_link){
			$out.='<image>
<link>'.self::$image_link.'</link>
<url>'.self::$image_url.'</url>
<title>'.self::$image_title.'</title>
</image>';
		}
		foreach ($data as $value){
			if (empty($value['author']))$value['author']='';
			$out.=self::PrintBody($value['title'],$value['link'],$value['description'].(isset($value['description2'])?$value['description2']:''),$value['category'],date("r",strtotime($value['pubdate'])),$value['author']);
		}
		return $out.'</channel></rss>';
	}
	private static function PrintBody($title,$link,$description,$category,$pubDate,$author=''){
		return '<item>
<title><![CDATA['.$title.']]></title>
<link>'.$link.'</link>
<description><![CDATA['.$description.']]></description>
'.($author?'<author>'.$author.'</author>':'').
'<category>'.$category.'</category>
<pubDate>'.$pubDate.'</pubDate>
<guid>'.$link.'</guid>
</item>';
	}
}
rss::$PubDate=date('r');