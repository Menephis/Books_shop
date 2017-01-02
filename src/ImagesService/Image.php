<?php
namespace src\ImagesService;

class Image{
    private $file;              //Путь к файлу с исходным изображением
    private $image;             //Исходное изображение
    private $image_new = false;   //Изображение после масштабирования
 
    /**
    * Загрузка файла для обработки
    *
    * @param string $file путь к файлу
    */
    public function __construct($file)
    {
        try
        {
            if( ! file_exists($file)) 
                throw new \Exception($file . " - file doesn't exist");
            //Получаем информацию о файле
            if( ! list($width, $height, $image_type) = getimagesize($file)) 
                throw new \Exception($file . " - is not image");
            //Создаем изображение из файла
            switch ($image_type)
            {
                case 1: $this->image = imagecreatefromgif($file); break;
                case 2: $this->image = imagecreatefromjpeg($file);  break;
                case 3: $this->image = imagecreatefrompng($file); break;
                default: throw new \Exception('Image should be jpg, png or gif type');  break;
            }
            $this->file = $file;
        }catch(\Exception $e){
            throw new \Exception($e);
        }
    }
 
    /**
     * Масштабирует исходное изображение
     *
     * @param int $W Ширина
     * @param int $H Высота
     */
    public function resize($W, $H)
    {
        $this->image_new = false;
 
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
        
        $this->image_new = imagecreatetruecolor($W,$H);
        imagecopyresampled($this->image_new, $this->image, 0, 0, 0, 0, $W, $H, $X, $Y);
        return $this;
 
    }
 
 
    /**
     * Сохранение файла
     *
     * @param string $file Путь к файлу (если не указан, записывает в исходный)
     * @param int $qualiti Качество сжатие JPEG
     */
    public function save($file=false, $qualiti=90)
    {
        try{
            if( ! $file || $file == $this->file) {
                $file = $this->file;
                if( ! $this->image_new){
                    throw new \Exception("Can't create image");  
                }else{
                    if( ! ImageJpeg($this->image_new, $file, $qualiti))
                        throw new \Exception("Can't save image");
                }
            }else{
                if( ! $this->image_new){
                    if( ! copy($this->file, $file))
                        throw new \Exception("Can't save image"); 
                }else{
                    if( ! ImageJpeg($this->image_new, $file, $qualiti))
                        throw new \Exception("Can't save image");
                }
            }
        }catch(\Exception $e){
            throw $e;
        }
    }
}
?>