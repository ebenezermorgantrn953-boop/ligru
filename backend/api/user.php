<?php
/**
 * 用户信息接口 GET
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
$data = Auth::formatUser($user);

// 会员套餐价格
$data['plans'] = [
    'month'   => ['name' => '月卡会员', 'price' => UsageLimit::getSetting('vip_month_price', '9.90'),   'days' => UsageLimit::getSetting('vip_month_days', 30)],
    'quarter' => ['name' => '季卡会员', 'price' => UsageLimit::getSetting('vip_quarter_price', '24.90'), 'days' => UsageLimit::getSetting('vip_quarter_days', 90)],
    'year'    => ['name' => '年卡会员', 'price' => UsageLimit::getSetting('vip_year_price', '68.00'),    'days' => UsageLimit::getSetting('vip_year_days', 365)],
];

// 广告配置(供前端读取)
$db = Database::getInstance();
$ads = $db->fetchAll('SELECT ad_key, enabled, ad_unit_id, show_interval, daily_limit, reward_count FROM wm_ad_config');
$data['ad_config'] = $ads;

Response::success($data);
