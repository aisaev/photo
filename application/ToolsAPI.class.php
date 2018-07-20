<?php
namespace photo\edit;

spl_autoload_register(function($name){
    if (substr_compare($name, 'lsolesen\\pel\\', 0, 13) === 0) {
        $classname = str_replace('lsolesen\\pel\\', '', $name);
        $load = realpath('/var/www/pel/src/' . $classname . '.php');
        if ($load !== false) {
            include_once realpath($load);
        }
    } else {    
    $fn=str_replace('\\', DIRECTORY_SEPARATOR, $name);
    $fn=str_replace('photo/common/', '', $fn);    
    $fn=str_replace('photo/', '', $fn);
    $fn=__DIR__ .DIRECTORY_SEPARATOR.$fn.(substr($name,0,3)=='lso'?'':'.class').'.php';
    if(file_exists($fn)) {
        include $fn;
    } else {
        return false;
    }
    }
});

use photo\DAO\EventDAO;
use photo\DAO\LocationDAO;
use photo\DAO\PersonDAO;
use photo\DAO\PhotoDAO;
use photo\Model\Event;
use photo\Model\Location;
use photo\Model\Person;
use photo\common\Config;
use photo\common\DBHelper;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelTiff;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelEntryRational;
use lsolesen\pel\PelEntryByte;
use lsolesen\pel\PelEntryTime;

final class ToolsAPI
{
    private $oDir = NULL;
    
    public function process()
    {
        if (! isset($_GET['op'])) {
            throw new \Exception('Operation required');
        }
        Config::$__mode = Config::MODE_EDIT;
        switch ($_GET['op']) {
            case 'ap':
                // audit photos
                //return $this->auditPhotos();
            
            case 'cpf':
                //copy single image file to production. ID is known
                return $this->copyImageToProduction()?$_POST:[];
                
            case 'db':
                //single dir
                return $this->saveDir();
                
            case 'draft':
                return $this->saveDraft();
                
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
            case 'edppl': //edit person
                if (!isset($_POST['n'])) throw new \Exception('No person data received');
                $o = new Person();
                $dao = PersonDAO::getInstance();
                $dao->initFromPOST($_POST,$o);
                $dao->save($o);
                return $o;
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
            case 'lbc':
                //lock dir before processing file copy to production
                if(!isset($_POST['d'])) throw new \Exception("Invalid arguments");
                $dir = $_POST['d'];
                $this->oDir = new PhotoDir(Config::DIR_UNPROCESSED,$dir);
                $this->oDir->lock();
                return true;
            case 'll':
                $dao = LocationDAO::getInstance();
                return $dao->getList();
            case 'pl':
                $dao = PersonDAO::getInstance();
                return $dao->getList();
            case 'ploc': //update count of photos per location
                return $this->updatePhotosPerLocation();
            case 'raa': 
                return $this->resetAfterAdd();
            case 'rs_rd':
                return $this->prepareListToResize();
            
            case 'thumb':
                //build single thumbnail
                if (!isset($_REQUEST['d']) || !isset($_REQUEST['f'])) throw new \Exception('Missing parameter');
                $oThumb = new Thumbnail(Config::DIR_UNPROCESSED.$_REQUEST['d'], $_REQUEST['f']);
                $a = $_POST;
                $a['ok']=($oThumb->process(isset($_REQUEST['fix']))?1:0);
                return $a;
            
            case 'tonf2d':
                return $this->updateTakenOnInDB();
                
            case 'upl': //get unprocessed photos
                return $this->collectPhotos();
            case 'cp_exif': //set exif for full IMG where
                if (!isset($_REQUEST['nn'])) throw new \Exception('Missing NN');
                return $this->updateEXIF();
            default:
                throw new \Exception('Operation invalid');
        }
    }
    
    private function auditPhotos() {
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
    
    private function buildDirDefault($dir) {
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
    
    private function collectPhotos():PhotoDir {
        //scans location /var/www/unprocessed hierarchically and prepares array of files to be processed, with metadata
        $o = new PhotoDir(Config::DIR_UNPROCESSED,'');
        $o->collect();
        return $o;        
    }

    /**
     * Convert a decimal degree into degrees, minutes, and seconds.
     *
     * @param
     *            int the degree in the form 123.456. Must be in the interval
     *            [-180, 180].
     *
     * @return array a triple with the degrees, minutes, and seconds. Each
     *         value is an array itself, suitable for passing to a
     *         PelEntryRational. If the degree is outside the allowed interval,
     *         null is returned instead.
     */
    function convertDecimalToDMS($degree)
    {
        if ($degree > 180 || $degree < - 180) {
            return null;
        }
        $degree = abs($degree); // make sure number is positive
        // (no distinction here for N/S
        // or W/E).
        $seconds = $degree * 3600; // Total number of seconds.
        $degrees = floor($degree); // Number of whole degrees.
        $seconds -= $degrees * 3600; // Subtract the number of seconds
        // taken by the degrees.
        $minutes = floor($seconds / 60); // Number of whole minutes.
        $seconds -= $minutes * 60; // Subtract the number of seconds
        // taken by the minutes.
        $seconds = round($seconds * 100, 0); // Round seconds with a 1/100th
        // second precision.
        return [
            [
                $degrees,
                1
            ],
            [
                $minutes,
                1
            ],
            [
                $seconds,
                100
            ]
        ];
    }
    
    private function copyImageToProduction() {
        if(!isset($_POST['d'])) throw new \Exception("Invalid arguments");
        $dir = $_POST['d'];
        if(!isset($_POST['id'])) throw new \Exception("No ID specified");
        $id = intval($_POST['id']);
        $this->oDir = new PhotoDir(Config::DIR_UNPROCESSED,$dir);
        $this->oDir->collect(true);
        return $this->oDir->copyToProductionSingle($id);
        
    }
    
    private function createSingleThumb($size, $fn_src)
    {
        switch ($size) {
            case Config::DIR_PICSFULL:
                $w = 2560;
                $h = 2560;
                $q = 85;
                break;
            case Config::DIR_PICS:
                $w = 1280;
                $h = 1280;
                $q = 85;
                break;
            case Config::DIR_TPICS:
                $w = 640;
                $h = 480;
                $q = 80;
                break;
            default:
                throw new \Exception('Unrecognized size '.$size);
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
    
    private function prepareListOfDBPhotos() {
        $a = PhotoDAO::getInstance()->getList();
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
    
    private function prepareListToResize()
    {
        // ReSize-Read Dirs - returns array of dirs in picsfull for files
        // that don't have all smaller sizes
        
        // check if dirs exist
        $this->checkCreateDir(Config::DIR_PICS);
        $this->checkCreateDir(Config::DIR_TPICS);
        
        $dirNN = dir(Config::DIR_RAW);
        $a = [];
        while (false !== ($l_d = $dirNN->read())) {
            if ($l_d == '.' || $l_d == '..')
                continue;
                if (! is_dir(Config::DIR_RAW . $l_d)) {
                    continue;
                }
                $l_dp = Config::DIR_PICS . $l_d . '/';
                $l_dt = Config::DIR_TPICS . $l_d . '/';
                
                $this->checkCreateDir($l_dp);
                $this->checkCreateDir($l_dt);
                
                $files = dir(Config::DIR_RAW . $l_d);
                while (false !== ($l_f = $files->read())) {
                    // only consider JPG files
                    if ($l_f == '.' || $l_f == '..') {
                        continue;
                    }
                    if (is_dir(Config::DIR_RAW . $l_d . '/'.$l_f)) {
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
    
    private function prepareSmallerPics($f)
    {
        // make sure file name is kosher
        if (preg_match('/^\d\d\d\d\d\.jpg$/', $f) == 0) {
            return 'Bad file name ' . $f;
        }
        try 
        {
            $this->createSingleThumb(Config::DIR_PICS, $f);
            $this->createSingleThumb(Config::DIR_TPICS, $f);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return 1;
    }
    
    private function resetAfterAdd(): bool {
        //update photo counters
        $pdao = PersonDAO::getInstance();
        $pdao->refreshPPN();
        $ldao = LocationDAO::getInstance();
        $ldao->refreshPPN();
        //remove generated .js files
        exec('rm -R '.Config::DIR_PUBLIC.'/evt');
        exec('rm -R '.Config::DIR_PUBLIC.'/loc');
        exec('rm -R '.Config::DIR_PUBLIC.'/ppl');
        return true;
    }
    
    private function saveDir() {
        if(!isset($_POST['d'])) throw new \Exception("Invalid arguments");
        $dir = $_POST['d'];
        $this->oDir = new PhotoDir(Config::DIR_UNPROCESSED,$dir);
        $this->oDir->collect();
        $dir_defaults = [];
        $dir_defaults[$dir] = $this->buildDirDefault($_POST);
        $this->oDir->updateFromPOST($dir_defaults);
        return $this->oDir->saveDB();
    }
    
    private function saveDraft() {
        //save draft
        if(!isset($_POST['d'])) throw new \Exception("Invalid arguments");
        $this->oDir = $this->collectPhotos();
        $dir_defaults = [];
        foreach ($_POST['d'] as $dir) {
            $dir_defaults[$dir['d']] = $this->buildDirDefault($dir);
        }
        return $this->oDir->updateFromPOST($dir_defaults);        
    }
    
    private function updateEXIF() { //total hack, since not properly decoupled
        $nn = $_REQUEST['nn'];
        $path = Config::DIR_ROOT.'photo/full_google/'.$nn;
        if(preg_match('/\d\d/', $nn)==0 || !is_dir($path)) {
            throw new \Exception("Invalid dir ".$path);            
        }
        //read files
        $l_d = dir($path);
        if($l_d == NULL || $l_d ===FALSE) throw new \Exception("Error with dir ".$path);
        //pre-read EXIF data from database
        $id_from = intval($nn)*1000;
        $id_to = $id_from+999;
        $pdo = DBHelper::getPDO();
        $a = [];
        $sql = 'SELECT * FROM public.photos WHERE photoid BETWEEN '.$id_from.' AND '.$id_to.' ORDER BY photoid';
        foreach ($pdo->query($sql) as $rec) {
            $a[$rec['photoid']] = array(
                'taken_on'=>$rec['taken_on'],
                'lat' => $rec['Latitude'],
                'lng' => $rec['Longitude']
                );
        }
        $r=[];
        $r['nodb']=[];
        $r['pelrd']=[];
        $r['nogeo']=[];
        $r['nodt']=[];
        $r['nowr']=[];
        while (false !== ($l_f = $l_d->read())) {
            if(preg_match('/jpg/i',$l_f)==0 || is_dir($l_f)) continue;
            $id = intval(substr($l_f, 0,5));
            $dst = '/var/www/photo/photo/full_google/'.$l_f;
            if(file_exists($dst)) continue;
            if(!isset($a[$id])) {
                $r['nodb'][]=$id; //not in DB
                continue;
            }
            $o=$a[$id];
            $jpeg = new PelJpeg($path.'/'.$l_f);
            if($jpeg==null) {
                $r['pelrd'][]=$id;
                continue;
            }
            $exif = $jpeg->getExif();

            $dto=null;
            $lat=null;
            $lng=null;
            $latr=null;
            $lngr=null;
            $ifd_gps==null;
            $tiff = null;
            $ifd = null;
            $ifd_exif = null;
            
            if($exif!=null) {
                $tiff = $exif->getTiff();
                if ($tiff!=null) {
                    $ifd = $tiff->getIfd();
                    if($ifd!=null) {
                        $ifd_exif = $ifd->getSubIfd(PelIfd::EXIF);
                        if($ifd_exif!=null) {
                            $dto=$ifd_exif->getEntry(PelTag::DATE_TIME_ORIGINAL);
                            if($dto==null) $dto=$ifd_exif->getEntry(PelTag::DATE_TIME);
                        }
                        $ifd_gps = $ifd->getSubIfd(PelIfd::GPS);                        
                        if($ifd_gps!=null) {
                            $lat=$ifd_gps->getEntry(PelTag::GPS_LATITUDE);
                            $lng=$ifd_gps->getEntry(PelTag::GPS_LONGITUDE);
                            $latr=$ifd_gps->getEntry(PelTag::GPS_LATITUDE_REF);
                            $lngr=$ifd_gps->getEntry(PelTag::GPS_LONGITUDE_REF);
                        }
                    }
                }
            }
            if($ifd_exif==null) {
                $ifd_exif = new PelIfd(PelIfd::EXIF);             
            }            
            if($ifd_gps==null) {
                $ifd_gps = new PelIfd(PelIfd::GPS);
            }
            if($ifd==null) {
                $ifd=new PelIfd(PelIfd::IFD0);
            }
            if($tiff==null) {
                $tiff = new PelTiff();
            }
            if($exif==null) {
                $exif = new PelExif();
            }
            $write_me = false;
            if($o['lat']!=null && $lat==null) {
                $ifd_gps->addEntry(new PelEntryByte(PelTag::GPS_VERSION_ID, 2, 2, 0, 0));
                $latitude = $o['lat'];
                $longitude = $o['lng'];
                list ($hours, $minutes, $seconds) = $this->convertDecimalToDMS($latitude);
                /* We interpret a negative latitude as being south. */
                $latitude_ref = ($latitude < 0) ? 'S' : 'N';
                $ifd_gps->addEntry(new PelEntryAscii(PelTag::GPS_LATITUDE_REF, $latitude_ref));
                $ifd_gps->addEntry(new PelEntryRational(PelTag::GPS_LATITUDE, $hours, $minutes, $seconds));
                /* The longitude works like the latitude. */
                list ($hours, $minutes, $seconds) = $this->convertDecimalToDMS($longitude);
                $longitude_ref = ($longitude < 0) ? 'W' : 'E';
                $ifd_gps->addEntry(new PelEntryAscii(PelTag::GPS_LONGITUDE_REF, $longitude_ref));
                $ifd_gps->addEntry(new PelEntryRational(PelTag::GPS_LONGITUDE, $hours, $minutes, $seconds));
                $write_me = true;                
            } else $r['nogeo'][]=$id;
            
            if ($o['taken_on']!=null) {
                $tst = \DateTime::createFromFormat('Y-m-d H:i:s',$o['taken_on']);
                $ifd_exif->addEntry(new PelEntryTime(PelTag::DATE_TIME_ORIGINAL, $tst->getTimestamp()));
                $write_me = true;
            } else $r['nogeo'][]=$id;
            if($write_me) {
                $inter_ifd = new PelIfd(PelIfd::INTEROPERABILITY);
                $ifd->addSubIfd($inter_ifd);
                $ifd->addSubIfd($ifd_exif);
                $ifd->addSubIfd($ifd_gps);
                $tiff->setIfd($ifd);
                $exif->setTiff($tiff);
                $jpeg->setExif($exif);
                file_put_contents($dst, $jpeg->getBytes());
            } else else $r['nowr'][]=$id;
        }
        sort($r['nodb']);
        sort($r['nogeo']);
        return $r;
    }
    
    private function updatePhotosPerLocation() {
        $dao = LocationDAO::getInstance()->refreshPPN();
    }
    
    private function updateTakenOnInDB() {
        $path = Config::DIR_PICSFULL;
        $pdo = DBHelper::getPDO();
        $sql = 'SELECT photoid, taken_on FROM public.photos p JOIN public.events e ON e.evntid = p.event ORDER BY taken_on, date_from, date_to, sequence, seqnum, photoid';
        $a=[];
        foreach ($pdo->query($sql) as $rec) {
            $n5 = sprintf('%05d',$rec['photoid']);
            $n2 = substr($n5, 0,2);
            $fn = $path.$n2.'/'.$n5.'.jpg';            
            if(file_exists($fn)) {
                $exif = exif_read_data($fn);
                if(isset($exif['DateTimeOriginal'])) {
                    $dt = explode(' ', $exif['DateTimeOriginal']);
                    if ($dt !== false && count($dt) == 2) {
                        $d = str_replace(':', '-', $dt[0]);
                        $d .= ' ' . $dt[1];
                        if(intval(substr($d,0,4))>1990 && $rec['taken_on']!= $d) {
                            $a[$rec['photoid']]=array('db'=>$rec['taken_on'],'to'=>$d);
                        }                             
                    }
                }
            }
        }
        $upd = $pdo->prepare("UPDATE public.photos SET taken_on = ? WHERE photoid = ?");
        foreach ($a as $id=>$ton) {
            if(!$upd->execute([$ton['to'],$id])) throw new \Exception('Failed to update for id '.$id);
        }
        
        return $a;        
    }
     
}

