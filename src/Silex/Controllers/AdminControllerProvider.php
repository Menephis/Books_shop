<?php
namespace src\Silex\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use kurapov\kurapov_validate\Validator\Validator;
use src\ImagesService\Image;
use Silex\Provider\SessionServiceProvider;

class AdminControllerProvider implements ControllerProviderInterface{
    public function connect(Application $app){
        $app->register(new SessionServiceProvider());
        $controllers = $app['controllers_factory'];
        
        $controllers->get('/', function (Request $request) use($app){
            //var_dump($app['security.authorization_checker']->isGranted('ROLE_ADMIN'));
            $templateEngine = $app['template.engine']();
            return $templateEngine->render(
                'AdminPannel'
            );
        });
        
        $controllers->match('/addbook', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $dbBook = $app['book.repository']();
            $dbCategory = $app['category.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $name = $request->get('BookName');
                $authors = $request->get('BookAuthors');
                $price = $request->get('BookPrice');
                $description = $request->get('BookDescription');
                $dateOfRelease = $request->get('DateOfRelease');
                $language = $request->get('BookLanguage');
                $printing = $request->get('BookPrinting');
                $image = $request->files->get('photo');
                $files = $request->files->get('images');
                $idCategories = $request->get('idCategories');
                (new Image($image->getPathName()))->resize(150, 100)->save();
                foreach($files as $file){
                    (new Image($file->getPathName()))->resize(150, 100)->save();;
                }
                // Запись в базу
                $dbBook->saveBook($name, $authors, (int)$price, $image, $idCategories, $description, 
                           $dateOfRelease, $language, $printing, $files, $app['config']['paths']['path.to.images']);
                //return $app->redirect('/books-shop/web/admin');
            }
            return $templateEngine->render('AddBook', [
                'categories' => $dbCategory->getCategories(),
            ]);
        });
        $controllers->match('/delete', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('deleteBook');
                $db->deleteBook($id, $app['config']['paths']['path.to.images']);
            }
            return $templateEngine->render('deleteBook');
        });
        $controllers->match('/update/{idBook}', function (Request $request, $idBook) use($app){
            /* Определяем сервис с доступом к базе*/
            $dbBook = $app['book.repository']();
            $dbCategories = $app['category.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $name = $request->get('BookName');
                $authors = $request->get('BookAuthors');
                $price = $request->get('BookPrice');
                $description = $request->get('BookDescription');
                $dateOfRelease = $request->get('DateOfRelease');
                $language = $request->get('BookLanguage');
                $printing = $request->get('BookPrinting');
                $image = $request->files->get('photo');
                $idCategories = $request->get('idCategories');
                // Масштабирование изображения
                if($image){
                    (new Image($image->getPathName()))->resize(150, 100)->save();
                }else{
                    $image = null;
                }
                // UpdateBook
                $dbBook->updateBook($idBook, $name, $authors, $price, $image, $idCategories, $description, 
                                    $dateOfRelease, $language, $printing, $app['config']['paths']['path.to.images']);
            }
            return $templateEngine->render('UpdateBook', [
                'book' => $dbBook->getBookDetail($idBook), 
                'categories' => $dbCategories->getCategories(),
            ]);
        });
        
        
        $controllers->match('/addcategory', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['category.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $name = $request->get('categoryName');
                $id = $request->get('parentCategory');
                
                $db->addCategory($id, $name);
                return $app->redirect('/books-shop/web/admin');
            }
            $categories = $db->getCategories();
            return $templateEngine->render('addCategory', [ 'categories' => $categories]);
        });
        
        $controllers->match('/deletecategory', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['category.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();

            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('idCategory');
                $db->deleteCategory($id);
                return $app->redirect('/books-shop/web/admin');
            }
            $categories = $db->getCategories();
            return $templateEngine->render('deleteCategory', [ 'categories' => $categories]);
        });
        
        $controllers->match('/movecategory', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['category.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('idCategory');
                $newParent = $request->get('idParentCategory');
                
                $db->moveCategory($id, $newParent);
                return $app->redirect('/books-shop/web/admin');
            }
            $categories = $db->getCategories();
            return $templateEngine->render('moveCategory', [ 'categories' => $categories]);
        });
        
        $controllers->match('/changeordercategory', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['category.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('idCategory');
                $setAfter = $request->get('SetAfterIdCategory');
                
                $db->changeOrder($id, $setAfter);
                return $app->redirect('/books-shop/web/admin');
            }
            $categories = $db->getCategories();
            return $templateEngine->render('changeOrderCategory', [ 'categories' => $categories]);
        });
        
        return $controllers;
    }
}
?>
