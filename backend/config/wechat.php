<?php
/**
 * 微信相关配置
 */
return [
    'appid'  => 'your_mini_program_appid',
    'secret' => 'your_mini_program_secret',

    // 微信支付
    'pay' => [
        'mch_id'    => 'your_merchant_id',
        'api_key'   => 'your_api_v2_key',
        'notify_url'=> '', // 留空则自动拼接站点域名
    ],
];
