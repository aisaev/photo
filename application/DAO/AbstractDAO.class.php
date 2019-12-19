<?php
namespace photo\DAO;

use photo\Model\DBModel;
 
abstract class AbstractDAO implements IDAO {
    const MAX_CNT = 100;
    protected $tablename = null;
    protected $seq_name = null;
    protected $map = null;
    protected $maplen = 0;
    protected $idx_by_prop = null;
    protected $db_keys=null;
    protected $keys=null;
    protected static $__instance = null;    
    
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
    //public function findById(array $pk): DBModel {}
    public function getList($listOfPK=null): array { return []; }
    public function fillFromDB(&$o,$rec) {
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
    
    public function init() {
        $this->maplen = count($this->map);
        $this->idx_by_prop = [];
        for($i=0;$i<$this->maplen;$i++) {
            $a = $this->map[$i];
            $this->idx_by_prop[$a[1]] = $a;
        }        
    }
    
}

