<?php
namespace src\Silex\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use kurapov\kurapov_validate\Validator\Validator;
use kurapov\kurapov_validate\Masks\AuthenticationMask;

class TestControllerProvider implements ControllerProviderInterface{
    public function connect(Application $app){
        $controllers = $app['controllers_factory'];
        $controllers->get('/', function () use($app){
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


        $controllers->match('/addbook', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $price = $request->get('BookPrice');
                $name = $request->get('BookName');
                var_dump($_POST);
                $des = $request->get('BookDes');
                $db->saveBook($name, $des, $price);
            }
            $categories = $db->getCategories();
            return $templateEngine->render('AddBook');
        });
        
        
        $controllers->match('/addcategory', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $name = $request->get('categoryName');
                $id = $request->get('parentCategory');
                var_dump($_POST);
                
                $result = $db->addCategory($id, $name);
                var_dump($result);
            }
            $categories = $db->getCategories();
            return $templateEngine->render('addCategory', [ 'categories' => $categories]);
        });

        $controllers->get('/{idCategory}', function (Request $request, $idCategory) use($app){

            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

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
        return $controllers;
    }
}
?>
