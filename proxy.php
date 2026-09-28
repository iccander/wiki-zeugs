<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$url = $_GET['url'] ?? '';
$callback = $_GET['callback'] ?? '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo $callback ? $callback . '({"error":"Invalid URL"})' : '{"error":"Invalid URL"}';
    exit;
}

$params = $_GET;
unset($params['url']);
unset($params['callback']);
$separator = (strpos($url, '?') === false) ? '?' : '&';
$ch = curl_init($url . $separator . http_build_query($params));
curl_setopt($ch, CURLOPT_USERAGENT, 'QuickGND/1.0 (+https://github.com/iccander/wiki-zeugs)');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $callback ? $callback . '({"error":"HTTP ' . $httpCode . '"})' : '{"error":"HTTP ' . $httpCode . '"}';
    exit;
}

if ($callback) {
    echo $callback . $response;
} else {
    echo $response;
}
