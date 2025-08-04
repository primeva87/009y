<?php
// Simple test script to diagnose issues
header('Content-Type: application/json');

echo json_encode(array(
    'status' => 'PHP is working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'get_data' => $_GET,
    'server_info' => array(
        'php_version' => phpversion(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    )
));
?>