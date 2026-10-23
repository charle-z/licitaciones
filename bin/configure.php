<?php

declare(strict_types=1);

$path = dirname(__DIR__) . '/.env';
$handle = @fopen($path, 'x');
if ($handle === false) {
    fwrite(STDERR, "No se cambió .env. Si ya existe, conserva sus claves.\n");
    exit(is_file($path) ? 0 : 1);
}
chmod($path, 0600);
fwrite($handle, 'DB_PASSWORD=' . bin2hex(random_bytes(32)) . "\nMYSQL_ROOT_PASSWORD=" . bin2hex(random_bytes(32)) . "\n");
fclose($handle);
echo "Configuración local creada. Las claves no se muestran ni se suben al repositorio.\n";
