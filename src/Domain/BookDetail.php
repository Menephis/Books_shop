<?php

namespace src\Domain;

class BookDetail extends Book
{
    /**
    * @var string $description
    */
    protected $description;
    /**
    * @var date $dateRelease
    */
    protected $dateRelease;
    /**
    * @var string $language
    */
    protected $language;
    /**
    * @var integer $printing
    */
    protected $printing;
    /**
    * @var array $booksImages
    */
    protected $booksImages;
    /**
    * @var int $bookCategory
    */
    protected $bookCategories;
    /**
    * @return void
    */
    public function __construct(int $id, string $name, string $authors, int $price, string $image, string $description, $dateRelease, string $language, int $printing, string $booksImages, array $bookCategory)
    {
        parent::__construct($id, $name, $authors, $price, $image);
        $this->description = $description;
        $this->dateRelease = $dateRelease;
        $this->language = $language;
        $this->printing = $printing;
        $this->booksImages = $booksImages;    
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getDateRelease()
    {
        return $this->dateRelease;
    }
    public function getLanguage()
    {
        return $this->language;
    }
    public function getPrinting()
    {
        return $this->printing;
    }
    public function getBookImages()
    {
        return $this->booksImages;
    }
    public function getBookCategories()
    {
        return $this->bookCategories;
    }
}
?>