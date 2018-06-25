<?php
namespace photo\DAO;

use photo\Model\DBModel;
use photo\Model\Location;
use photo\common\DBHelper;
use photo\common\Config;

class LocationDAO_pgsql extends AbstractDAO_pgsql implements ILocationDAO {
    
    function __construct() {
        $this->tablename = 'public.tblgis';
        $this->seq_name = 'public.seq_loc';
        $this->map = [ //db,prop,js,sanitize
            ['"Node"','id','id',FILTER_SANITIZE_NUMBER_INT,true],
            ['"Parent"','parent','p',FILTER_SANITIZE_NUMBER_INT],
            ['"DescR"','descr_r','dr',FILTER_SANITIZE_STRING],
            ['"DescE"','descr_e','de',FILTER_SANITIZE_STRING],
            ['"Type"','type','t',FILTER_SANITIZE_NUMBER_INT],
            ['"Latitude"','lat','la',FILTER_SANITIZE_NUMBER_FLOAT],
            ['"Longitude"','lng','lg',FILTER_SANITIZE_NUMBER_FLOAT],
            ['"CommentR"','comment_r','cr',FILTER_SANITIZE_STRING],
            ['"CommentE"','comment_e','ce',FILTER_SANITIZE_STRING],
            ['"PPN"','photoCnt','c',FILTER_SANITIZE_NUMBER_INT],
            ['"PPNSnt"','privatePhotoCnt','cs',FILTER_SANITIZE_NUMBER_INT],
            ['"PPNChild"','childrenPhotoCnt','a',FILTER_SANITIZE_NUMBER_INT],
            ['"PPNChildSnt"','childrenPrivatePhotoCnt','as',FILTER_SANITIZE_NUMBER_INT]
        ];
        $this->keys = ['id'];
        $this->db_keys = ['"Node"'];
        $this->init();        
    }
    
    function getList($listOfPK=NULL): array {
        $a=[];
        $sql = 'SELECT * FROM '.$this->tablename;
        if($listOfPK!=null) {
            $pkList = $listOfPK;
            $hashLoc=[];
            while (count($pkList)>0) {
                $aSubset=$this->getByPKList($pkList,Location::class);
                $pkList=[];
                //1st pass to fill hash
                foreach ($aSubset as $k=>$o) {
                    $a[]=$o;
                    $hashLoc[$o->id] = true;
                }
                //2nd pass to pick parents not in hash
                foreach ($aSubset as $k=>$o) {
                    if($o->parent !== null && $o->parent !== 0 && !isset($hashLoc[$o->parent])) {
                        $pkList[]=$o->parent;
                        $hashLoc[$o->parent] = true;
                    }
                }
            }
        } else {
            $pdo = DBHelper::getPDO();
            foreach ($pdo->query($sql) as $rec) {
                $o = new Location();
                $this->fillFromDB($o,$rec);
                $a[] = $o;
            }
        }
        return $a;
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
    public function refreshPPN()
    {
        $pdo = DBHelper::getPDO();
        $pdo->exec('SELECT public."updateLocPhotoCount"()');
        $node = [];
        $leaf = [];
        foreach($pdo->query('SELECT "Node", "Parent","PPN","PPNSnt" FROM '.$this->tablename) as $rec) {
            $node[$rec['Node']] = [$rec['Parent'],$rec['PPN'],$rec['PPNSnt'],0,0];
            if($rec['PPN']>0) {
                $leaf[$rec['Node']] = $rec['Node'];
            }
        }
        
        foreach($leaf as $k=>$id) {
            $el = $node[$id];
            $pid = $el[0];
            $used=[];
            while($pid!=0) {
                if(!isset($used[$pid])) $used[$pid]=true;
                else throw new \Exception("Circular reference for node $k");
                $el_p = &$node[$pid];
                $el_p[3]+=$el[1];
                $el_p[4]+=$el[2];
                $pid = $el_p[0];
            }
        }
        
        $ps = $pdo->prepare('UPDATE '.$this->tablename.
            ' SET "PPNChild" = ?, "PPNChildSnt" = ? WHERE "Node" = ?');
        foreach($node as $k=>$el){
            $ps->execute([$el[3],$el[4],$k]);
        }
    }
    
    public function fillFromDB(&$o, $rec) {
        parent::fillFromDB($o, $rec);
        $o->allPhotoCnt = $o->photoCnt + $o->childrenPhotoCnt;
        $o->allPrivatePhotoCnt = $o->privatePhotoCnt + $o->childrenPrivatePhotoCnt;
        
    }
    
    public function findById(array $pk): DBModel {
        $o = new Location();
        $pdo = DBHelper::getPDO();
        $id=$pk[0];
        if($id>0) {
            $sql = 'SELECT * FROM '.$this->tablename.' WHERE '.$this->db_keys[0].' = ?';
            $sth = $pdo->prepare($sql);
            $sth->execute($pk);
            $rec = $sth->fetch(\PDO::FETCH_ASSOC);
            $this->fillFromDB($o,$rec);
            $o->sentimental = ($o->allPhotoCnt <= $o->allPrivatePhotoCnt);
        } else {
            $o->descr_e = 'World';
            $o->descr_r = 'Мир';
            $o->type = Location::TYPE_WORLD;
        }
        
        return $o;
    }
    
    public function readChildren(Location &$o) {
        //read nearest children
        $pdo = DBHelper::getPDO();
        $sql = 'SELECT * FROM '.$this->tablename.' WHERE "Parent" = ?';
        $sth = $pdo->prepare($sql);
        $sth->execute([$o->id]);
        foreach ($sth->fetchAll() as $rec) {
            $oc = new Location();
            $this->fillFromDB($oc, $rec);
            if($o->children==NULL) $o->children=[];
            $o->children[] = $oc;
            if($o->id==0) {
                //world: virtual counts
                $o->allPrivatePhotoCnt+=$oc->allPrivatePhotoCnt+$oc->photoCnt;
                $o->allPhotoCnt+=$oc->allPhotoCnt+$oc->privatePhotoCnt;
            }
        }
        if($o->allPhotoCnt<Config::MAX_PHOTO_LOC && $o->children!=NULL) {
            foreach ($o->children as $i=>$oc) {
                $this->readChildren($oc);
            }
        }
    }
    
    public function readParents(Location &$o) {
        if($o->id==0) return;
        //read nearest children
        $pdo = DBHelper::getPDO();
        $id=$o->parent;
        while($id>=0) {
            if($o->parentList==NULL) $o->parentList=[];
            $op = $this->findById([$id]);
            $o->parentList[] = $op;
            if($id==0) break;
            $id = $op->parent;
        }
    }
    
}
?>