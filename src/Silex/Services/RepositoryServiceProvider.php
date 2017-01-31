<?php
namespace src\Silex\Services;

use Silex\Application;
use src\Silex\Services\AbstractServiceProvider;
use src\Repository\BookRepository;
use src\Repository\BookDetailRepository;
use src\Repository\CategoryRepository;
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
            'CategoryRepository',
            'BookDetailRepository',
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
        $app['book.repository'] = $app->protect(function() use($app){
            return new BookRepository($app['db.connection']);
        });
    }
    /**
    * Register BookDetailrepository
    *
    * @param Application @app
    *
    * @return void
    */
    protected function registerBookDetailRepository(Application $app){
        $app['book.detail.repository'] = $app->protect(function() use($app){
            return new BookDetailRepository($app['db.connection']);
        });
    }
    /**
    * Register templateEngine
    *
    * @param Application @app
    *
    * @return void
    */
    protected function registerCategoryRepository(Application $app){
        $app['category.repository'] = $app->protect(function() use($app){
            return new CategoryRepository($app['db.connection']);
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
