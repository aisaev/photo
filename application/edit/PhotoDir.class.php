<?php
namespace photo\edit;

require_once 'PhotoFile.class.php';
require_once 'Thumbnail.class.php';
use photo\Model\Photo;
use \Exception;
use photo\common\DBHelper;
use photo\DAO\EventDAO;

final class PhotoDir implements \JsonSerializable {
    public $parent_dir = NULL;
	public $dir = NULL;
	public $files = [];
	public $subdir = [];
	
	public $defaults = NULL;
	
	public function __construct($parent_dir,$dir_name) {
	    $this->parent_dir = $parent_dir;
	    $this->dir = $dir_name;
	}
	
	public function collect($locked=false) {
	    //read dir defaults
	    $path = $this->parent_dir.$this->dir.'/';
	    $dir_json = $path.'dir.json';
	    $files_json = $path.'files.json';
	    if(file_exists($dir_json)) {
	        $this->defaults = unserialize(file_get_contents($dir_json));
	    }

	    $l_d = dir($path);
	    if($l_d == NULL || $l_d ===FALSE) return FALSE;
	        
	    $filesJSON = NULL;
	    $filesCache = [];
	    $rebuild = false;
	    if(file_exists($files_json)) {
	        //read file meta-data
	        $filesJSON = unserialize(file_get_contents($files_json));
	    } elseif($locked) {
	        throw new \Exception('Collection for locked dir is not allowed');
	    } else {
	        $rebuild = true;
	    }

        if($filesJSON != NULL) {
            //prepare associative array to get data from if file exists
            foreach ($filesJSON['files'] as $oFile) {
                $filesCache[$oFile->filename] = $oFile;
            }
        }
	        
        while (false !== ($l_f = $l_d->read())) {
            if($l_f == '.' || $l_f == '..' || $l_f == '.thumb') continue;
            if(!is_dir($path.$l_f)) {
                $pp = pathinfo($l_f);
                $l_ext = '';
                if(isset($pp['extension'])) {
                    $l_ext = strtoupper($pp['extension']);
                    if($l_ext == 'JPG') {
                        if(isset($filesCache[$l_f])) {
                            $oFile = $filesCache[$l_f];
                        } else {
                            $oFile = new PhotoFile($path,$l_f);
                        }
                        //prepare thumbnails, if needed. They will be 250x250 and 800x800 under .thumb/250 and .thumb/800
                        //$oThumb = new Thumbnail($path, $l_f);
                        //$oThumb->process(isset($_GET['fix']) && (substr($l_f, 0,1)=='P'));
                        $this->files[] = $oFile;
                    }
                    $l_ext = 'jpg';
                }
            } else {
                $oDir = new self($path,$l_f);
                $oDir->collect();
                $this->subdir[] = $oDir;
            }
        }
        //sort files and subdirs
        usort($this->files,function(PhotoFile $a,PhotoFile $b){
            return ($a->taken_on < $b->taken_on)?
                -1:
                ($a->taken_on > $b->taken_on?
                    1:
                    ($a->filename < $b->filename?
                        -1:
                        1));
        });

        if(!empty($this->files) && $rebuild) {
            $json = serialize(['files' => $this->files]);
            $saveResult = file_put_contents($files_json, $json);
        }
        usort($this->subdir,function(PhotoDir $a,PhotoDir $b){ return strcmp($a->dir,$b->dir); });
	}
	
	private function copyResized(PhotoFile $o) {
	    $o->copyToArchive($this->parent_dir.$this->dir.'/');
	    $o->resizeImage();
	}
	
	public function jsonSerialize() {
		return [
				'd' => $this->dir,
				'f' => $this->files,
				's' => $this->subdir,
				'e' => ($this->defaults!=NULL?$this->defaults->event:0),
				'l' => ($this->defaults!=NULL?$this->defaults->location:0),
				'p' => ($this->defaults!=NULL?$this->defaults->people:[])
		];
	}
	
	public function saveDB():array {
	    $this->validate();
	    $is_first = TRUE;
	    $cur_path = $this->parent_dir.$this->dir;
	    try {
	        $event_dao = EventDAO::getInstance();
	        $minseq = $event_dao->getMinSeqNum($this->defaults->event);
	        foreach ($this->files as $oFile) {
	            if($is_first) {
	                $pdo = DBHelper::getPDO();
	                if($pdo->beginTransaction()==FALSE) throw new Exception("Failed to start transaction");
	                $is_first = false;
	            }
	            $oFile->seq+=$minseq;
	            $oFile->saveDB($this->defaults);
	        }
	        if(!$is_first) {
	            if($pdo->commit()==FALSE) throw new Exception("Failed to commit");
	            else {
	                $json = serialize(['files' => $this->files]);
	                $saveResult = file_put_contents($cur_path.'/files.json', $json);	                
	            }
	        }	        
	    } catch (Exception $e) {
	        if($is_first) throw $e;
	        if($pdo->rollBack()==FALSE) throw new Exception("Failed to roll back");
	    }
	    return $this->files;
	}
	
	public function copyToProductionSingle($id):bool {
	    //resize
	    foreach ($this->files as $oFile) {
	        if($oFile->id != $id) continue;
	        $fnf = $this->parent_dir.$this->dir.'/'.$oFile->filename;
	        if(file_exists($fnf)) {
	            $this->copyResized($oFile);
	            unlink($fnf);
	            return true;
	        }	        
	    }
	    return false;
	}
	
	public function cleanUp() {
	    //if we're here, no exceptions occured, can remove from unprocessed
	    //array_map('unlink', glob($cur_path.'/*'));
	    exec('rm -R '.$this->parent_dir.$this->dir);	    
	}
	
	public function updateFromPOST($defaults) {
	    $file_idx=[];
	    $core = NULL;
	    if(isset($defaults[$this->dir])) {
	        $data = $defaults[$this->dir];
	        foreach ($data['f'] as $f) {
	            $file_idx[$f['f']]=$f;
	        }
	        if($data['e'] != '0') {
	            $core = new Photo();
	            $core->event = intval($data['e']);
	        }
	        if($data['l'] != '0') {
	            if($core==NULL) $core = new Photo();
	            $core->location = intval($data['l']);
	        }
	        foreach ($data['p'] as $ps) {
	            if($core==NULL) $core = new Photo();
	            $core->people[] = intval($ps);
	        }
	    }
	    $path = $this->parent_dir.($this->dir==''?'':$this->dir.'/');
	    
	    if($core != $this->defaults) {
	        $this->defaults = $core;
	        $filedata = serialize($this->defaults);
	        $fname = $path.'dir.json';
	        if(file_put_contents($fname, $filedata)===false)
	            throw new Exception('Failed to create '.$fname);
	    }
	    
	    $seq = 1;
	    $changed=FALSE;
	    foreach ($this->files as $oFile) {
	        $oFile->seq = $seq++;
	        if(isset($file_idx[$oFile->filename])) {
	            $changed = ($oFile->updateFromPOST($file_idx[$oFile->filename]) || $changed);
	        }
	    }
	    if($changed) {
	        $json = serialize(['files' => $this->files]);
	        if(file_put_contents($path.'files.json', $json)===false)
	            throw new Exception('Failed to create '.$path.'files.json');
	    }
	    
	    foreach ($this->subdir as $child_dir) {
	        $child_dir->updateFromPost($defaults);
	    }
	    return true;
	}
	
	private function validate() {
	    if($this->defaults->event==0) throw new Exception('Event required');
	    if($this->defaults->location==0) throw new Exception('Event required');
	}
	
}
?>