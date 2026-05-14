<?php
// Router pel PHP built-in server (php -S -t public public/router.php)
// No tocar $_GET, $_SERVER['REQUEST_METHOD'] ni php://input: el built-in server ja els passa.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Rutes API: despatxar a fitxers fora de public/
if (strpos($uri, '/api/') === 0) {
    // Mapeig directe: /api/todos.php -> ../api/todos.php
    $apiFile = __DIR__ . '/..' . $uri;
    $real = realpath($apiFile);
    $apiBase = realpath(__DIR__ . '/../api');
    if ($real !== false && $apiBase !== false && strpos($real, $apiBase) === 0 && is_file($real)) {
        require $real;
        return true;
    }
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    return true;
}

// Si el fitxer estàtic existeix dins public/, deixa que el built-in server el serveixi
$staticFile = __DIR__ . $uri;
if ($uri !== '/' && is_file($staticFile)) {
    return false;
}

// Fallback: serveix index.html
$index = __DIR__ . '/index.html';
if (is_file($index)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($index);
    return true;
}

http_response_code(404);
return true;
