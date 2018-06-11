<?php 
namespace photo\Controller;

use photo\DAO\AbstractDAO;
use photo\DAO\PhotoDAO;
use photo\Model\Event;
use photo\Model\Person;
use photo\Model\Location;
use photo\Model\Photo;
use photo\common\Config;

class PhotoController extends AbstractDAO {
    protected static $__instance=null;
    
    public function ListForEvent(Event $oe) {
        return PhotoDAO::getInstance()->getListByEvent($oe);
    }
    
    public function ListForLocation(Location $ol,$level) {
        if($level > 10) throw new \Exception("GIS is too deep");
        $a = PhotoDAO::getInstance()->getListByLocation($ol);
        if($ol->children!=null && $ol->allPhotoCnt <= Config::MAX_PHOTO_LOC) {
            foreach ($ol->children as $i=>$oc) {
                array_merge($a,$this->ListForLocation($oc, $level+1));
            }
        }
        if($level == 0) {
            usort($a, function(Photo $a, Photo $b){
                if($a->taken_on == $b->taken_on) return $b->id - $a->id;
                return strtotime($b->taken_on) - strtotime($a->taken_on);
            });
        }
        return $a;
    }
    
    public function ListForPerson(Person $op) {
        return PhotoDAO::getInstance()->getListByPerson($op);
    }
    
    public static function getInstance():self
    {
        if(static::$__instance==null) {
            static::$__instance = new self();
        }
        return static::$__instance;
    }

}
?>