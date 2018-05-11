<?php
namespace photo\common;

use photo\Controller\EventController;
use photo\Controller\LocationController;
use photo\Controller\PhotoController;
use photo\Controller\PersonController;
use photo\DAO\LocationDAO;
use photo\DAO\PersonDAO;
use photo\DAO\EventDAO;

spl_autoload_register(function($name){
    $fn=str_replace('\\', DIRECTORY_SEPARATOR, $name);
    $fn=str_replace('photo/common/', '', $fn);
    $fn=str_replace('photo/', '', $fn);
    $fn=__DIR__ .DIRECTORY_SEPARATOR.$fn.'.class.php';
    if(file_exists($fn)) {
        include $fn;
    } else {
        return false;
    }
});


class PhotosetHelper {
    const PFX_EVENT = 'evt', PFX_PLACE = 'loc', PFX_PERSON = 'ppl';
    const JS_ERR = 'js/err.js';
    
    public function __construct() {
        Config::init();        
    }
    
    public function get_data_file_name() {
        if(!isset($_GET['id']) || !isset($_GET['op']) ) {
            return '/js/err.js';
        } else {
            switch($_GET['op'])
            {
                case 'e': //event
                    $pfx = self::PFX_EVENT;
                    Config::$__mode = Config::MODE_EVENT;
                    break;
                case 'l': //place
                    $pfx = self::PFX_PLACE;
                    Config::$__mode = Config::MODE_PLACE;
                    break;
                case 'p': //person
                    $pfx = self::PFX_PERSON;
                    Config::$__mode = Config::MODE_PEOPLE;
                    break;
                default:
                    return self::JS_ERR;
            }
            $id=intval($_GET['id']);
            
            $uri='/'.$pfx.'/'.Config::$lng.'/'.$id.'.js';
            $dir = Config::DIR_PUBLIC.$pfx;
            if(!file_exists($dir)) {
                mkdir($dir);
            }
            $dir.='/'.Config::$lng;
            if(!file_exists($dir)) {
                mkdir($dir);
            }
            $dir.='/';
            $file_name = $dir.$id.'.js';
            //check if /{event|place|person}/{lng}/{id}.js exist
            if(!file_exists($file_name)) {
                $listEvents = [];
                $listPlaces = [];
                $listPeople = [];
                try {
                    switch ($pfx) {
                        case self::PFX_EVENT:
                            $oe = EventController::getInstance()->ReadSingle($id);
                            $listEvents=[$oe];
                            $listPhotos=PhotoController::getInstance()->ListForEvent($oe);
                            $hashLoc = [];
                            $hashPpl = [];
                            foreach ($listPhotos as $oPhoto) {
                                $hashLoc[$oPhoto->location] = true;
                                if($oPhoto->people!==null) {
                                    foreach ($oPhoto->people as $k=>$pplid) {
                                        $hashPpl[$pplid]=true;
                                    }
                                }
                            }
                            $listPlaces = LocationDAO::getInstance()->getList(array_keys($hashLoc));
                            $listPeople = PersonDAO::getInstance()->getList(array_keys($hashPpl));
                            break;
                        case self::PFX_PERSON:
                            $op = PersonController::getInstance()->ReadSingle($id);
                            $listPhotos=PhotoController::getInstance()->ListForPerson($op);
                            $hashLoc = [];
                            $hashPpl = [];
                            $hashEvt = [];
                            foreach ($listPhotos as $oPhoto) {
                                $hashLoc[$oPhoto->location] = true;
                                $hashEvt[$oPhoto->event] = true;
                                foreach ($oPhoto->people as $k=>$pplid) {
                                    $hashPpl[$pplid]=true;
                                }
                            }
                            $listEvents = EventDAO::getInstance()->getList(array_keys($hashEvt));
                            $listPlaces = LocationDAO::getInstance()->getList(array_keys($hashLoc));
                            $listPeople = PersonDAO::getInstance()->getList(array_keys($hashPpl));
                            break;
                        case self::PFX_PLACE:
                            $ol = LocationController::getInstance()->ReadSingle($id);
                            $listPhotos=PhotoController::getInstance()->ListForLocation($ol);
                            $listPlaces = [$ol];
                            if($ol->parentList!=NULL) {
                                foreach ($ol->parentList as $olp) $listPlaces[]=$olp;
                            }
                            $hashLoc = [];
                            $hashPpl = [];
                            $hashEvt = [];
                            $hasPhotos = false;
                            foreach ($listPhotos as $oPhoto) {
                                $hasPhotos = true;
                                $hashEvt[$oPhoto->event] = true;
                                if($oPhoto->people!==null) {
                                    foreach ($oPhoto->people as $k=>$pplid) {
                                        $hashPpl[$pplid]=true;
                                    }
                                }
                            }
                            if($hasPhotos) {
                                $listEvents = EventDAO::getInstance()->getList(array_keys($hashEvt));
                                if(count(array_keys($hashPpl))>0) {
                                    $listPeople = PersonDAO::getInstance()->getList(array_keys($hashPpl));
                                }
                            }
                            break;
                    }                    
                    $a = ['events'=>$listEvents,'places'=>$listPlaces,'people'=>$listPeople,'photos'=>$listPhotos];
                } catch (\Exception $e) {
                    return self::JS_ERR;
                }
                //generate data file
                $el_json = 'var events = '.json_encode($listEvents,JSON_UNESCAPED_UNICODE).';';
        	    $ll_json = 'var places = '.json_encode($listPlaces,JSON_UNESCAPED_UNICODE).';';
        	    $pl_json = 'var people = '.json_encode($listPeople,JSON_UNESCAPED_UNICODE).';';
        	    $ph_json = 'var photos = '.json_encode($listPhotos,JSON_UNESCAPED_UNICODE).';';
        	    
        	    file_put_contents($file_name, $el_json."\n".$ll_json."\n".$pl_json."\n".$ph_json);	    
            }
            return '<script src="'.$uri.'"></script>';
        }        
    }
}
?>