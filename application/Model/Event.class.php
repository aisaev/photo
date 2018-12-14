<?php
namespace photo\Model;

use photo\common\Config;

final class Event extends DBModel implements \JsonSerializable
{
    public $date_from = NULL;
    public $date_to = NULL;
    public $desc_r = NULL;
    public $desc_e = NULL;
    public $hide = false;
    public $sentimental = false;
    public $added_on = NULL;
    
    public function checkVar($n, &$v)
    {
        switch ($n) {            
            case 'i':
                $v = intval($v);
                break;
            case 'r': case 'e';
                $v = trim($v);
                if(mb_strlen($v) > 250) throw new \Exception('Event description too long');
                break;
            case 's':
                $v=($v=='true' || $v=='1');
                break;
            case 'f': case 't':
                if($v!='') {
                    $d = \DateTime::createFromFormat('Y-m-d', $v);
                    if($d==FALSE) {
                        throw new \Exception('Invalid date format');
                    }
                }
        }
    }

    public function jsonSerialize()
    {
        $a = [
            'i'=>$this->id,
            'f'=>$this->date_from
        ];
        if($this->date_to>$this->date_from) $a['t'] = $this->date_to;
        if(Config::$__mode==Config::MODE_EDIT) {
            if($this->desc_r!==null) $a['r'] = $this->desc_r;
            if($this->desc_e!==null) $a['e'] = $this->desc_e;
        } else {
            if(Config::$lng==Config::LNG_RU) $a['d'] = $this->desc_r;
            else $a['d'] = $this->desc_e;
            
            if ($this->added_on <> null) {
                $dtNow = new \DateTime("now");
                $dtDiff = $dtNow->diff($this->added_on);
                if ($dtDiff->days < 15) {
                    $a['n'] = '1';                    
                }
            }
        }
        if($this->sentimental) $a['s'] = 1;
        return $a;
    }
    
    public function validateAfterEntry() {
        if($this->date_from==NULL) throw new \Exception('Date from is required');
        if($this->date_to==NULL) $this->date_to = $this->date_from;
        if($this->desc_r==NULL) {
            if($this->desc_e==NULL) throw new \Exception('Description is required');
            else $this->desc_r = $this->desc_e;
        } else if($this->desc_e==NULL) {
            $this->desc_e = $this->desc_r;
        }
        if(strtotime($this->date_to) < strtotime($this->date_from)) {
            throw new \Exception('From date must be before or the same as to date');
        }
    }
}  
    