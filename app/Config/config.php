<?php
const DB_HOST = "localhost";
const DB_NAME = "books_shop";
const DB_USER = "Admin";
const DB_PASS = "1231";

$config = [
    'dbs' => [
        'dbname' => 'books_shop',
        'user' => 'Admin',
        'password' => '1231',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ],
    'paths' => [
        'path.to.templates' => __DIR__ . '/../../src/Templates',
        'path.to.web' => '/books-shop/web',
    ]
]
?>
