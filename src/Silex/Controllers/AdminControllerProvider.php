<?php
namespace src\Silex\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\Request;

use src\ImagesService\{Image, ImageException };
use src\Validator\{ DBMask, BookValidator };
use Menephis\MaskValidator\Validator\ValidatorViewHelper;

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
        /*
        * 
        * Add Book
        *
        */
        $controllers->match('/addbook', function (Request $request) use($app){
            try{
                /* Определяем сервис с доступом к базе*/
                $dbBook = $app['book.repository']();
                $dbCategory = $app['category.repository']();
                /* Определяем сервис с шаблонами */
                $templateEngine = $app['template.engine']();
                // Формирование вывода 
                $toOutput = [
                    'categories' => $dbCategory->getCategories(),
                ];
                if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                    // Загружаем маску валидации
                    $dbMask = (new DBMask($app['config']['paths']['path.to.validate.masks']))->fastLoadYaml('SaveBook.yaml');
                    $bookValidator = new BookValidator($dbMask);
                    // Валидируем
                    $validatedData = $bookValidator->validate($_POST);
                    $image = $request->files->get('photo');
                    $toOutput['viewHelper'] = new ValidatorViewHelper($validatedData);; 
                    // Валидация и масштабирование изображения
                    if( isset($image) ){
                        (new Image($image->getPathName()))->resize(250, 125)->save();
                        $toOutput['validImage'] = true;
                    }else{
                        $toOutput['validImage'] = false;
                        $toOutput['imageError'] = 'Image isn\'t sent';
                    } 
                    // Если данные или изображение не прошли проверку возвращаем их обратно
                    if ( $bookValidator->isValid() and $toOutput['validImage'] ) {
                        // Запись в базу
                        $dbBook->saveBook(
                            $validatedData['BookName']->getValidedData(), 
                            $validatedData['BookAuthors']->getValidedData(), 
                            $validatedData['BookPrice']->getValidedData(),
                            $image,
                            $validatedData['idCategories']->getValidedData(),
                            $validatedData['BookDescription']->getValidedData(), 
                            $validatedData['DateOfRelease']->getValidedData(), 
                            $validatedData['BookLanguage']->getValidedData(), 
                            $validatedData['BookPrinting']->getValidedData(), 
                            $app['config']['paths']['path.to.images']);
                        return $app->redirect('/books-shop/web/admin');
                    }else{
                        return $templateEngine->render('AddBook', $toOutput);
                    }
                }
                $toOutput['viewHelper'] = new ValidatorViewHelper();
                $toOutput['validImage'] = true;
                return $templateEngine->render('AddBook', $toOutput);
            }catch( ImageException $e){
                $toOutput['validImage'] = false;
                $toOutput['imageError'] = $e->getMessage();
                return $templateEngine->render('AddBook', $toOutput);
            }
        });
        /*
        * 
        * Delete Book
        *
        */
        $controllers->match('/delete/{idBook}', function (Request $request, $idBook) use($app){
            /* Определяем сервис с доступом к базе*/
            $db = $app['book.repository']();
            $db->deleteBook($idBook, $app['config']['paths']['path.to.images']);
            return $app->redirect('/books-shop/web/catalog');
        });
        /*
        * 
        * Update Book
        *
        */
        $controllers->match('/update/{idBook}', function (Request $request, $idBook) use($app){
            try{
                /* Определяем сервис с доступом к базе*/
                $dbBook = $app['book.repository']();
                $dbCategory = $app['category.repository']();
                /* Определяем сервис с шаблонами */
                $templateEngine = $app['template.engine']();
                $toOutput = [
                        'categories' => $dbCategory->getCategories(),
                ];
                if(($_SERVER['REQUEST_METHOD'] == 'POST')){
                    // Загружаем маску валидации
                    $dbMask = (new DBMask($app['config']['paths']['path.to.validate.masks']))->fastLoadYaml('SaveBook.yaml');
                    $bookValidator = new BookValidator($dbMask);
                    // Валидируем
                    $validatedData = $bookValidator->validate($_POST);
                    $image = $request->files->get('photo');
                    $toOutput['viewHelper'] = new ValidatorViewHelper($validatedData);; 
                    // Валидация и масштабирование изображения
                    if( isset($image) ){
                        (new Image($image->getPathName()))->resize(250, 125)->save();
                        $toOutput['validImage'] = true;
                    }else{
                        $toOutput['validImage'] = false;
                        $toOutput['imageError'] = 'Image isn\'t sent';
                    } 
                    // Если данные или изображение не прошли проверку возвращаем их обратно
                    if ( $bookValidator->isValid() and $toOutput['validImage'] ) {
                        // UpdateBook
                        $dbBook->saveBook(
                            $idBook,
                            $validatedData['BookName']->getValidedData(), 
                            $validatedData['BookAuthors']->getValidedData(), 
                            $validatedData['BookPrice']->getValidedData(),
                            $image,
                            $validatedData['idCategories']->getValidedData(),
                            $validatedData['BookDescription']->getValidedData(), 
                            $validatedData['DateOfRelease']->getValidedData(), 
                            $validatedData['BookLanguage']->getValidedData(), 
                            $validatedData['BookPrinting']->getValidedData(), 
                            $app['config']['paths']['path.to.images']);
                        return $app->redirect('/books-shop/web/admin');
                    }else{
                        return $templateEngine->render('UpdateBook', $toOutput);
                    }
                }
                $toOutput['viewHelper'] = new ValidatorViewHelper();
                $toOutput['validImage'] = true;
                $toOutput['book'] = $dbBook->getBookDetail($idBook);
                return $templateEngine->render('UpdateBook', $toOutput);
            }catch( ImageException $e){
                $toOutput['validImage'] = false;
                $toOutput['imageError'] = $e->getMessage();
                return $templateEngine->render('AddBook', $toOutput);
            }
        });
        /*
        * 
        * Add category
        *
        */
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
        /*
        * 
        * Delete category
        *
        */
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
        /*
        * 
        * move category
        *
        */
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
        /*
        * 
        * Change order Book
        *
        */
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
