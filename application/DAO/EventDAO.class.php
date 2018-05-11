<?php
namespace photo\DAO;

use photo\common\DBHelper;

class EventDAO extends AbstractDAO {
    protected static $__instance = null;
    
    static function getInstance():IDAO {
        if(self::$__instance == null) {
            $classname = __NAMESPACE__.'\EventDAO_'.DBHelper::DB_ENGINE;
            self::$__instance = new $classname();
        }
        return self::$__instance;
    }
    
}


?>