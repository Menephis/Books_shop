<?php

namespace src\Repository;

use Doctrine\DBAL\Connection;
use src\Repository\FieldDescription;

abstract class AbstractRepository
{
    /**
    * @var resourse $connection - stores connection to DB
    */
    protected $connection;
    /**
    * @param resourse $connection
    */
    function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
    * Description 
    *
    * Should return array that contains names of domain class and appropriate it array of FieldDescriptions
    *
    * @return array
    */
    abstract protected function getEntityDescription():array;
    /**
    * Create object from assoc array
    *
    * Create domain object from result query assoc array
    *
    * @param array $result - result of db query
    *
    * @return object - domain object
    */
    protected function objectFromAssoc(array $result, string $domainClass)
    {
        $injectValue = function($descriptions, $result){
            foreach($descriptions  as $description){
                $this->{$description->getProperty()} = $result[$description->getDBField()];
            }
            return $this;
        };
        // Return type declarations of getEntityDescription() makes sure that we get array
        $entityDescription = $this->getEntityDescription();
        if( ! array_key_exists($domainClass, $entityDescription))
            throw new \Exception( $domainClass . 'does not exist in entity description' ); 
        $domainObject = (new \ReflectionClass($domainClass))->newInstanceWithoutConstructor();
        $result = $this->prepareResult($entityDescription[$domainClass], $result);
        $injectValue = $injectValue->bindTo($domainObject, $domainObject);
        return $injectValue($entityDescription[$domainClass], $result);
    }
    /**
    * Create array of object from array of assoc array
    * 
    * @param array $result 
    *
    * @return array - return array of domain objects
    */
    protected function objectsFromArrayOfAssoc(array $result, string $domainClass)
    {
        $objects = array();
        foreach($result as $assoc){
            $objects[] = $this->objectFromAssoc($assoc, $domainClass);
        }
        return $objects;
    }
    /**
    * Prepare result to inject it in object
    *
    * Do something if you need or just return result
    *
    * @param mixed $result
    * @param array $fieldDescription - array FieldDescription
    *
    * @return array
    */
    abstract protected function prepareResult(array $fieldDescription, array $result);
}
?>