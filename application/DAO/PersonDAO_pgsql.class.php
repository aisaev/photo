<?php
namespace photo\DAO;

use photo\Model\DBModel;
use photo\Model\Person;
use photo\common\Config;
use photo\common\DBHelper;

class PersonDAO_pgsql extends AbstractDAO_pgsql {
    
    function __construct() {
        $this->tablename = 'public.people';
        $this->seq_name = 'public.seq_person';
        $this->map = [ //db,prop,js,sanitize
            ['pplid','id','id',FILTER_SANITIZE_NUMBER_INT,true],
            ['person','name_r','n',FILTER_SANITIZE_STRING],
            ['persone','name_e','ne',FILTER_SANITIZE_STRING],
            ['aka','aka_r','a',FILTER_SANITIZE_STRING],
            ['akae','aka_e','ae',FILTER_SANITIZE_STRING],
            ['cntry','country_of_origin','c',FILTER_SANITIZE_STRING],
            ['sent','sentimental','s',FILTER_SANITIZE_NUMBER_INT],
            ['ppn','photo_count','p',FILTER_SANITIZE_NUMBER_INT]
        ];
        $this->keys = ['id'];
        $this->db_keys = ['pplid'];
    }
    
    function getList($listOfPK=NULL): array {
        if($listOfPK!=null) {
            $a=$this->getByPKList($listOfPK,Person::class);
        } else {
            $sql = 'SELECT * FROM '.$this->tablename.
            ' ORDER BY person'.(Config::$lng==Config::LNG_EN?'e':'').', pplid';
            $pdo = DBHelper::getPDO();
            $a=[];
            foreach ($pdo->query($sql) as $rec) {
                $o = new Person();
                $this->fillFromDB($o,$rec);
                $a[] = $o;
            }
        }
        return $a;
    }
    
    public static function getInstance():IDAO
    {
        if(self::$__instance==null) {
            self::$__instance = new self();
        }
        return self::$__instance;
    }
    
    public function findById(array $pk): DBModel {
        $sql = 'SELECT * FROM '.$this->tablename.' WHERE '.$this->db_keys[0].' = ?';
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute($pk);
        $rec = $sth->fetch(\PDO::FETCH_ASSOC);
        $o = new Person();
        $this->fillFromDB($o,$rec);
        return $o;
    }
    
    
}
?>