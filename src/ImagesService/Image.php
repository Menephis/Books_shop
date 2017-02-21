<?php
namespace src\ImagesService;

class Image{
    /**
    * @var string $file - path to file 
    */
    private $file;
    /**
    * @var resource $image - current image
    */
    private $image;
    /**
    * @var string $imageType - type of passed image
    */
    private $imageType;
    /*
    * Allowed image type to processing
    */
    const ALLOWED_IMAGE_TYPE = ['jpeg', 'jpg', 'png', 'gif'];
    /**
    * DOwnload the file to process
    *
    * @param string $file - path to file
    */
    public function __construct($file)
    {
        try
        {
            if( ! file_exists($file)) 
                throw new ImageException($file . " - file doesn't exist");
            // Get imnformation about file
            if( ! list($width, $height, $imageType) = getimagesize($file)) 
                throw new ImageException("Passed file is not image");
            //Create image from filepath
            switch ($imageType)
            {
                case 1: 
                    $this->image = imagecreatefromgif($file);
                    break;
                case 2: 
                    $this->image = imagecreatefromjpeg($file);  
                    break;
                case 3: 
                    $this->image = imagecreatefrompng($file); 
                    break;
                default: 
                    throw new ImageException(sprintf('Image type must be in %s', implode(',', self::ALLOWED_IMAGE_TYPE)));  
                    break;
            }
            $this->imageType = image_type_to_extension($imageType, FALSE);
            $this->file = $file;
        }catch( ImageException $e){
            throw $e;
        }
    }
 
    /**
     * Resize current image
     *
     * The image is scaled by proportion rules
     *
     * @param int $W - width
     * @param int $H - height
     */
    public function resize($W, $H)
    {
 
        $X = ImageSX($this->image);
        $Y = ImageSY($this->image);
 
        $H_NEW = $Y;
        $W_NEW = $X;
 
        if($X > $W){
            $W_NEW = $W;
            $H_NEW = $W * $Y / $X;
        }
 
        if($H_NEW > $H){
            $H_NEW = $H;
            $W_NEW = $H * $X / $Y;
        }
        
        $W = (int)$W_NEW;
        $H = (int)$H_NEW;
        
        $image = imagecreatetruecolor($W,$H);
        if( ! imagecopyresampled($image, $this->image, 0, 0, 0, 0, $W, $H, $X, $Y))
            throw new ImageException('Can\'t resize image');
        $this->image = $image;
        return $this;
 
    }
    /**
    * Change type of image then it will be save
    */
    public function changeToJpeg()
    {
        $this->imageType = 'jpeg';
        return $this;
    }
    /**
    * Change type of image then it will be save
    */
    public function changeToGif()
    {
        $this->imageType = 'gif';
        return $this;
    }
    /**
    * Change type of image then it will be save
    */
    public function changeToPng()
    {
        $this->imageType = 'png';
        return $this;
    }
    /**
    * Create image dependet of current type of image
    *
    * @param string $file - path to save image
    * @param integer $quality - qualiti for jpeg type
    * 
    * @return void
    */
    protected function createImage($file, $qualiti)
    {
        try
        {
            switch($this->imageType)
            {
                case 'jpeg':
                    $result = ImageJpeg($this->image, $file, $qualiti);
                    break;
                case 'gif':
                    $result = ImageGif($this->image, $file);
                    break;
                case 'png':
                    $result = ImagePng($this->image, $file);
                    break;
                default:
                    throw new ImageException('Can\'t save image');
            }  
            if( ! $result)
                throw new ImageException('Can\'t save image');
        }catch(ImageException $e)
        {
            throw $e;
        }
        
    }
    /**
     * Save image
     *
     * @param string $file - if will not pass then will save to current path
     * @param int $qualiti - qualiti for JPEG TYPE
     */
    public function save($file = false, $qualiti = 90)
    {
        try
        {
            if( ! $file || $file === $this->file) {
                $this->createImage($this->file, $qualiti);
            }else{
                $this->createImage($file, $qualiti);
            }
            return true;
        }catch( ImageException $e)
        {
            throw $e;
        }  
    }
}
?>