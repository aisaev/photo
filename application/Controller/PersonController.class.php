<?php
namespace photo\Controller;

use photo\DAO\AbstractDAO;
use photo\DAO\PersonDAO;
use photo\Model\Person;

final class PersonController extends AbstractDAO {
    public static function getInstance()
    {
        if(static::$__instance==null) {
            static::$__instance = new static();
        }
        return static::$__instance;
    }
    
    public function UpdateFromPOST() {
        $o = new Person();
        $o->initFromPOST($_POST);
        if($o->name_r==NULL) throw new \Exception("Native name is required");
        if($o->name_e == NULL) {
            $o->name_e = $o->name_r;
        }
        
        if(isset($POST['i'])) {
            $id = intval($POST['i']);
            $o_b4 = new self($id);
        }
        $o->db_save();
        return $o->id;
    }
    
    public function ReadSingle($id) {
        return PersonDAO::getInstance()->findById([$id]);
    }
}
?>