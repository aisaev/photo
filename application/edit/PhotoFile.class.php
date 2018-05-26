<?php
namespace photo\edit;

use photo\Model\Photo;
use \DateTime;
use \DateInterval;
use \Exception;
use photo\common\Config;
use photo\DAO\PhotoDAO;

final class PhotoFile extends Photo implements \JsonSerializable {
	public $filename = NULL;
	private $filename_new = NULL;
	public $unproc = true;	
	
	public function __construct($dir=NULL,$filename=NULL,$off_m=0) {
		if($dir==NULL) return;
		
		$this->filename = $filename;
		
		$exif = exif_read_data($dir.'/'.$filename);
		if(preg_match('/^IMG_20\d{6}_\d{6}\.*/', $filename)) {
			//camera photo, timestamp in file name
			$a = explode('_', $this->filename);
			$d = $a[1];
			$t = $a[2];
			$this->taken_on = date(substr($d, 0,4) . '-' . substr($d, 4,2) . '-' . substr($d, 6,2) . ' ' . substr($t, 0,2) . ':' . substr($t, 2,2) . ':' . substr($t, 4,2));
		} else {
			$tst = NULL;
			if(isset($exif['DateTimeOriginal'])) {
				$tst = $exif['DateTimeOriginal'];
			} else if(isset($exif['DateTime'])) {
				$tst = $exif['DateTime'];
			} else if(isset($exif['FileDateTime'])) {
				$tst = date('Y:m:d H:i:s',$exif['FileDateTime']);
			}
			if($tst != NULL) {
				$dt = explode(' ', $tst);
				if ($dt !== false && count($dt) == 2) {
					$d = str_replace(':', '-', $dt[0]);
					$this->taken_on = date( $d . ' ' . $dt[1]);
					$tst = DateTime::createFromFormat('Y-m-d H:i:s',$this->taken_on);
					$this->addOffset($off_m);
				}
			}
		}
		
		if(isset($exif['GPSLatitude']) && isset($exif['GPSLatitudeRef'])) $this->geo_lat = $this->convertExifGeo($exif['GPSLatitude'],$exif['GPSLatitudeRef']);
		if(isset($exif['GPSLongitude']) && isset($exif['GPSLongitudeRef'])) $this->geo_lon = $this->convertExifGeo($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
	}
	
	public function addOffset($off_m) {
	    if($off_m==0 || substr($this->filename,0,1)!='P') return;
	    $tst = DateTime::createFromFormat('Y-m-d H:i:s',$this->taken_on);
	    $offset=false;
	    if($off_m>0) $offset='PT'.$off_m.'M';
	    elseif ($off_m<0)$offset='PT'.(-$off_m).'M';
	    if($offset!==false) {
	        $interval = new DateInterval($offset);
	        if($off_m>0) $tst->add($interval);
	        else $tst->subtract($interval);
	    }
	    $this->taken_on = $tst->format('Y-m-d H:i:s');	    
	}
	
	private function convertExifGeo($coord,$ewns) {
		$ll = NULL;
		if(is_array($coord)) {
			$i=1;
			foreach ($coord as $lat_part) {
				$lat = explode('/', $lat_part);
				if(is_array($lat)) $lat_num = intval($lat[0]) / intval($lat[1]);
				else $lat_num = intval($lat_part);
				switch($i++) {
					case 1: //degrees
						$ll = $lat_num;
						break;
					case 2:
						$ll += $lat_num / 60;
						break;
					case 3:
						$ll += $lat_num / 3600;
						break;
				}
			}
		}
		if($ewns == 'S' || $ewns == 'W') $ll = - $ll;
		return $ll;
	}
	
	public function copyToArchive($dir_src) {
	    if($this->id==0) throw new Exception('Photo ID is 0 for'.$this->filename);
	    if($this->id > 99999) throw new Exception('Photo ID limit reached');
	    $fn_short = '';
	    if($this->id < 10) $fn_short = '0000';
	    elseif($this->id < 100) $fn_short = '000';
	    elseif($this->id < 1000) $fn_short = '00';
	    elseif($this->id < 10000) $fn_short = '0';
	    $fn_short .= $this->id.'.jpg';
	    $dd = substr($fn_short, 0,2);
	    $dir_dst = Config::DIR_PICSFULL.$dd;
	    $fn_src = $dir_src.$this->filename;
	    if (!is_dir($dir_dst)) {
	        if (mkdir($dir_dst)==false)
	            throw new Exception('Failed to create dir '.$dir_dst) ;	            
	    }
	    $fn_dst = $dir_dst.'/'.$fn_short;
	    if(!copy($fn_src,$fn_dst)) throw new Exception('Failed to copy '.$fn_src.' to '.$fn_dst);
	    $this->filename_new = $fn_short;
	}
	
	private function createSingleThumb($size,$force) {
	    switch ($size) {
	        case Config::DIR_PICS:
	            $w = 1280;
	            $h = 1280;
	            $q = 85;
	            break;
	        case Config::DIR_TPICS:
	            $w = 480;
	            $h = 480;
	            $q = 80;
	            break;
	        default:
	            throw new Exception('Unrecognized size '.$size);
	    }
	    $dd = substr($this->filename_new, 0,2);
	    $thumbdir = $size.$dd;
	    if(!is_dir($thumbdir)) {
	        if (mkdir($thumbdir)==false)
	            throw new Exception('Failed to create thumb dir '.$thumbdir) ;
	    }
	    $fn_dst = $thumbdir.'/'.$this->filename_new;
	    if(!$force && file_exists($fn_dst)) return;
	    $oPic = new Thumbnail(Config::DIR_PICSFULL.$dd.'/', $this->filename_new);
	    $oPic->resize_image($w, $h, $fn_dst,$q);
	}
	
	public function jsonSerialize() {
	    return [
	        'fn' => $this->filename,
	        'dt' => $this->taken_on,
	        'lt' => $this->geo_lat,
	        'ln' => $this->geo_lon,
	        'e' => $this->event,
	        's' => $this->seq,
	        'l' => $this->location,
	        'p' => $this->people,
	        'i' => $this->id,
	        'cr' => $this->comment,
	        'ce' => $this->commente
	    ];
	}
	
	public function resizeImage($force=FALSE) {
	    $this->createSingleThumb(Config::DIR_PICS, $force);
	    $this->createSingleThumb(Config::DIR_TPICS, $force);
	}
	
	public function saveDB(Photo $defaults) {
	    if($this->event==0) $this->event = $defaults->event;
	    if($this->location==0) $this->location = $defaults->location;
	    if($this->id==0) {
	        //new
	        $dao = PhotoDAO::getInstance();
	        $dao->create($this);
	        $dao->createPeopleLink($this);
	    }
	}
	
	public function updateFromPOST($file_data) {
	    $changed = FALSE;
	    $location = 0;
	    if(isset($file_data['l'])) {
	        $location = intval($file_data['l']);
	    }
	    if($changed = ($this->location != $location)) {
	        $this->location = $location;
	    }
	    
	    
	    $people=[];
	    if(isset($file_data['p'])) {
	        foreach ($file_data['p'] as $pid) {
	            $people[] = intval($pid);
	        }
	        sort($people);
	    }
	    if($people!=$this->people) {
	        $this->people = $people;
	        $changed = true;
	    }
	    
	    if(isset($file_data['s'])) {
	        $seq = intval($file_data['s']);
	        if($seq > 0 && $seq != $this->seq) {
	            $this->seq = $seq;
	            $changed = true;
	        }
	    }
	    
	    if(isset($file_data['dt'])) {
	        if($this->taken_on != $file_data['dt']) {
	            $this->taken_on = $file_data['dt'];
	            $changed = true;
	        }
	    }
	    
	    if(isset($file_data['cr'])) {
	        if($this->comment != $file_data['cr']) {
	            $this->comment = $file_data['cr'];
	            $changed = true;
	        }
	    }
	    
	    if(isset($file_data['ce'])) {
	        if($this->commente != $file_data['ce']) {
	            $this->commente = $file_data['ce'];
	            $changed = true;
	        }
	    }
	    
	    return $changed;
	}
	
}
?>