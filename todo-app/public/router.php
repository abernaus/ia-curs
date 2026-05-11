<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/api/#', $uri)) {
    require __DIR__ . '/../api/todos.php';
    return true;
}
$path = __DIR__ . $uri;
if (is_file($path)) {
    return false;
}
// Default: serve index.html
require __DIR__ . '/index.html';
return true;
