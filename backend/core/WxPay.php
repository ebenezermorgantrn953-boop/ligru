<?php
/**
 * 微信支付
 */
class WxPay
{
    public static function createOrder($userId, $planType)
    {
        $plans = [
            'month'   => ['days' => (int)UsageLimit::getSetting('vip_month_days', 30),   'price' => UsageLimit::getSetting('vip_month_price', '9.90')],
            'quarter' => ['days' => (int)UsageLimit::getSetting('vip_quarter_days', 90), 'price' => UsageLimit::getSetting('vip_quarter_price', '24.90')],
            'year'    => ['days' => (int)UsageLimit::getSetting('vip_year_days', 365),  'price' => UsageLimit::getSetting('vip_year_price', '68.00')],
        ];

        if (!isset($plans[$planType])) {
            Response::error('无效的套餐类型');
        }

        $plan = $plans[$planType];
        $orderNo = 'WM' . date('YmdHis') . mt_rand(1000, 9999);
        $amount = (float)$plan['price'];

        $db = Database::getInstance();
        $db->insert('wm_orders', [
            'order_no'  => $orderNo,
            'user_id'   => $userId,
            'plan_type' => $planType,
            'amount'    => $amount,
            'days'      => $plan['days'],
        ]);

        $user = $db->fetch('SELECT openid FROM wm_users WHERE id = ?', [$userId]);
        $cfg = $GLOBALS['wm_config']['wechat'];

        // 开发模式返回模拟支付参数
        if ($cfg['mch_id'] === 'your_mch_id' || empty($cfg['mch_id'])) {
            return [
                'order_no' => $orderNo,
                'dev_mode' => true,
                'message'  => '请配置微信支付后使用真实支付',
            ];
        }

        $params = self::unifiedOrder($orderNo, $user['openid'], $amount, $cfg);
        return array_merge(['order_no' => $orderNo], $params);
    }

    /** 支付成功回调处理 */
    public static function handleNotify($xml)
    {
        $data = self::xmlToArray($xml);
        if ($data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            return self::notifyResponse('FAIL', '支付失败');
        }

        $db = Database::getInstance();
        $order = $db->fetch('SELECT * FROM wm_orders WHERE order_no = ? AND pay_status = 0', [$data['out_trade_no']]);
        if (!$order) {
            return self::notifyResponse('SUCCESS', 'OK');
        }

        $db->update('wm_orders', [
            'pay_status'     => 1,
            'transaction_id' => $data['transaction_id'],
            'paid_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$order['id']]);

        // 开通会员
        $user = $db->fetch('SELECT * FROM wm_users WHERE id = ?', [$order['user_id']]);
        $expire = self::calcVipExpire($user['vip_expire'], $order['days']);
        $db->update('wm_users', [
            'is_vip'     => 1,
            'vip_expire' => $expire,
        ], 'id = ?', [$order['user_id']]);

        Stats::incr('order_count');
        Stats::incr('order_amount', $order['amount']);

        return self::notifyResponse('SUCCESS', 'OK');
    }

    private static function calcVipExpire($currentExpire, $days)
    {
        $base = (!empty($currentExpire) && strtotime($currentExpire) > time())
            ? strtotime($currentExpire)
            : time();
        return date('Y-m-d H:i:s', $base + $days * 86400);
    }

    private static function unifiedOrder($orderNo, $openid, $amount, $cfg)
    {
        $params = [
            'appid'            => $cfg['appid'],
            'mch_id'           => $cfg['mch_id'],
            'nonce_str'        => self::nonceStr(),
            'body'             => 'AI去水印会员',
            'out_trade_no'     => $orderNo,
            'total_fee'        => (int)($amount * 100),
            'spbill_create_ip' => Security::getClientIp(),
            'notify_url'       => rtrim($GLOBALS['wm_config']['site']['url'], '/') . ($cfg['notify_url'] ?? '/api/pay_notify.php'),
            'trade_type'       => 'JSAPI',
            'openid'           => $openid,
        ];
        $params['sign'] = self::sign($params, $cfg['pay_key']);

        $xml = self::arrayToXml($params);
        $res = self::postXml('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $result = self::xmlToArray($res);

        if ($result['return_code'] !== 'SUCCESS') {
            Response::error('创建支付订单失败');
        }

        $payParams = [
            'appId'     => $cfg['appid'],
            'timeStamp' => (string)time(),
            'nonceStr'  => self::nonceStr(),
            'package'   => 'prepay_id=' . $result['prepay_id'],
            'signType'  => 'MD5',
        ];
        $payParams['paySign'] = self::sign($payParams, $cfg['pay_key']);

        return ['pay_params' => $payParams];
    }

    private static function sign($params, $key)
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($k !== 'sign' && $v !== '') {
                $str .= "{$k}={$v}&";
            }
        }
        $str .= 'key=' . $key;
        return strtoupper(md5($str));
    }

    private static function nonceStr($len = 32)
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, $len);
    }

    private static function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $k => $v) {
            $xml .= "<{$k}><![CDATA[{$v}]]></{$k}>";
        }
        return $xml . '</xml>';
    }

    private static function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    private static function postXml($url, $xml)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    private static function notifyResponse($code, $msg)
    {
        return '<xml><return_code><![CDATA[' . $code . ']]></return_code><return_msg><![CDATA[' . $msg . ']]></return_msg></xml>';
    }
}
