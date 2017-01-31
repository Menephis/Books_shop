<?php

namespace src\Repository;

use Doctrine\DBAL\Connection;
use src\Repository\FieldDescription;

abstract class AbstractRepository
{
    /**
    * @var - resourse $connection - stores connection to DB
    */
    protected $connection;
    /**
    * @param - resourse $connection
    */
    function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
    * Get entity
    *
    * Should return domain class that present entity in busines logic
    */
    abstract protected function getDomainClass();
    /**
    * Description 
    *
    * Should return array that contains query result fields appropriate propertys domain entity ($propertyName => $queryFieldName)
    *
    * @return array
    */
    abstract protected function getFieldDescription();
    /**
    * Create object from assoc array
    *
    * Create domain object from result query assoc array
    *
    * @param array $result - result of db query
    *
    * @return object - domain object
    */
    protected function objectFromAssoc(array $result)
    {
        $injectValue = function($descriptions, $result){
            foreach($descriptions  as $description){
                $this->{$description->getProperty()} = $result[$description->getDBField()];
            }
            return $this;
        };
        $domainObject = (new \ReflectionClass($this->getDomainClass()))->newInstanceWithoutConstructor();
        $result = $this->prepareValue($this->getFieldDescription(), $result);
        $injectValue = $injectValue->bindTo($domainObject, $domainObject);
        return $injectValue($this->getFieldDescription(), $result);
    }
    /**
    * Create array of object from array of assoc array
    * 
    * @param array $result 
    *
    * @return array - return array of domain objects
    */
    protected function objectsFromArrayOfAssoc(array $result)
    {
        $objects = array();
        foreach($result as $assoc){
            $objects[] = $this->objectFromAssoc($assoc);
        }
        return $objects;
    }
    /**
    * Prepare result to inject it in object
    *
    * Do something if you need or just return result
    *
    * @param mixed $result
    * @param FieldDescription
    *
    * @return mixed
    */
    abstract protected function prepareValue($fieldDescription, array $result);
}
?>