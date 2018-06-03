<?php
namespace photo\DAO;

use photo\common\DBHelper;

class EventDAO {
    protected static $__instance = null;
    
    static function getInstance():IDAOEvent {
        if(self::$__instance == null) {
            $classname = __NAMESPACE__.'\EventDAO_'.DBHelper::DB_ENGINE;
            self::$__instance = new $classname();
        }
        return self::$__instance;
    }
    
}


?>