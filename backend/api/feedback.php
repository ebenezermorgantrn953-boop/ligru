<?php
/**
 * 问题反馈 POST
 * 参数: content, contact
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
$content = trim($_POST['content'] ?? '');
if (empty($content)) {
    Response::error('请填写反馈内容');
}

Database::getInstance()->insert('wm_feedback', [
    'user_id' => $user['id'],
    'content' => $content,
    'contact' => $_POST['contact'] ?? '',
]);

Response::success(null, '反馈已提交，感谢您的建议');
