<?php
namespace photo\Controller;

use photo\DAO\EventDAO;
use photo\Model\Event;
use photo\DAO\AbstractDAO;

final class EventController extends AbstractDAO {
    public static function getInstance()
    {
        if(static::$__instance==null) {
            static::$__instance = new static();
        }
        return static::$__instance;
    }
    
    public function UpdateFromPOST() {
        $o = new Event();
        $o->initFromPOST($_POST);
        if($o->desc_r==NULL) throw new \Exception("Description is required");
        if($o->date_from==NULL) throw new \Exception("Date from is required");
        if($o->date_to==null||$o->date_to=='') $o->date_to = $o->date_from;
        if($o->desc_e == NULL) {
            $o->desc_e = $o->desc_r;
        }
        
        if(isset($POST['i'])) {
            $id = intval($POST['i']);
            $o_b4 = new self($id);
        }
        $o->db_save();
        return $o->id;
    }
    
    public function ReadSingle($id) {
        return EventDAO::getInstance()->findById([$id]);
    }
}
?>