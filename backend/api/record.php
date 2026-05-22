<?php
/**
 * 历史记录接口 GET
 * 参数: page=1, limit=20
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$db = Database::getInstance();
$total = $db->fetch('SELECT COUNT(*) as cnt FROM wm_records WHERE user_id = ?', [$user['id']])['cnt'];
$list = $db->fetchAll(
    'SELECT id, type, source_url, result_url, status, created_at FROM wm_records WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
    [$user['id']]
);

foreach ($list as &$item) {
    if ($item['type'] === 'video' && $item['result_url']) {
        $decoded = json_decode($item['result_url'], true);
        if ($decoded) {
            $item['result'] = $decoded;
        }
    }
}

Response::success([
    'list'  => $list,
    'total' => (int)$total,
    'page'  => $page,
]);
