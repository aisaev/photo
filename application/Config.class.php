<?php
namespace photo\common;

class Config {
	const DIR_ROOT = __DIR__.'/../';
	const DIR_EDIT = self::DIR_ROOT.'editor/';
	const DIR_PUBLIC = self::DIR_ROOT.'public_html/';
	const DIR_UNPROCESSED = self::DIR_EDIT . 'unprocessed/';
	const DIR_PICSFULL = self::DIR_PUBLIC . 'full/';
	const DIR_PICS = self::DIR_PUBLIC . 'pic/';
	const DIR_SPICS = self::DIR_PUBLIC . 'pic/';
	const DIR_TPICS = self::DIR_PUBLIC . 'tmb/';	
	const LNG_RU = 'ru', LNG_EN = 'en';
	const MODE_EDIT = 0, MODE_EVENT = 1, MODE_PEOPLE = 2, MODE_PLACE = 3;
	
	static public $lng = self::LNG_RU;
	static public $__mode = self::MODE_EVENT;
	
	static function init() {
	    if(isset($_COOKIE['language'])) {
	        self::$lng = $_COOKIE['language'];
	    }
	    switch (self::$lng) {
	        case self::LNG_EN: case self::LNG_RU:
	            break;
	        default:
	            self::$lng = self::LNG_RU;
	            setcookie('language',self::$lng,0);
	    }
	}
}
?>