<?php
namespace photo\DAO;

use photo\Model\DBModel;
use photo\common\DBHelper;
use photo\common\ErrorHandler;

abstract class AbstractDAO_pgsql extends AbstractDAO {
    
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
?>