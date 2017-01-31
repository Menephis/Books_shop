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
                $idCategory = $request->get('idCategory');
                (new Image($image->getPathName()))->resize(150, 100)->save();
                foreach($files as $file){
                    (new Image($file->getPathName()))->resize(150, 100)->save();;
                }
                // Запись в базу
                $dbBook->saveBook($name, $authors, (int)$price, $image, $idCategory, $app['config']['paths']['path.to.images'], $description, 
                           $dateOfRelease, $language, $printing, $files);
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
        $controllers->match('/update', function (Request $request) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                $id = $request->get('updateBook');
                $price = $request->get('BookPrice');
                $name = $request->get('BookName');
                $authtors = $request->get('BookAuthors');
                $file = $request->files->get('photo');
                // Масштабирование изображения
                $image = new Image($file->getPathname());
                $image->resize(150, 100)->save();
                // UpdateBook
                $db->updateBook((int)$id, $name, $authtors, (int)$price, $file, $app['config']['paths']['path.to.images']);
            }
            return $templateEngine->render('UpdateBook');
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
                //return $app->redirect('/books-shop/web/admin');
            }
            $categories = $db->getCategories();
            return $templateEngine->render('changeOrderCategory', [ 'categories' => $categories]);
        });
        
        return $controllers;
    }
}
?>
