<?php
namespace photo\Model;

use photo\common\Config;

final class Person extends DBModel implements \JsonSerializable
{

    public $name_r = NULL;

    public $name_e = NULL;

    public $aka_r = NULL;

    public $aka_e = NULL;

    public $country_of_origin = NULL;
    
    public $sentimental = false;
    public $photo_count = 0;

    public function checkVar($n, &$v)
    {
        switch ($n) {
            case 'id':
                $v = intval($v);
                if ($v < 0)
                    throw new \Exception("Invalid value for " . $n);
                break;
            case 'n':
            case 'ne':
                $v = trim($v);
                $l = strlen($v);
                if ($l == 0) {
                    throw new \Exception("Description required for " . $this->id);
                }
                if ($l > 80) {
                    throw new \Exception("Description too long for " . $this->id);
                }
                break;
            case 'a':
            case 'ae':
                if ($v === null)
                    return;
                $v = trim($v);
                $l = strlen($v);
                if ($l == 0)
                    $v = null;
                elseif ($l > 80) {
                    throw new \Exception("$n too long for " . $this->id);
                }
                break;
            case 'c':
                if ($v != null && strlen($v) != 2) {
                    throw new \Exception("Invalid country " . $v);
                }
                break;
        }
    }

    public function jsonSerialize()
    {
        if (Config::$__mode==Config::MODE_EDIT) {
            $a = [
                'id'=>$this->id,
                'n'=>$this->name_r,
                'ne'=>$this->name_e,
            ];
            if ($this->aka_r != null) $a['aka'] = $this->aka_r;
            if ($this->aka_e != null) $a['akae'] = $this->aka_e;
        } else {
            $a = [
                'i' => $this->id
            ];
            if (Config::$lng == 'ru') {
                $a['n'] = $this->name_r;
                if ($this->aka_r != null)
                    $a['a'] = $this->aka_r;
            } else {
                $a['n'] = $this->name_e;
                if ($this->aka_e != null)
                    $a['a'] = $this->aka_e;
            }
            if ($this->sentimental) $a['s']=1;
            if($this->photo_count==0) $a['p']=0;
        }
        
        if ($this->country_of_origin != null)
            $a['c'] = $this->country_of_origin;
        return $a;
    }
}
?>