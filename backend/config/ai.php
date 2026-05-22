<?php
/**
 * AI接口配置
 *
 * provider 可选:
 *   - deepseek  : 使用 DeepSeek API（推荐，见 config/deepseek.php）
 *   - custom    : 使用下方自定义 HTTP 接口
 *   - mock      : 演示模式，不调用真实 AI
 */
return [
    // 是否启用 AI（false = 演示模式）
    'enabled' => true,

    // AI 提供商: deepseek | custom | mock
    'provider' => 'deepseek',

    // ========== DeepSeek 配置 ==========
    // 密钥与模型请在 config/deepseek.php 中填写

    // ========== 自定义第三方 API（provider=custom 时生效）==========
    'image_remove' => [
        'url'    => 'https://api.example.com/v1/image/inpaint',
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY',
            'Content-Type'  => 'application/json',
        ],
        'body_template' => [
            'image' => '{image_url}',
            'mask'  => '{mask_url}',
        ],
        'result_field' => 'data.result_url',
    ],

    'brush_remove' => [
        'url'    => 'https://api.example.com/v1/image/inpaint',
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY',
        ],
        'body_template' => [
            'image' => '{image_url}',
            'mask'  => '{mask_url}',
        ],
        'result_field' => 'data.result_url',
    ],

    'video_parse' => [
        'url'    => 'https://api.example.com/v1/video/parse',
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY',
        ],
        'body_template' => [
            'url' => '{video_url}',
        ],
        'result_field' => 'data.video_url',
    ],

    'timeout' => 90,
];
