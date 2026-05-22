<?php
/**
 * DeepSeek API 配置模板
 * 复制为 deepseek.php 并填入密钥，或通过云托管环境变量 DEEPSEEK_API_KEY 配置
 */
return [
    'api_key' => 'sk-your-deepseek-api-key',
    'base_url' => 'https://api.deepseek.com',
    'model' => 'deepseek-v4-flash',
    'thinking' => ['type' => 'disabled'],
    'timeout' => 90,
];
