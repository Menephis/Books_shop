<?php
namespace src\Silex\Services;

use Silex\Application;
use src\Silex\Services\AbstractServiceProvider;
use src\Repository\BookRepository;
use src\Silex\Views\TemplateEngine;

class RepositoryServiceProvider extends AbstractServiceProvider{
    /**
    * Declare services
    *
    * @return array $services
    */
    protected function getServices(){
        return [
            'BookRepository',
            'TemplateEngine',
        ];
    }
    /**
    * Register bookRepository
    *
    * @param Application @app
    *
    * @return void
    */
    protected function registerBookRepository(Application $app){
        $app['book.repository.service'] = $app->protect(function() use($app){
            return new BookRepository($app['db.connection']);
        });
    }
    /**
    * Register templateEngine
    *
    * @param Application @app
    *
    * @return void
    */
    protected function registerTemplateEngine(Application $app){
        $app['template.engine'] = $app->protect(function() use($app){
            return new TemplateEngine($app['config']['paths']['path.to.templates'], $app['config']['paths']['path.to.web']);
        });
    }
}
?>
