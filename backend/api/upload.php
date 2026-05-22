<?php
/**
 * 文件上传接口 POST multipart
 * 参数: file, type=image|video|mask
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();
Auth::user();

if (empty($_FILES['file'])) {
    Response::error('请选择文件');
}

$type = $_POST['type'] ?? 'image';
$subdirMap = ['image' => 'images', 'video' => 'videos', 'mask' => 'masks'];
$subdir = $subdirMap[$type] ?? 'images';

if ($type === 'mask' && !empty($_POST['base64'])) {
    $result = Upload::saveBase64($_POST['base64'], 'masks');
    Response::success($result);
}

$file = $_FILES['file'];
$result = Upload::save($file, $subdir);
Response::success($result);
