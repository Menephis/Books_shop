<?php

namespace src\Domain;

class Book
{
    /**
    * @var integer $id
    */
    protected $id;   
    /**
    * @var string $name
    */
    protected $name;
    /**
    * @var string $authors
    */
    protected $authors;
    /**
    * @var integer $price
    */
    protected $price;
    /**
    * @var string $image
    */
    protected $image;
    /**
    * @return void
    */
    function __construct(int $id, string $name, string $authors, int $price, string $image)
    {
        $this->id = $id;
        $this->name = $name;
        $this->authors = $authors;
        $this->price = $price;
        $this->image = $image;
    }
    /** 
    * @return integer $this->id
    */
    public function getId()
    {
        return $this->id;
    }
    /**
    * @return string $this->name
    */
    public function getName()
    {
        return $this->name;
    }
    /**
    * @return string $this->authors
    */
    public function getAuthors()
    {
        return $this->authors;
    }
    /**
    * @return integer $this->price
    */
    public function getPrice()
    {
        return $this->price;
    }
    /**
    * @return string $this->image
    */
    public function getImage()
    {
        return $this->image;
    }
}
?>