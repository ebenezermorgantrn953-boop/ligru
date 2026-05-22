<?php
/**
 * 创建会员支付订单 POST
 * 参数: plan_type = month|quarter|year
 */
require_once __DIR__ . '/../core/bootstrap.php';
Security::rateLimit();

$user = Auth::user();
$planType = $_POST['plan_type'] ?? '';
$result = WxPay::createOrder($user['id'], $planType);
Response::success($result);
