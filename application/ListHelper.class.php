<?php
namespace photo\common;

use photo\DAO\EventDAO;
use photo\DAO\PersonDAO;
spl_autoload_register(function ($name) {
    $fn = str_replace('\\', DIRECTORY_SEPARATOR, $name);
    $fn = str_replace('photo/common/', '', $fn);
    $fn = str_replace('photo/', '', $fn);
    $fn = __DIR__ . DIRECTORY_SEPARATOR . $fn . '.class.php';
    if (file_exists($fn)) {
        include $fn;
    } else {
        return false;
    }
});

class ListHelper
{

    public $data_file = null;

    public $data_uri = null;

    public $proc_uri = null;

    public function __construct($op)
    {
        Config::init();
        $rdir = Config::DIR_PUBLIC;
        
        switch ($op) {
            case 'event':
                $rdir .= 'evt';
                if(!file_exists($rdir)) mkdir($rdir);
                $rdir .= '/'.Config::$lng;
                if(!file_exists($rdir)) mkdir($rdir);
                $this->data_uri = '/evt/' . Config::$lng . '/list.js';
                $this->data_file = __DIR__ . '/../public_html' . $this->data_uri;
                $this->proc_uri = '/js/events.js';
                if (! file_exists($this->data_file)) {
                    $this->writeEventList();
                }
                break;
            case 'person':
                $rdir .= 'ppl';
                if(!file_exists($rdir)) mkdir($rdir);
                $rdir .= '/'.Config::$lng;
                if(!file_exists($rdir)) mkdir($rdir);
                $this->data_uri = '/ppl/' . Config::$lng . '/list.js';
                $this->data_file = __DIR__ . '/../public_html' . $this->data_uri;
                $this->proc_uri = '/js/people.js';
                if (! file_exists($this->data_file)) {
                    $this->writePeopleList();
                }
                break;
        }
    }

    public function writeEventList()
    {
        $dao = EventDAO::getInstance();
        if (! file_exists($this->data_file)) {
            if (file_put_contents($this->data_file, 'var events=' . json_encode($dao->getList(), JSON_UNESCAPED_UNICODE) . ';') === FALSE) {
                throw new \Exception("Event list couldn't be generated");
            }
        }
    }

    public function writePeopleList()
    {
        $dao = PersonDAO::getInstance();
        if (! file_exists($this->data_file)) {
            if (file_put_contents($this->data_file, 'var people=' . json_encode($dao->getList(), JSON_UNESCAPED_UNICODE) . ';') === FALSE) {
                throw new \Exception("People list couldn't be generated");
            }
        }
    }
}

