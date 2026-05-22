<?php
/**
 * 云托管入口 / 健康检查
 */
header('Content-Type: application/json; charset=utf-8');

$health = [
    'status'  => 'ok',
    'service' => 'ai-watermark-api',
    'version' => '1.0.0',
    'time'    => date('Y-m-d H:i:s'),
    'php'     => PHP_VERSION,
    'cloud'   => getenv('WX_CLOUD_RUN') ?: (getenv('MYSQL_ADDRESS') ? 'auto' : 'local'),
];

// 快速检测数据库（可选）
try {
    require_once __DIR__ . '/core/bootstrap.php';
    Database::getInstance()->fetch('SELECT 1 as ok');
    $health['database'] = 'connected';
} catch (Exception $e) {
    $health['database'] = 'error';
    $health['db_msg'] = $GLOBALS['wm_config']['debug'] ?? false ? $e->getMessage() : 'check config';
}

echo json_encode($health, JSON_UNESCAPED_UNICODE);
