<?php
/**
 * 图片去水印 / 手动涂抹去水印 POST
 * 参数: type=image|brush, image_url, mask_url(brush必填)
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
if (!UsageLimit::checkCanUse($user)) {
    Response::error('今日免费次数已用完', 1001, ['need_ad' => true]);
}

$type = $_POST['type'] ?? 'image';
$imageUrl = $_POST['image_url'] ?? '';
$maskUrl = $_POST['mask_url'] ?? '';

if (empty($imageUrl)) {
    Response::error('请上传图片');
}
$imageUrl = Security::sanitizeUrl($imageUrl);

$db = Database::getInstance();
$recordId = $db->insert('wm_records', [
    'user_id'    => $user['id'],
    'type'       => $type === 'brush' ? 'brush' : 'image',
    'source_url' => $imageUrl,
    'status'     => 0,
]);

try {
    if ($type === 'brush') {
        if (empty($maskUrl)) {
            Response::error('请绘制涂抹区域');
        }
        $resultUrl = AiService::brushRemove($imageUrl, $maskUrl);
    } else {
        $resultUrl = AiService::removeImageWatermark($imageUrl);
    }

    $db->update('wm_records', [
        'result_url' => is_string($resultUrl) ? $resultUrl : json_encode($resultUrl),
        'status'     => 1,
    ], 'id = ?', [$recordId]);

    UsageLimit::consume($user['id']);
    Stats::incrProcess($type === 'brush' ? 'brush' : 'image');

    $user = Auth::formatUser($db->fetch('SELECT * FROM wm_users WHERE id = ?', [$user['id']]));

    Response::success([
        'record_id'  => $recordId,
        'result_url' => is_string($resultUrl) ? $resultUrl : $resultUrl,
        'remain_count' => $user['remain_count'],
    ]);
} catch (Exception $e) {
    $db->update('wm_records', ['status' => 2, 'remark' => $e->getMessage()], 'id = ?', [$recordId]);
    Response::error('处理失败，请重试');
}
