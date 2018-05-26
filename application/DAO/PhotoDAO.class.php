<?php
namespace photo\DAO;

require_once 'AbstractDAO.class.php';
require_once 'IPhotoDAO.class.php';

use photo\Model\Event;
use photo\Model\Location;
use photo\Model\Person;
use photo\Model\Photo;
use photo\common\DBHelper;

class PhotoDAO extends AbstractDAO implements IPhotoDAO {
    protected static $__instance = null;
    
    static function getInstance():IPhotoDAO {
        if(self::$__instance == null) {
            $classname = __NAMESPACE__.'\PhotoDAO_'.DBHelper::DB_ENGINE;
            self::$__instance = new $classname();
        }
        return self::$__instance;
    }

    public function getListByEvent(Event $oe): array {
        return [];
    }
    public function getListByLocation(Location $loc): array {
        return [];        
    }
    public function getListByPerson(Person $psn): array {
        return [];
    }
    
    public function createPeopleLink(Photo &$o) { }
}


