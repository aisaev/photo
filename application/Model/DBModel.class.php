<?php
namespace photo\Model;

abstract class DBModel
{   
    public $id = 0;
    public $updfld = [];
    abstract public function checkVar($n,&$v);
    public function validateAfterEntry() {}
}

