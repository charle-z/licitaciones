<?php

declare(strict_types=1);

use App\Controllers\ActividadController;
use App\Controllers\DocumentoController;
use App\Controllers\OfertaController;
use App\Http\HttpException;
use App\Http\Response;
use App\Services\DocumentStorage;

require dirname(__DIR__) . '/app/bootstrap.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
session_name('licitaciones');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict', 'cookie_secure' => getenv('COOKIE_SECURE') === '1', 'use_strict_mode' => true]);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
session_write_close();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST' && !hash_equals($csrf, $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        throw new HttpException('La sesión cambió. Recarga la página e inténtalo de nuevo.', 403);
    }
    $storage = new DocumentStorage(dirname(__DIR__) . '/storage');
    $offers = new OfertaController($storage);
    if ($method === 'GET' && $path === '/health') {
        \Illuminate\Database\Capsule\Manager::connection()->select('SELECT 1');
        Response::json(['status' => 'ok']);
    } elseif ($method === 'GET' && $path === '/') {
        require dirname(__DIR__) . '/views/home.php';
    } elseif ($method === 'GET' && $path === '/api/actividades') {
        (new ActividadController())->index();
    } elseif ($method === 'GET' && $path === '/api/ofertas/exportar') {
        $offers->export();
    } elseif ($path === '/api/ofertas' && $method === 'GET') {
        $offers->index();
    } elseif ($path === '/api/ofertas' && $method === 'POST') {
        $offers->create();
    } elseif (preg_match('#^/api/ofertas/([1-9]\d{0,9})$#D', $path, $match) && in_array($method, ['GET', 'POST'], true)) {
        $method === 'GET' ? $offers->show((int) $match[1]) : $offers->update((int) $match[1]);
    } elseif ($method === 'GET' && preg_match('#^/api/documentos/([1-9]\d{0,9})/descargar$#D', $path, $match)) {
        (new DocumentoController($storage))->download((int) $match[1]);
    } else {
        throw new HttpException('La ruta solicitada no existe.', 404);
    }
} catch (HttpException $error) {
    Response::json(['message' => $error->getMessage(), 'errors' => $error->errors], $error->status);
} catch (Throwable $error) {
    $reference = bin2hex(random_bytes(6));
    error_log('Error ' . $reference . ': ' . $error::class . ' código ' . $error->getCode());
    Response::json(['message' => 'No se pudo completar la operación. Referencia: ' . $reference], 500);
}
