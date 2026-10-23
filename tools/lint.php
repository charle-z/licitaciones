<?php

declare(strict_types=1);

$failed = false;
foreach (['app', 'public', 'views', 'bin', 'tests', 'tools'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getExtension() === 'php') {
            passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $code);
            $failed = $failed || $code !== 0;
        }
    }
}
exit($failed ? 1 : 0);
