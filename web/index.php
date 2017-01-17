<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../app/Config/config.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use src\Silex\Controllers\TestControllerProvider;
use src\Silex\Services\RepositoryServiceProvider;
use kurapov\kurapov_validate\Validator\Validator;
use src\Silex\Security\User\UserProvider;
use Silex\Provider\SecurityServiceProvider;
use src\Silex\Security\Encoder\BCryptEncodeWithParameter;
use src\Silex\Controllers\CatalogControllerProvider;
use src\Silex\Controllers\AdminControllerProvider;

$app = new Silex\Application();
$app['debug'] = true;

$DoctrineConfig = new Configuration();
$app['config'] = $config;
$app['db.connection'] = function() use ($app, $DoctrineConfig){
    return DriverManager::getConnection($app['config']['dbs'], $DoctrineConfig);
};

$app->register(new RepositoryServiceProvider);

$app->mount('/catalog', new CatalogControllerProvider());
$app->mount('/admin', new AdminControllerProvider());
$app->mount('/test', new TestControllerProvider());

$app->register(new SecurityServiceProvider());

$app->get('/login', function() use($app) {
    $templateEngine = $app['template.engine']();
    return $templateEngine->render('login');
}   
);
$app['security.default_encoder'] = function ($app) {
    return new BCryptEncodeWithParameter(10, $app['config']['security']['parameter']);
};
$app['security.firewalls'] = array(
        'admin' => array(
            'form' => true,
            'pattern' => '^.*',
            'anonymous' => true,
            'form' => array('login_path' => '/login', 'check_path' => '/test/login_check'),
            'users' => function () use ($app) {
                return new UserProvider($app['db.connection']);
            },
    ),
);

$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array('ROLE_USER'),
);
$app['security.access_rules'] = array(
    array('^/admin', 'ROLE_ADMIN',),
);
$app->run();

?>
