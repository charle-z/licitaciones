<?php

declare(strict_types=1);

$integrity = json_decode(file_get_contents(dirname(__DIR__) . '/public/assets/integrity.json'), true, 512, JSON_THROW_ON_ERROR);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Licitaciones</title>
    <link rel="stylesheet" href="/assets/app.css" integrity="<?= htmlspecialchars($integrity['app.css'], ENT_QUOTES, 'UTF-8') ?>">
    <script type="module" src="/assets/app.js" integrity="<?= htmlspecialchars($integrity['app.js'], ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body><div id="app"></div></body>
</html>
