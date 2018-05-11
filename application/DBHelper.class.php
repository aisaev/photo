<?php
namespace photo\common;

require_once 'ErrorHandler.class.php';

class DBConfig
{   
    const CFG_FILE = '/usr/local/wwwcfg/photo.cfg';
    const DB_NAME = 'DB_NAME';
    const DB_PASS = 'DB_PASS';
    const DB_USER = 'DB_USER';
    
    private static $__instance = NULL;
    
    public $db_name = '';
    public $db_user = '';
    public $db_pass = '';
    
    private function __construct() {
        $ini_array = parse_ini_file(self::CFG_FILE);
        if(!isset($ini_array[self::DB_NAME])) throw new \Exception('No DB name'); 
        if(!isset($ini_array[self::DB_USER])) throw new \Exception('No DB user');
        if(!isset($ini_array[self::DB_PASS])) throw new \Exception('No DB password');
        $this->db_name = $ini_array[self::DB_NAME];
        $this->db_user = $ini_array[self::DB_USER];
        $this->db_pass = $ini_array[self::DB_PASS];
    }
    
    static public function getInstance(): DBConfig {
        if (self::$__instance==NULL) {
            self::$__instance = new self();
        }
        return self::$__instance;
    }
}

class DBHelper
{
    static private $pdo = null;
    const DB_ENGINE = 'pgsql';
    
    static public function getPDO() {
        if (self::$pdo==null) {
            $db_host = 'localhost';
            $dbCfg = DBConfig::getInstance();
            try {
                $dsn = self::DB_ENGINE.':host='.$db_host.';dbname='.$dbCfg->db_name;
                $pdo = new \PDO($dsn,$dbCfg->db_user,$dbCfg->db_pass);
                self::$pdo = $pdo;
                self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new \Exception('Failed to connect to DB', ErrorHandler::PDO_INIT_FAILURE, $e);
            }
        }
        return self::$pdo;
    }
}
