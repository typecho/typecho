<?php
/**
 * Echo fixture for tests/Unit/HttpClientTest.php, served by PHP's built-in web server.
 * Reports the received request back as JSON so the test can assert exactly what
 * Client put on the wire; special paths simulate slow, redirecting and failing endpoints.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ('/slow' === $path) {
    sleep(2);
}

if ('/redirect' === $path) {
    header('Location: /target', true, 302);
    exit;
}

if (1 === preg_match('#^/status/(\d{3})$#', $path, $matches)) {
    http_response_code((int) $matches[1]);
}

$headers = [];

foreach ($_SERVER as $key => $value) {
    if (0 === strpos($key, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
    }
}

if (isset($_SERVER['CONTENT_TYPE'])) {
    $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
}

$files = [];

foreach ($_FILES as $name => $file) {
    $files[$name] = [
        'name' => $file['name'],
        'size' => $file['size'],
        'content' => is_uploaded_file($file['tmp_name']) ? file_get_contents($file['tmp_name']) : null,
    ];
}

header('Content-Type: application/json');
header('X-Router: typecho-http-fixture');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'query' => $_GET,
    'post' => $_POST,
    'files' => $files,
    'raw' => file_get_contents('php://input'),
    'headers' => $headers,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
