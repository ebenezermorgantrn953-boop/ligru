<?php
/**
 * 微信登录接口 POST
 * 参数: code, nickName, avatarUrl
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$code = $_POST['code'] ?? '';
if (empty($code)) {
    Response::error('缺少登录凭证code');
}

$userInfo = [
    'nickName'  => $_POST['nickName'] ?? '微信用户',
    'avatarUrl' => $_POST['avatarUrl'] ?? '',
];

$user = Auth::wxLogin($code, $userInfo);
Response::success($user, '登录成功');
