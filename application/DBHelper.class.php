<?php
namespace photo\common;

require_once 'ErrorHandler.class.php';


class DBHelper
{
    static private $pdo = null;
    const DB_ENGINE = 'pgsql';
    
    static public function getPDO() {
        if (self::$pdo==null) {
            $db_host = 'localhost';
            $db_name = '';
            $db_user = '';
            $db_pass = '';
            try {
                $dsn = self::DB_ENGINE.':host='.$db_host.';dbname='.$db_name;
                $pdo = new \PDO($dsn,$db_user,$db_pass);
                self::$pdo = $pdo;
                self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new \Exception('Failed to connect to DB', ErrorHandler::PDO_INIT_FAILURE, $e);
            }
        }
        return self::$pdo;
    }
}
