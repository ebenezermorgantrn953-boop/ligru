<?php
/**
 * 微信支付回调通知
 */
require_once __DIR__ . '/../core/bootstrap.php';

$xml = file_get_contents('php://input');
echo WxPay::handleNotify($xml);
