<?php
namespace photo\DAO;

require_once 'AbstractDAO.class.php';
require_once 'ILocationDAO.class.php';

use photo\common\DBHelper;

abstract class LocationDAO extends AbstractDAO {
    protected static $__instance = null;
    
    static function getInstance():ILocationDAO {
        if(self::$__instance == null) {
            $classname = __NAMESPACE__.'\LocationDAO_'.DBHelper::DB_ENGINE;
            self::$__instance = new $classname();
        }
        return self::$__instance;
    }
    
}

?>