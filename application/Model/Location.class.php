<?php
namespace photo\Model;

use photo\common\Config;

class Location extends DBModel implements \JsonSerializable
{
    const ALLOWED_TYPES = [10,20,30,40,50,60,63,64,65,70,75,76,80,90,99,100];
    const TYPE_WORLD = 0, TYPE_CONTINENT = 10, TYPE_GROUP_OF_COUNTRIES = 20, TYPE_COUNTRY = 30;
    const MAX_TO_SHOW = 200;
    
    public $parent = NULL;
    public $descr_r = NULL;
    public $descr_e = NULL;
    public $type = NULL;
    public $lat = NULL;
    public $lng = NULL;
    public $comment_r = NULL;
    public $comment_e = NULL;
    public $sentimental = false;
    public $children = NULL;
    public $parentList = NULL;
    public $allPhotoCnt = 0, $allPrivatePhotoCnt = 0, $photoCnt = 0, $privatePhotoCnt = 0, $childrenPhotoCnt = 0, $childrenPrivatePhotoCnt = 0;
    
    public function checkVar($n, &$v) {
        switch ($n) {
            case 'id': case 'p':
                $v=intval($v);
                if($v<0) throw new \Exception("Invalid value for "+$n);
                break;
            case 'de': case 'dr':
                $v = trim($v);
                $l=strlen($v);
                if($n == 'dr' && $l==0) {
                    throw new \Exception("Description required for ".$this->id);
                }
                if($l > 80) {
                    throw new \Exception("Description too long for ".$this->id);
                }
                break;
            case 't':
                if(!in_array($v, self::ALLOWED_TYPES)) {
                    throw new \Exception("Location type '.$v.'not allowed");
                }
                break;
        }
    }
    
    public function jsonSerialize()
    {
        if (Config::$__mode == Config::MODE_EDIT) {
            $a = [ 
                'id'=>$this->id, 
                'p'=>$this->parent, 
                'dr'=>$this->descr_r, 
                'de'=>$this->descr_e,
                't'=>$this->type
            ];
            if($this->comment_r!=null) $a['cr']=$this->comment_r;
            if($this->comment_e!=null) $a['ce']=$this->comment_e;
        } elseif (Config::$__mode == Config::MODE_LL) {
            $a = [
                'id'=>$this->id                
            ];
            if($this->id>0) $a['p']=$this->parent;
            if($this->descr_e = $this->descr_r) $a['d'] = $this->descr_r;
            else {
                $a['dr'] = $this->descr_r;
                $a['de'] = $this->descr_e;
            }
        } else {
            $a = [
                'i'=>$this->id
            ];
            if($this->id > 0) $a['p']=$this->parent;
            if(Config::$lng=='ru') $a['d'] = $this->descr_r;
            else $a['d'] = $this->descr_e;
            if($this->sentimental) $a['s'] = 1;
            if($this->children!=NULL) {
                $arrayOfChildren=[];
                foreach ($this->children as $oc) {
                    if($oc->allPhotoCnt > 0) {
                        $child = $oc->jsonSerialize();
                        if($oc->sentimental) $child['s'] = 1;
                        $arrayOfChildren[] = $child;
                    }
                }
                $a['c'] = $arrayOfChildren;
            }
        }
        return $a;        
    }  

    public function validateAfterEntry() {
        if($this->id!=0 && $this->id == $this->parent) {
            throw new \Exception('Location parent is the same as location ID');
        }
        //decode HTML to string
        html_entity_decode($this->descr_r);
        html_entity_decode($this->descr_e);
        if($this->comment_e!=null) html_entity_decode($this->comment_e);
        if($this->comment_r!=null) html_entity_decode($this->comment_r);
    }
}
?>