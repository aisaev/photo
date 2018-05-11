<?php
namespace photo\edit;

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

use photo\common\Config;
use photo\DAO\EventDAO;
use photo\DAO\PhotoDAO;
use photo\DAO\LocationDAO;
use photo\DAO\PersonDAO;
use photo\Model\Event;
use photo\Model\Location;

class ToolsAPI
{
    public function process()
    {
        if (! isset($_GET['op'])) {
            throw new \Exception('Operation required');
        }
        Config::$__mode = Config::MODE_EDIT;
        switch ($_GET['op']) {
            case 'ap':
                // audit photos
                return $this->auditPhotos();
            case 'db':
                //single dir
                if(!isset($_POST['d'])) {
                    throw new \Exception("Invalid arguments");
                }
                $dir = filter_var($_POST['d'],FILTER_SANITIZE_STRING);
                if($dir===FALSE) {
                    throw new \Exception("Invalid arguments, RC=2");
                } elseif (!is_dir(Config::DIR_UNPROCESSED.$dir)) {
                    throw new \Exception("Invalid arguments, RC=3");
                }
                $oDir = new PhotoDir(self::DIR_UNPROCESSED,$dir);
                $oDir->collect();
                $dir_defaults = [];
                $dir_defaults[$dir] = $this->buildDirDefault($_POST);
                $oDir->updateFromPOST($dir_defaults);
                return $oDir->saveDB();
                
            case 'draft':
                //save draft
                if(!isset($_POST['d'])) throw new \Exception("Invalid arguments");
                $oDir = $this->collectPhotos();
                $dir_defaults = [];
                foreach ($_POST['d'] as $dir) {
                    $dir_defaults[$dir['d']] = $this->buildDirDefault($dir);
                }
                return $oDir->updateFromPOST($dir_defaults);
                
            case 'edevt':
                if (!isset($_POST['f']))
                    throw new \Exception('No event data received');
                $o = new Event();                
                $dao = EventDAO::getInstance();
                $dao->initFromPOST($_POST,$o);
                $dao->create($o);
                return $o;
            case 'edl': //edit location
                if (!isset($_POST['u'])) throw new \Exception('No location data received');
                if(!is_array($_POST['u'])) throw new \Exception('Invalid data, RC=1');
                
                $dao = LocationDAO::getInstance();
                $a=[];

                foreach ($_POST['u'] as $lupdate) {
                    $o = new Location();
                    if(!isset($lupdate['id'])) {
                        continue;
                    }
                    $resp = array('o' => trim($lupdate['id']));
                    if(strlen($resp['o'])>=1 && substr($resp['o'], 0,1)=='$'){
                        unset($lupdate['id']); //new
                    }
                    
                    $dao->initFromPOST($lupdate, $o);
                    if($o->descr_e == NULL) {
                        $o->descr_e = $o->descr_r;
                    }
                    $dao->save($o);
                    $resp['id'] = $o->id;
                    $a[]=$resp;
                }
                return $a;
            case 'el':
                $dao = EventDAO::getInstance();
                return $dao->getList(); 
            case 'rs_10':
                // resize list of files that came in POST
                $a = [];
                if (! isset($_POST['p'])) {
                    throw new \Exception('Photo list not supplied');
                }
                foreach ($_POST['p'] as $fn) {
                    $a[$fn] = $this->prepareSmallerPics($fn);
                }
                return $a;
            case 'll':
                $dao = LocationDAO::getInstance();
                return $dao->getList();
            case 'pl':
                $dao = PersonDAO::getInstance();
                return $dao->getList();
            case 'ploc': //update count of photos per location
                return $this->updatePhotosPerLocation();
            case 'rs_rd':
                return $this->prepareListToResize();
            case 'upl': //get unprocessed photos
                return $this->collectPhotos();
            default:
                throw new \Exception('Operation invalid');
        }
    }
    
    private function updatePhotosPerLocation() {
        $dao = LocationDAO::getInstance()->refreshPPN();
    }
    
    protected function auditPhotos() {
        // get all photo IDs from DB that have event assignment
        $a = [];
        $pFiles = $this->prepareListOfPics();
        $pDB = PhotoDAO::getInstance()->getList();
        foreach ($pDB as $o) {
            $fn=$o->getFileName();
            if($o->hide) {
                if(isset($pFiles[$fn])) {
                    $a[$o->id]=1; //file for hidden rec exists
                    unset($pFiles[$fn]);
                }
            } else {
                if(!isset($pFiles[$fn])) {
                    //no file for good DB rec, try to get
                    try {
                        $jpegContent = file_get_contents('http://192.168.2.5/pics/'.substr($fn,0,2).'/'.$fn);
                        if($jpegContent===false) {
                            $a[$o->id]=2;
                        } else {
                            if(file_put_contents(Config::DIR_PICSFULL.substr($fn,0,2).'/'.$fn, $jpegContent)===false) {
                                $a[$o->id]=4;
                            } else {
                                $a[$o->id]=0;
                            }
                        }
                    } catch (\Exception $e) {
                        $a[$o->id]=5;
                    }
                    
                } else {
                    unset($pFiles[$fn]);
                }
            }
        }
        foreach ($pFiles as $fn=>$v) {
            $a[$fn]=3;
        }  
        return $a;
        
        // read all photo IDs from files in picsfull
        // match DB to file
    }
    
    protected function prepareListToResize()
    {
        // ReSize-Read Dirs - returns array of dirs in picsfull for files
        // that don't have all smaller sizes
        
        // check if dirs exist
        $this->checkCreateDir(Config::DIR_PICS);
        $this->checkCreateDir(Config::DIR_TPICS);
        
        $dirNN = dir(Config::DIR_PICSFULL);
        $a = [];
        while (false !== ($l_d = $dirNN->read())) {
            if ($l_d == '.' || $l_d == '..')
                continue;
                if (! is_dir(Config::DIR_PICSFULL . $l_d))
                    continue;
                    $l_dp = Config::DIR_PICS . $l_d . '/';
                    $l_dt = Config::DIR_TPICS . $l_d . '/';
                    
                    $this->checkCreateDir($l_dp);
                    $this->checkCreateDir($l_dt);
                    
                    $files = dir(Config::DIR_PICSFULL . $l_d);
                    while (false !== ($l_f = $files->read())) {
                        // only consider JPG files
                        if ($l_f == '.' || $l_f == '..') {
                            continue;
                        }
                        if (is_dir(Config::DIR_PICSFULL . $l_d . '/'.$l_f)) {
                            continue;
                        }
                        if (preg_match('/^\d\d\d\d\d\.jpg$/', $l_f) == 0) {
                            continue;
                        }
                        // check if "pic" and "tmb" exist
                        if (! file_exists($l_dp . $l_f) || ! file_exists($l_dt . $l_f) 
                            || filesize($l_dp . $l_f)==0 || filesize($l_dt . $l_f)==0) {
                            $a[] = $l_f;
                        }
                    }
        }
        sort($a);
        return $a;
    }
    
    private function prepareListOfPics()
    {
        $dirNN = dir(Config::DIR_PICSFULL);
        $a = [];
        while (false !== ($l_d = $dirNN->read())) {
            if ($l_d == '.' || $l_d == '..' || ! is_dir(Config::DIR_PICSFULL . $l_d)) {
                continue;
            }
            $dNN=Config::DIR_PICSFULL . $l_d.'/';
            $files = dir($dNN);
            while (false !== ($l_f = $files->read())) {
                // only consider .jpg files
                if ($l_f == '.' || $l_f == '..' ||
                    is_dir($dNN. $l_f) ||
                    preg_match('/^\d\d\d\d\d\.jpg$/', $l_f) == 0) {
                        continue;
                    }
                    if(filesize($dNN. $l_f)>0) {
                        $a[$l_f]=true;
                    }
                        
            }
        }
        return $a;
    }
    
    private function prepareListOfDBPhotos() {
        $a = PhotoDAO::getInstance()->getList();
        return $a;
        
    }
    
    private function prepareSmallerPics($f)
    {
        // make sure file name is kosher
        if (preg_match('/^\d\d\d\d\d\.jpg$/', $f) == 0) {
            return 'Bad file name ' . $f;
        }
        try {
            $this->createSingleThumb(Config::DIR_PICS, $f);
            $this->createSingleThumb(Config::DIR_TPICS, $f);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return 1;
    }
    
    private function collectPhotos() {
        //scans location /var/www/unprocessed hierarchically and prepares array of files to be processed, with metadata
        $o = new PhotoDir(Config::DIR_UNPROCESSED,'');
        $o->collect(0);
        return $o;        
    }
    
    private function createSingleThumb($size, $fn_src)
    {
        switch ($size) {
            case Config::DIR_PICS:
                $w = 1280;
                $h = 1280;
                $q = 70;
                break;
            case Config::DIR_TPICS:
                $w = 480;
                $h = 480;
                $q = 50;
                break;
            default:
                throw new \Exception('Unrecognized size ' . $size);
        }
        $dd = substr($fn_src, 0, 2);
        $thumbdir = $size . $dd;
        $fn_dst = $thumbdir . '/' . $fn_src;
        if (file_exists($fn_dst) && filesize($fn_dst)>0) {
            return;
        }
        $oPic = new Thumbnail(Config::DIR_PICSFULL . $dd . '/', $fn_src);
        $oPic->resize_image($w, $h, $fn_dst, $q);
    }
    
    protected function buildDirDefault($dir) {
        return [
            'e'=>isset($dir['e'])?intval($dir['e']):0,
            'l'=>isset($dir['l'])?intval($dir['l']):0,
            'p'=>isset($dir['p'])&&is_array($dir['p'])?$dir['p']:[],
            'f'=>isset($dir['f'])&&is_array($dir['f'])?$dir['f']:[]
        ];
    }
    
    private function checkCreateDir($d)
    {
        if (! file_exists($d)) {
            if (! mkdir($d)) {
                throw new \Exception("Failed to create " . $d);
            }
        } elseif (! is_dir($d)) {
            throw new \Exception($d . " is not a directory");
        }
    }
}
