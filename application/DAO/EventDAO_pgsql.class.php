<?php
namespace photo\DAO;

use photo\Model\DBModel;
use photo\Model\Event;
use photo\common\DBHelper;

class EventDAO_pgsql extends AbstractDAO_pgsql implements IDAOEvent {
    
    function __construct() {
        $this->tablename = 'public.events';
        $this->seq_name = 'public.seq_event';
        $this->keys = ['id'];
        $this->db_keys = ['evntid'];
        $this->map = [ //db,prop,js,sanitize
            ['evntid','id','i',FILTER_SANITIZE_NUMBER_INT,true],
            ['date_from','date_from','f',FILTER_SANITIZE_STRING],
            ['date_to','date_to','t',FILTER_SANITIZE_STRING],
            ['hide','hide','h',FILTER_SANITIZE_STRING],
            ['sent','sentimental','s',FILTER_SANITIZE_STRING],
            ['event','desc_r','r',null],
            ['evente','desc_e','e',null],
            ['addon','added_on','a',null]
        ];
        $this->init();        
    }
    
    function getList($listOfPK=NULL): array {
        if($listOfPK!=null) {
            $a=$this->getByPKList($listOfPK,Event::class);
        } else {
            $sql = 'SELECT * FROM '.$this->tablename.
            ' WHERE hide = false ORDER BY date_from DESC, date_to DESC, evntid DESC';
            $pdo = DBHelper::getPDO();
            $a=[];
            foreach ($pdo->query($sql) as $rec) {
                $o = new Event();
                $this->fillFromDB($o, $rec);
                if($o->added_on != NULL) $o->added_on = new \DateTime($o->added_on);
                $a[] = $o;
            }
        }
        return $a;
    }
    
    public function findById(array $pk): DBModel {
        $sql = 'SELECT * FROM '.$this->tablename.' WHERE '.$this->db_keys[0].' = ?';
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute($pk);
        $rec = $sth->fetch(\PDO::FETCH_ASSOC);
        $o = new Event();
        $this->fillFromDB($o,$rec);
        return $o;
    }
    
    public function getMinSeqNum($id): int {
        $sql = "SELECT MAX(seqnum) FROM public.photos WHERE event = ?";
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        if(!$sth->execute([$id])) return 0;
        $rec = $sth->fetch(\PDO::FETCH_COLUMN);
        if($rec==false) return 0;    
        return $rec;
    }
    
    public static function getInstance()
    {
        {
            if(static::$__instance==null) {
                static::$__instance = new static();
            }
            return static::$__instance;
        }
    }
    
}
?>