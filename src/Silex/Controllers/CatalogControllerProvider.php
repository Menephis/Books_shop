<?php
namespace src\Silex\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use kurapov\kurapov_validate\Validator\Validator;

class CatalogControllerProvider implements ControllerProviderInterface{
    public function connect(Application $app){
        $controllers = $app['controllers_factory'];
        $controllers->get('/', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            $books = $db->getBooksByCat(1, 0, 20);
            $categories = $db->getCategories();
            return $templateEngine->render(
                'index',
                [
                    'categories' => $categories,
                    'books' => $books
                ]
            );
        });
        $controllers->get('/{idCategory}', function (Request $request, $idCategory) use($app){

            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            $idCategory = (int)$idCategory != 0 ? $idCategory: $idCategory + 1;
            $categories = $db->getCategories();
            $books = $db->getBooksByCat($idCategory, 0, 20);
            
            return $templateEngine->render(
                'index',
                [
                    'categories' => $categories,
                    'books' => $books
                ]
            );
        });
        $controllers->get('/detail/{idBook}', function (Request $request, $idBook) use($app){

//            /* Определяем сервис с доступом к базе*/
//            $db = $app['book.repository.service']();
//            /* Определяем сервис с шаблонами */
//            $templateEngine = $app['template.engine']();
//            $idCategory = (int)$idCategory != 0 ? $idCategory: $idCategory + 1;
//            $categories = $db->getCategories();
//            $books = $db->getBooksByCat($idCategory, 0, 20);
            echo $idBook;
            return null;
        });
        return $controllers;
    }
}
?>