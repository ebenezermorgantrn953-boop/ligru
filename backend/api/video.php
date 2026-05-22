<?php
/**
 * 短视频去水印 POST
 * 参数: url (平台分享链接)
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
if (!UsageLimit::checkCanUse($user)) {
    Response::error('今日免费次数已用完', 1001, ['need_ad' => true]);
}

$url = $_POST['url'] ?? '';
if (empty($url)) {
    Response::error('请输入视频链接');
}

$db = Database::getInstance();
$recordId = $db->insert('wm_records', [
    'user_id'    => $user['id'],
    'type'       => 'video',
    'source_url' => $url,
    'status'     => 0,
]);

try {
    $result = AiService::parseVideo($url);
    $resultUrl = is_array($result) ? ($result['video_url'] ?? '') : $result;

    $db->update('wm_records', [
        'result_url' => is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : $resultUrl,
        'status'     => 1,
    ], 'id = ?', [$recordId]);

    UsageLimit::consume($user['id']);
    Stats::incrProcess('video');

    $userData = Auth::formatUser($db->fetch('SELECT * FROM wm_users WHERE id = ?', [$user['id']]));

    Response::success([
        'record_id'    => $recordId,
        'result'       => is_array($result) ? $result : ['video_url' => $resultUrl],
        'remain_count' => $userData['remain_count'],
    ]);
} catch (Exception $e) {
    $db->update('wm_records', ['status' => 2, 'remark' => $e->getMessage()], 'id = ?', [$recordId]);
    Response::error('视频解析失败，请检查链接');
}
