<?php
namespace photo\Model;

use photo\common\Config;

class Photo extends DBModel implements \JsonSerializable
{
    public $event = 0;
    public $location = 0;
    public $people = [];
    public $taken_on = NULL;
    public $geo_lat = 0;
    public $geo_lon = 0;
    public $seq = 0;
    public $sentimental = false;
    public $hide = false;
    public $comment = NULL;
    public $commente = NULL;
    
    public function checkVar($n, &$v)
    {
        switch ($n) {
            case 'id': case 'p':
                $v=intval($v);
                if($v<0) throw new \Exception("Invalid value for "+$n);
                break;
            case 'cr': case 'ce':
                $v = trim($v);
                $l=strlen($v);
                if($l==0) {
                    $v=null;
                } else if($l > 1024) {
                    throw new \Exception("Comment too long for ".$this->id);
                }
                break;
        }
    }
    
    public function getFileName() {
        $fn_short = '';
        if($this->id < 10) $fn_short = '0000';
        elseif($this->id < 100) $fn_short = '000';
        elseif($this->id < 1000) $fn_short = '00';
        elseif($this->id < 10000) $fn_short = '0';
        $fn_short .= $this->id.'.jpg';
        return $fn_short;
    }
    
    public function jsonSerialize()
    {
        $a = [
            'i'=>$this->id
        ];
        
        if(Config::$__mode!==1) $a['e']=$this->event;
        if(Config::$__mode!==3) $a['l']=$this->location;
        
        if($this->comment !==null) {
            if ($this->commente == null) $this->commente = $this->comment;
        } elseif ($this->commente != null) {
            $this->comment = $this->commente;
        }
        
        if(Config::$__mode==0) {
            if($this->comment!=null) $a['cr'] = $this->comment;
            if($this->commente!=null) $a['ce'] = $this->commente;
        } else {
            if(Config::$lng=='ru') {
                if($this->comment!=null) $a['c'] = $this->comment;
            } else {
                if($this->commente!=null) $a['c'] = $this->commente;
            }
        }
        
        if($this->people!=null) $a['p'] = $this->people;
        if($this->taken_on!=null) {
            $a['t'] = preg_replace('/ 06:00:00$/', '', $this->taken_on);
        }
        if($this->sentimental) $a['s'] = 1;
        return $a;
    }
}

