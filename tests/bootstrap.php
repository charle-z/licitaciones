<?php

declare(strict_types=1);

if (getenv('DB_DATABASE') !== 'suplos_test') {
    throw new RuntimeException('Las pruebas solo pueden usar suplos_test.');
}
require dirname(__DIR__) . '/app/bootstrap.php';
