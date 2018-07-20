<?php
namespace photo\DAO;
use photo\Model\Event;
use photo\Model\Location;
use photo\Model\Person;
use photo\Model\Photo;
use photo\common\Config;
use photo\common\DBHelper;

class PhotoDAO_pgsql extends AbstractDAO_pgsql implements IPhotoDAO {
    
    function __construct() {
        $this->tablename = 'public.photos';
        $this->seq_name = 'public.seq_photo';
        $this->map = [ //db,prop,js,sanitize
            ['photoid','id','i',FILTER_SANITIZE_NUMBER_INT,true],
            ['event','event','e',FILTER_SANITIZE_NUMBER_INT],
            ['seqnum','seq','s',FILTER_SANITIZE_NUMBER_INT],
            ['hide','hide','h',FILTER_SANITIZE_STRING],
            ['sent','sentimental','sn',FILTER_SANITIZE_STRING],
            ['comment','comment','cr',FILTER_SANITIZE_STRING],
            ['commente','commente','ce',FILTER_SANITIZE_STRING],
            ['"Node"','location','l',FILTER_SANITIZE_NUMBER_INT],
            ['"Latitude"','geo_lat','lt',FILTER_SANITIZE_NUMBER_FLOAT],
            ['"Longitude"','geo_lon','ln',FILTER_SANITIZE_NUMBER_FLOAT],
            ['taken_on','taken_on','dt',FILTER_SANITIZE_STRING]
        ];
        $this->keys = ['id'];
        $this->db_keys = ['photoid'];
        $this->init();        
    }
    
    public function createPeopleLink(Photo &$o) {
        if(empty($o->people)) return;
        $pdo = DBHelper::getPDO();
        $sql = "INSERT INTO public.ppl2photo (photoid,pplid) VALUES(?,?)";
        $stmt = $pdo->prepare($sql);
        foreach ($o->people as $p2p) {
            if(!$stmt->execute([$o->id,$p2p])) throw new \Exception("Failed to link person ID ".$p2p." to photo ".$o->id);
        }
    }
    
    public function getList($listOfPK=null): array {
        $pdo = DBHelper::getPDO();
        $a = [];
        $sql = 'SELECT * FROM '.$this->tablename.' ORDER BY '.$this->db_keys[0];
        foreach ($pdo->query($sql) as $rec) {
            $o = new Photo();
            $this->fillFromDB($o, $rec);
            $a[] = $o;
        }
        return $a;
    }
    
    public function getListByEvent(Event $evt):array {
        //list of photos for event, used in event page
        //prefill people on photos
        $sql = 'SELECT p.photoid, pplid FROM photos p JOIN ppl2photo p2p ON p2p.photoid = p.photoid '.
            'WHERE event = ?';
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute([$evt->id]);
        $p2p = [];
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if(!isset($p2p['photoid'])) $p2p['photoid']=[];
            $p2p[$rec['photoid']][]=intval($rec['pplid']);
        }
        $sql = 'SELECT * FROM photos WHERE event = ? ORDER BY seqnum, photoid';
        $sth = $pdo->prepare($sql);
        $sth->execute([$evt->id]);
        $a=[];
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $o = new Photo();
            $this->fillFromDB($o, $rec);
            if($evt->date_from==$evt->date_to && $o->taken_on==$evt->date_from) $o->taken_on = null;
            if(isset($p2p[$rec['photoid']])) $o->people=$p2p[$rec['photoid']];
            else $o->people = null;
            $a[] = $o;
        }
        return $a;
    }
    
    public function getListByPerson(Person $psn): array {
        //list of photos for person, used in person page
        //prefill people on photos
        $a=[];
        $sql = 'SELECT photoid, pplid FROM ppl2photo '.
            'WHERE photoid IN (SELECT DISTINCT photoid FROM ppl2photo WHERE pplid = ?)';
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute([$psn->id]);
        $p2p = [];
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if(!isset($p2p['photoid'])) $p2p['photoid']=[];
            $p2p[$rec['photoid']][]=intval($rec['pplid']);
        }
        $sql = 'SELECT p.* FROM photos p JOIN ppl2photo p2p ON p2p.photoid = p.photoid '.
            'JOIN events e ON e.evntid = p.event '.
            'WHERE p2p.pplid= ? ORDER BY p.taken_on DESC, e.date_from DESC, e.date_to DESC, e.evntid DESC, p.seqnum DESC';
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute([$psn->id]);
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $o = new Photo();
            $this->fillFromDB($o, $rec);
            if(isset($p2p[$rec['photoid']])) $o->people=$p2p[$rec['photoid']];
            else $o->people = null;
            $a[] = $o;
        }
        return $a;
    }
    
    public function getListByLocation(Location $loc):array {
        $a=[];
        //if less than 300 photos in the subtree or leaf node - retrieve all photos
        //recursive call, to make things easuer, we will get event data and plug date from-to and seq into the photo itself
        if($loc->allPhotoCnt <= Config::MAX_PHOTO_LOC && $loc->children != null) {
            foreach ($loc->children as $i=>$oc) {
                $a = array_merge($a,$this->getListByLocation($oc));        
            }
        }
        //list of photos for location, used in location page
        //prefill people on photos        
        $sql = 'SELECT p.photoid, pplid FROM photos p JOIN ppl2photo p2p ON p2p.photoid = p.photoid '.
            'WHERE "Node" = ?';
        $pdo = DBHelper::getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute([$loc->id]);
        $p2p = [];
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if(!isset($p2p['photoid'])) $p2p['photoid']=[];
            $p2p[$rec['photoid']][]=intval($rec['pplid']);
        }
        $sql = 'SELECT * FROM photos WHERE "Node" = ?';
        $sth = $pdo->prepare($sql);
        $sth->execute([$loc->id]);
        while($rec = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $o = new Photo();
            $this->fillFromDB($o, $rec);
            if(isset($p2p[$rec['photoid']])) $o->people=$p2p[$rec['photoid']];
            else $o->people = null;
            $a[] = $o;
        }
        //ORDER BY taken_on DESC, event DESC, seqnum DESC, photoid DESC
        return $a;
    }
    
    public static function getInstance()
    {
        if(static::$__instance==null) {
            static::$__instance = new self();
        }
        return static::$__instance;
    }
    
}
?>