<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../app/Config/config.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use src\Silex\Controllers\TestControllerProvider;
use src\Silex\Services\RepositoryServiceProvider;

$app = new Silex\Application();
$app['debug'] = true;

$DoctrineConfig = new Configuration();
$app['config'] = $config;
$app['db.connection'] = DriverManager::getConnection($app['config']['dbs'], $DoctrineConfig);

$app->register(new RepositoryServiceProvider);
$app->mount('/test', new TestControllerProvider());
$app->run();
?>
