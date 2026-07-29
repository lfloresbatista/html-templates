<?php
/**
 * Router para `php -S host:port router.php`
 * Bloquea secretos y directorios sensibles.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$uri = str_replace('\\', '/', $uri);

// Normalizar
if (str_contains($uri, '..')) {
    http_response_code(400);
    echo 'Bad Request';
    return true;
}

$deniedPrefixes = [
    '/config/',
    '/storage/',
    '/database/',
    '/docker/',
    '/.git/',
    '/tcpdf/examples/',
    '/tcpdf/tools/',
];
$deniedExact = [
    '/.env',
    '/.env.example',
    '/secret',
    '/.htaccess',
    '/.gitignore',
    '/router.php',
    '/composer.json',
    '/composer.lock',
];
$deniedSuffixes = ['.sql', '.sqlite', '.log', '.bak', '.md', '.example'];

foreach ($deniedPrefixes as $p) {
    if (str_starts_with($uri, $p) || $uri === rtrim($p, '/')) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        return true;
    }
}
if (in_array($uri, $deniedExact, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    return true;
}
foreach ($deniedSuffixes as $s) {
    if (str_ends_with(strtolower($uri), $s)) {
        http_response_code(403);
        echo 'Forbidden';
        return true;
    }
}
// No ejecutar scripts en uploads
if (str_starts_with($uri, '/uploads/') && preg_match('/\.(php|phtml|phar|cgi|pl|py|sh)$/i', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
