<?php

// Router für den eingebauten PHP-Webserver (php -S), siehe Taskfile.yml (task start).
// Ersetzt die Rewrite-Regeln der TYPO3-.htaccess: vorhandene Dateien werden direkt
// ausgeliefert bzw. ausgeführt (z.B. typo3/index.php), alles andere geht an index.php.

$documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($path !== '/' && is_file($documentRoot . $path)) {
    return false;
}
if (is_file($documentRoot . rtrim($path, '/') . '/index.php') && $path !== '/') {
    if (!str_ends_with($path, '/')) {
        header('Location: ' . $path . '/', true, 301);
        return true;
    }
    return false;
}

// Dateinamen mit Versionsnummer (FE.versionNumberInFilename), z.B. style.1712345678.css
if (preg_match('/^(.+)\.\d+\.(php|js|css|png|jpe?g|gif|svg|gzip)$/', $path, $matches)
    && is_file($documentRoot . $matches[1] . '.' . $matches[2])
) {
    $_SERVER['SCRIPT_NAME'] = $matches[1] . '.' . $matches[2];
    $mimeType = mime_content_type($documentRoot . $_SERVER['SCRIPT_NAME']) ?: 'application/octet-stream';
    header('Content-Type: ' . $mimeType);
    readfile($documentRoot . $_SERVER['SCRIPT_NAME']);
    return true;
}

$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $documentRoot . '/index.php';
chdir($documentRoot);
require $documentRoot . '/index.php';
