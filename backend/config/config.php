<?php
/**
 * 主配置文件 - 部署时修改此文件
 */
return [
    // 数据库配置
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'ai_watermark',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // 站点配置
    'site' => [
        'name'       => 'AI智能去水印',
        'url'        => 'https://your-domain.com',  // 后端域名，末尾不要斜杠
        'upload_dir' => __DIR__ . '/../uploads/',
        'upload_url' => '/uploads/',
    ],

    // 安全配置
    'security' => [
        'api_secret'    => 'change_this_secret_key_2024',  // API签名密钥
        'token_expire'  => 86400 * 30,  // token有效期(秒)
        'max_upload_mb' => 10,          // 最大上传MB
        'allowed_image' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_video' => ['mp4', 'mov'],
        'rate_limit'    => 60,  // 每分钟最大请求数
    ],

    // 微信小程序配置 (也可在 wechat.php 单独配置)
    'wechat' => [
        'appid'  => 'your_appid',
        'secret' => 'your_secret',
        'mch_id' => 'your_mch_id',           // 商户号
        'pay_key' => 'your_api_v2_key',      // 支付密钥
        'notify_url' => '/api/pay_notify.php',
    ],

    // 调试模式
    'debug' => false,
];
