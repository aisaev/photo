<?php
namespace photo\DAO;

use photo\common\DBHelper;

class PersonDAO extends AbstractDAO {
    static protected $__instance = null;
    
    static function getInstance():IPersonDAO {
        if(self::$__instance == null) {
            $classname = __NAMESPACE__.'\PersonDAO_'.DBHelper::DB_ENGINE;
            self::$__instance = new $classname();
        }
        return self::$__instance;
    }
    
}

?>