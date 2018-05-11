<?php
namespace photo\common;

abstract class AbstractFactory {
    protected static $__instance = null;    
    
    abstract public static function getInstance();
}


?>