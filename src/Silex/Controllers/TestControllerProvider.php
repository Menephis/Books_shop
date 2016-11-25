<?php
namespace src\Silex\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class TestControllerProvider implements ControllerProviderInterface{
    public function connect(Application $app){
        $controllers = $app['controllers_factory'];
        $controllers->get('/', function () use($app){

            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            $categories = $db->getCategories();
            return $templateEngine->render(
                'index',
                [
                    'categories' => $categories
                ]
            );
        });


        $controllers->match('/input', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $price = $request->get('BookPrice');
                $name = $request->get('BookName');
                $des = $request->get('BookDes');
                $db->saveBook($name, $des, $price);
            }
            return $templateEngine->render('AddBook');
        });

        $controllers->get('/{idCategory}', function (Request $request, $idCategory) use($app){

            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            $categories = $db->getCategories();
            $books = $db->getBooksByCat($idCategory, 0, 3);
            return $templateEngine->render(
                'index',
                [
                    'categories' => $categories,
                    'books' => $books
                ]
            );
        });
        return $controllers;
    }
}
?>
