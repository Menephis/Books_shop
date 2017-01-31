<?php
namespace src\Repository;

class FieldDescription
{
    /**
    * @var string - name of property
    */
    protected $property;
    /**
    * @var string - name of database field
    */
    protected $dbField;
    /**
    * @var string - type
    */
    protected $type;
    /**
    * @param string $poperty
    * @param string $dbField
    * @param string $type
    *
    * @return void
    */
    public function __construct(string $poperty, string $dbField, string $type)
    {
        $this->property = $poperty;
        $this->dbField = $dbField;
        $this->type = $type;
    }
    /**
    * @return string 
    */
    public function getProperty()
    {
        return $this->property;
    }
    /**
    * @return string 
    */
    public function getDBField()
    {
        return $this->dbField;
    }
    /**
    * @return string 
    */
    public function getType()
    {
        return $this->type;
    }
}
?>