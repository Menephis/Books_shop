<?php

namespace src\Validator;

use Menephis\MaskValidator\Masks\AbstractMask;

class DBMask extends AbstractMask
{
    /**
    * @var string $pathToMasks - path to all masks
    */
    protected $pathToMasks;
    /**
    * @param string $pathToMasks
    */
    public function __construct(string $pathToMasks)
    {
        $this->pathToMasks = realpath($pathToMasks);
    }
    /**
    * @param string $maskName
    */
    public function fastLoadYAML(string $maskName)
    {
        $path = $this->pathToMasks . DIRECTORY_SEPARATOR . $maskName;
        if( file_exists($path) ){
            parent::loadFromYAML($path, pathinfo($maskName, PATHINFO_FILENAME), TRUE);
        }else{
            throw new \Exception(sprintf('YAML mask %s doesn\'t exists on path %s', $maskName, $path));
        }
    }
}
?>