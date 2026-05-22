<?php
/**
 * 广告激励奖励接口 POST
 * 用户观看激励视频后调用，增加使用次数
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
$db = Database::getInstance();

$adCfg = $db->fetch("SELECT * FROM wm_ad_config WHERE ad_key = 'reward_video'");
$rewardCount = $adCfg ? (int)$adCfg['reward_count'] : 2;

UsageLimit::addRewardCount($user['id'], $rewardCount);

$user = $db->fetch('SELECT * FROM wm_users WHERE id = ?', [$user['id']]);
Response::success([
    'added'        => $rewardCount,
    'remain_count' => UsageLimit::getRemainCount($user),
], '已获得' . $rewardCount . '次额外使用机会');
