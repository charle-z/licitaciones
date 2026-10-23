<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager;

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('America/Bogota');
$database = new Manager();
$database->addConnection([
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'mysql',
    'database' => getenv('DB_DATABASE') ?: 'suplos',
    'username' => getenv('DB_USERNAME') ?: 'suplos_app',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'strict' => true,
    'timezone' => '-05:00',
    'options' => [PDO::ATTR_EMULATE_PREPARES => false],
]);
$database->setAsGlobal();
$database->bootEloquent();

return $database;
