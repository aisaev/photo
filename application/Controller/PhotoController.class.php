<?php 
namespace photo\Controller;

use photo\DAO\AbstractDAO;
use photo\DAO\PhotoDAO;
use photo\Model\Event;
use photo\Model\Person;
use photo\Model\Location;

class PhotoController extends AbstractDAO {
    protected static $__instance=null;
    
    public function ListForEvent(Event $oe) {
        return PhotoDAO::getInstance()->getListByEvent($oe);
    }
    
    public function ListForLocation(Location $ol) {
        return PhotoDAO::getInstance()->getListByLocation($ol);
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