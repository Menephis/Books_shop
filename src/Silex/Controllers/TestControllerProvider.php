<?php
namespace src\Silex\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use kurapov\kurapov_validate\Validator\Validator;
use kurapov\kurapov_validate\Masks\AuthenticationMask;
use src\ImagesService\Image;
use Silex\Provider\SessionServiceProvider;

use Doctrine\DBAL\Types\Type;

class TestControllerProvider implements ControllerProviderInterface{
    public function connect(Application $app){
        $app->register(new SessionServiceProvider());
        $controllers = $app['controllers_factory'];
        $controllers->get('/', function (Request $request) use($app){
            //var_dump($app['security.authentication_listener.form._proto']);
            //var_dump($request->getSession());
            $token = $app['security.token_storage']->getToken();
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            $books = $db->getBooksByCat(1, 0, 20);
            $categories = $db->getCategories();
            var_dump(Type::INTEGER);
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
                $des = $request->get('BookDes');
                $file = $request->files->get('photo');
                
                // Запись в базу
                $db->saveBook($name, $des, (int)$price, $file, $app['config']['paths']['path.to.images']);
            }
            return $templateEngine->render('AddBook');
        });
        $controllers->match('/delete', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('deleteBook');
                echo $id;
                $db->deleteBook($id, $app['config']['paths']['path.to.images']);
            }
            return $templateEngine->render('deleteBook');
        });
        $controllers->match('/update', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository.service']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('updateBook');
                $price = $request->get('BookPrice');
                $name = $request->get('BookName');
                $des = $request->get('BookDes');
                $file = $request->files->get('photo');
                // Масштабирование изображения
                $image = new Image($file->getPathname());
                $image->resize(300, 300)->save();
                // UpdateBook
                $db->updateBook((int)$id, $name, $des, (int)$price, $file, $app['config']['paths']['path.to.images']);
            }
            return $templateEngine->render('UpdateBook');
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
