<?php
/**
 * 健康检查接口（压力测试探针，无需登录）
 */
require_once __DIR__ . '/../core/bootstrap.php';
// 健康检查不限流

$start = microtime(true);
$dbOk = false;
$dbMs = 0;

try {
    $t0 = microtime(true);
    Database::getInstance()->fetch('SELECT 1');
    $dbMs = round((microtime(true) - $t0) * 1000, 2);
    $dbOk = true;
} catch (Exception $e) {
    $dbMs = -1;
}

$elapsed = round((microtime(true) - $start) * 1000, 2);

Response::success([
    'status'       => 'ok',
    'cloud'        => Env::isCloudRun(),
    'database'     => $dbOk ? 'up' : 'down',
    'db_ms'        => $dbMs,
    'response_ms'  => $elapsed,
    'php_version'  => PHP_VERSION,
    'gd_enabled'   => extension_loaded('gd'),
    'time'         => date('Y-m-d H:i:s'),
]);
