<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../app/Config/config.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use src\Silex\Controllers\TestControllerProvider;
use src\Silex\Services\RepositoryServiceProvider;
use kurapov\kurapov_validate\Validator\Validator;

$app = new Silex\Application();
$app['debug'] = true;

$DoctrineConfig = new Configuration();
$app['config'] = $config;
$app['db.connection'] = function() use ($app, $DoctrineConfig){
    return DriverManager::getConnection($app['config']['dbs'], $DoctrineConfig);
};
$app->register(new RepositoryServiceProvider);
$app->mount('/test', new TestControllerProvider());
//$app->boot();
//$app['security.firewalls'] = array(
//        'admin' => array(
//            'pattern' => '^/test',
//            'http' => true,
//            'users' => array(
//                // raw password is foo
//                'admin' => array('ROLE_ADMIN', '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a'),
//                ),
//        ),
//);
$app->run();

?>
