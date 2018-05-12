<?php
namespace photo\edit;

class Thumbnail
{
    private $dir = NULL;
    private $file = NULL;

    public function __construct($dir,$file)
    {
        $this->dir = $dir;
        $this->file = $file;
    }
    
    public function resize_image($w, $h, $outfile, $qual = 75, $crop = FALSE) {
        list ( $width, $height ) = \getimagesize ( $this->dir.'/'.$this->file );
        if($width==null)
            throw new \Exception("Failed to get image info for ".$this->dir.$this->file);
        $r = $width / $height;
        if ($w / $h > $r) {
            $newwidth = $h * $r;
            $newheight = $h;
        } else {
            $newheight = $w / $r;
            $newwidth = $w;
        }
        if($width <= $newwidth) {
            if(\copy($this->dir.'/'.$this->file, $outfile)==false) 
                throw new \Exception('Failed to create resized image '.$dst);
        }
        $src = \imagecreatefromjpeg ( $this->dir.'/'.$this->file );
        if ($src == false) {
            throw new \Exception('Failed to open image '.$this->dir.'/'.$this->file );
        } else {
            $dst = \imagecreatetruecolor ( $newwidth, $newheight );
            \imagecopyresampled ( $dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );
            $exif = \exif_read_data ( $this->dir.'/'.$this->file );
            if (! empty ( $exif ['Orientation'] )) {
                switch ($exif ['Orientation']) {
                    case 8 :
                        $dst = \imagerotate ( $dst, 90, 0 );
                        break;
                    case 3 :
                        $dst = \imagerotate ( $dst, 180, 0 );
                        break;
                    case 6 :
                        $dst = \imagerotate ( $dst, - 90, 0 );
                        break;
                }
            }
            if (\imagejpeg ( $dst, $outfile, $qual ) == false)
                throw new \Exception('Failed to create resized image '.$dst);
        }
        return true;
    }
    
    public function process($force=false)
    {
        $thumbdir = $this->dir.'/.thumb';
        if(!is_dir($thumbdir)) {
            if (mkdir($thumbdir)==false)
                throw new \Exception('Failed to create thumb dir '.$thumbdir) ;
        }
        if(!is_dir($thumbdir.'/300')) {
            if(mkdir($thumbdir.'/300')==false)
                throw new \Exception('Failed to create thumb/300 dir') ;
        }
        if(!is_dir($thumbdir.'/1000')) {
            if(mkdir($thumbdir.'/1000')==false)
                throw new \Exception('Failed to create thumb/1000 dir') ;
        }
        $thumbfile_small = $thumbdir.'/300/'.$this->file;
        $thumbfile_big = $thumbdir.'/1000/'.$this->file;
        if(!is_file($thumbfile_small) || $force) {
            if($this->resize_image(300, 300, $thumbfile_small)==false)
                throw new \Exception('Failed to create thumb '.$thumbfile_small);
        }
        if(!is_file($thumbfile_big) || $force) {
            if($this->resize_image(1000, 1000, $thumbfile_big)==false)
                throw new \Exception('Failed to create thumb '.$thumbfile_big);
        }
        return true;
    }
    
}

