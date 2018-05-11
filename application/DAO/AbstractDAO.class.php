<?php
namespace photo\DAO;

require_once __DIR__.'/../core.php';

use photo\Model\DBModel;
use photo\common\AbstractFactory;
use photo\common\DBHelper;
use photo\common\ErrorHandler;

interface IDAO {
    function create(DBModel &$o): bool;
    function update(DBModel $o): bool;
    function delete(DBModel $o): bool;
    function save(DBModel &$o): bool;
    function findById(array $pk): DBModel;
    function getList($listOfPK=null): array;
    function initFromPOST($entry, DBModel &$o);
}

abstract class AbstractDAO extends AbstractFactory implements IDAO {
    const MAX_CNT = 100;
    protected $tablename = null;
    protected $seq_name = null;
    protected $map = null;
    public function create(DBModel &$o): bool { return false; }
    public function update(DBModel $o): bool { return false; }
    public function save(DBModel &$o): bool
    {
        if($o==null) throw new \Exception('Object instance is expected');
        if($o->id==0) {
            return $this->create($o);
        } else {
            return $this->update($o);
        }
    }
        public function delete(DBModel $o): bool { return false; }
    public function findById(array $pk): DBModel {}
    public function getList($listOfPK=null): array { return []; }
    
    public function fillFromDB($o,$rec) {
        $len = count($this->map);
        
        for ($i = 0,$j=1; $i < $len; $i++) {
            $dbfld = trim($this->map[$i][0],'"');
            $prop = $this->map[$i][1];
            if(is_bool($o->$prop)) {
                $o->$prop = ($rec[$dbfld]=='t');
            } else {
                if($this->map[$i][3]==FILTER_SANITIZE_NUMBER_INT) {
                    $o->$prop = intval($rec[$dbfld]);
                } else {
                    $o->$prop = $rec[$dbfld];
                }
            }
        }
    }
    
    public function initFromPOST($entry, DBModel &$o){
        $len = count($this->map);
        for ($i = 0; $i < $len; $i++) {
            $f=$this->map[$i];
            if(!isset($entry[$f[2]])) continue;
            if($f[3]!==NULL) {
                $v = filter_var($entry[$f[2]],$f[3]);
            } else {
                $v = $entry[$f[2]];
            }
            if($v===FALSE) throw new \Exception("Invalid value in ".$f[2]);
            $o->checkVar($f[2], $v);
            $prop = $f[1];
            if ($o->$prop != $v) {
                $o->$prop = $v;
                $o->updfld[$prop]=true;
            }
        }
        $o->validateAfterEntry();        
    }
    
}

abstract class DAO_pgsql extends AbstractDAO {
    
    public function create(DBModel &$o): bool
    {
        $fld = '';
        $ph = '';
        $param = [];
        $len = count($this->map);
        $pdo = DBHelper::getPDO();
        
        if($o->id==0) {
            $stmt = $pdo->query( "SELECT nextval('".$this->seq_name."')" ,\PDO::FETCH_NUM);
            if($stmt==FALSE) {
                throw new \Exception("Failed to get new ID",ErrorHandler::PDO_GET_NEXT_ID);
            }
            $rec = $stmt->fetch();
            $o->id = intval($rec[0]);
        }
        
        for ($i = 0; $i < $len; $i++) {
            $prop = $this->map[$i][1];
            if($o->$prop!==NULL) {
                $param[] = is_bool($o->$prop)?($o->$prop?'true':'false'):$o->$prop;
                if($fld!='') {
                    $fld.=',';
                    $ph.=',';
                }
                $fld .= $this->map[$i][0];
                $ph .= '?';
            }
        }
        $sql = "INSERT INTO ".$this->tablename." (".$fld.') VALUES('.$ph.');';
        $stmt = DBHelper::getPDO()->prepare($sql);
        if(!$stmt->execute($param)) {
            throw new \Exception('Failed to create new record');
        }
        return $o->id;
    }
    
    public function update(DBModel $o): bool
    {}
    
    public function delete(DBModel $o): bool
    {
        if ($o==null) {
            return true;
        }
    }
    
    public function getNewId():int {
        
        $result = pg_query ( "SELECT nextval('".static::$seq_name."');" );
        if($result==FALSE) {
            throw new \Exception(pg_last_error());
        }
        $rec = pg_fetch_row( $result );
        if($rec==FALSE) {
            throw new \Exception(pg_last_error());
        }
        $this->id = intval($rec[0]);
    }
    
    public function getByPKList($listOfIDs,$classname) {
        $a=[];
        $cnt = 0;
        $pklist='(';
        foreach ($listOfIDs as $k=>$v) {
            $pklist=$pklist.($cnt==0?'':',').$v;
            $cnt++;
            if($cnt>=static::MAX_CNT) {
                $this->getBySubset($pklist.')', $a,$classname);
                $pklist='(';
                $cnt=0;
            }
        }
        if($cnt>0) {
            $this->getBySubset($pklist.')', $a,$classname);
        }
        return $a;
    }

    private function getBySubset($pklist,&$a,$classname) {
        $sql = "SELECT * FROM ".$this->tablename." WHERE ".$this->db_keys[0]." IN $pklist;";
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $o=new $classname();
            $this->fillFromDB($o,$rec);
            $a[]=$o;
        }
    }
}

