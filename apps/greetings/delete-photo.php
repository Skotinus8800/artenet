<?php
// apps/greetings/delete-photo.php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['url'])) { http_response_code(400); exit; }
$path = $_SERVER['DOCUMENT_ROOT'] . $data['url'];
if (file_exists($path)) unlink($path);
echo json_encode(['success' => true]);