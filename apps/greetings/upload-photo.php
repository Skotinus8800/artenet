<?php
// apps/greetings/upload-photo.php

header('Content-Type: application/json');

$targetDir = __DIR__ . '/photos/';
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!isset($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['photo'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Недопустимый формат файла']);
    exit;
}

$filename = uniqid('greet_', true) . '.' . $ext;
$targetFile = $targetDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    // Путь для доступа из браузера
    $url = '/apps/greetings/photos/' . $filename;
    echo json_encode(['url' => $url]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка загрузки файла']);
}