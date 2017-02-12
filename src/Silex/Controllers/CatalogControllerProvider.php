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
            $categoryRepository = $app['category.repository']();
            $bookRepository = $app['book.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            $books = $bookRepository->getBooksByCat(1, 0, 20);
            //$b = $bookRepository->getBook(10);
            $categories = $categoryRepository->getCategories();
            return $templateEngine->render(
                'index',
                [
                    'categories' => $categories,
                    'books' => $books,
                    'authorizationChecker' => $app['security.authorization_checker'],
                ]
            );
        });
        $controllers->get('/{idCategory}', function (Request $request, $idCategory) use($app){

            /* Определяем сервис с доступом к базе*/
            $categoryRepository = $app['category.repository']();
            $bookRepository = $app['book.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            $idCategory = (int)$idCategory != 0 ? $idCategory: $idCategory + 1;
            $categories = $categoryRepository->getCategories();
            $books = $bookRepository->getBooksByCat($idCategory, 0, 20);
            
            return $templateEngine->render(
                'index',
                [
                    'categories' => $categories,
                    'books' => $books,
                    'authorizationChecker' => $app['security.authorization_checker'],
                ]
            );
        });
        $controllers->get('/detail/{idBook}', function (Request $request, $idBook) use($app){

            /* Определяем сервис с доступом к базе*/
            $bookRepository = $app['book.repository']();
            /* Определяем сервис с шаблонами */
            $templateEngine = $app['template.engine']();
            $book = $bookRepository->getBookDetail($idBook);
            return $templateEngine->render(
                'bookDetail',
                [
                    'book' => $book,
                ]
            );
        });
        return $controllers;
    }
}
?>