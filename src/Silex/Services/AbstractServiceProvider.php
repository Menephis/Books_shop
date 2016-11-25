<?php
namespace src\Silex\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

abstract class AbstractServiceProvider implements ServiceProviderInterface{
    /**
    * Register all Services
    *
    * @param Application object @app
    *
    * @return void
    */
    public function register(Container $app){
        foreach($this->getServices() as $service){
            $this->{'register' . $service}($app);
        }
    }
    //public function register(Container $pimple);
    /**
    * Declare services
    *
    * @return array $Services
    */
    abstract protected function getServices();
}
?>
