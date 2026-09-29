<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}
$url = $_GET['url'] ?? '';
if ($url !== 'https://lobid.org/gnd/search') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or unauthorized URL']);
    exit;
}
$params = $_GET;
unset($params['url']);
$ch = curl_init($url.'?'.http_build_query($params));
curl_setopt_array($ch,[
    CURLOPT_USERAGENT      => 'QuickGND/1.0 (+https://github.com/iccander/wiki-zeugs)',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => false
]);
$response = curl_exec($ch);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Proxy request failed']);
    curl_close($ch);
    exit;
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'HTTP '.$httpCode]);
    exit;
}
echo $response;
