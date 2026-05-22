<?php
/**
 * 核心引导文件
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('WM_ROOT', dirname(__DIR__));
define('WM_CORE', WM_ROOT . '/core');

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// 自动加载
spl_autoload_register(function ($class) {
    $file = WM_CORE . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$config = require WM_ROOT . '/config/config.php';
if (file_exists(WM_ROOT . '/config/config.cloud.php')) {
    $config = array_replace_recursive($config, require WM_ROOT . '/config/config.cloud.php');
}
$config = Env::mergeCloudConfig($config);

$aiConfig = require WM_ROOT . '/config/ai.php';
$wechatConfig = file_exists(WM_ROOT . '/config/wechat.php')
    ? require WM_ROOT . '/config/wechat.php'
    : [];

// 合并微信配置
if (!empty($wechatConfig['appid'])) {
    $config['wechat']['appid'] = $wechatConfig['appid'];
    $config['wechat']['secret'] = $wechatConfig['secret'];
}
if (!empty($wechatConfig['pay'])) {
    $config['wechat'] = array_merge($config['wechat'], $wechatConfig['pay']);
}

// CORS 支持小程序跨域
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Token, X-WX-SERVICE, X-WX-OPENID');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 全局配置
$GLOBALS['wm_config'] = $config;
$GLOBALS['wm_ai_config'] = $aiConfig;

// 确保上传目录存在
$uploadDir = $config['site']['upload_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
foreach (['images', 'videos', 'masks', 'results'] as $sub) {
    $path = $uploadDir . $sub;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}
