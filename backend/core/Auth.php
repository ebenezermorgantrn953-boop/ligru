<?php
/**
 * 用户认证与微信登录
 */
class Auth
{
    /**
     * 微信 code 换 openid 并登录
     */
    public static function wxLogin($code, $userInfo = [])
    {
        $cfg = $GLOBALS['wm_config']['wechat'];
        if (empty($cfg['appid']) || $cfg['appid'] === 'your_appid') {
            // 开发模式：模拟登录
            return self::devLogin($userInfo);
        }

        $url = sprintf(
            'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
            $cfg['appid'],
            $cfg['secret'],
            $code
        );

        $res = self::httpGet($url);
        $data = json_decode($res, true);

        if (empty($data['openid'])) {
            Response::error('微信登录失败: ' . ($data['errmsg'] ?? '未知错误'));
        }

        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM wm_users WHERE openid = ?', [$data['openid']]);

        $token = bin2hex(random_bytes(16));
        $nickname = $userInfo['nickName'] ?? '微信用户';
        $avatar = $userInfo['avatarUrl'] ?? '';

        if ($user) {
            $db->update('wm_users', [
                'nickname'    => $nickname,
                'avatar'      => $avatar,
                'session_key' => $data['session_key'] ?? '',
                'token'       => $token,
            ], 'id = ?', [$user['id']]);
            $userId = $user['id'];
        } else {
            $userId = $db->insert('wm_users', [
                'openid'      => $data['openid'],
                'unionid'     => $data['unionid'] ?? '',
                'nickname'    => $nickname,
                'avatar'      => $avatar,
                'session_key' => $data['session_key'] ?? '',
                'token'       => $token,
            ]);
            Stats::incr('new_users');
        }

        $user = $db->fetch('SELECT * FROM wm_users WHERE id = ?', [$userId]);
        Stats::incr('pv');

        return self::formatUser($user);
    }

    /** 开发环境模拟登录 */
    private static function devLogin($userInfo)
    {
        $db = Database::getInstance();
        $openid = 'dev_' . md5($userInfo['nickName'] ?? 'test');
        $user = $db->fetch('SELECT * FROM wm_users WHERE openid = ?', [$openid]);
        $token = bin2hex(random_bytes(16));

        if ($user) {
            $db->update('wm_users', ['token' => $token], 'id = ?', [$user['id']]);
        } else {
            $db->insert('wm_users', [
                'openid'   => $openid,
                'nickname' => $userInfo['nickName'] ?? '测试用户',
                'avatar'   => $userInfo['avatarUrl'] ?? '',
                'token'    => $token,
            ]);
        }
        $user = $db->fetch('SELECT * FROM wm_users WHERE openid = ?', [$openid]);
        return self::formatUser($user);
    }

    /** 通过 token 获取当前用户 */
    public static function user()
    {
        $token = self::getToken();
        if (!$token) {
            Response::error('请先登录', 401);
        }
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM wm_users WHERE token = ? AND status = 1', [$token]);
        if (!$user) {
            Response::error('登录已过期，请重新登录', 401);
        }
        return $user;
    }

    public static function getToken()
    {
        $headers = getallheaders();
        if (!empty($headers['X-Token'])) {
            return $headers['X-Token'];
        }
        if (!empty($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        return $_REQUEST['token'] ?? '';
    }

    public static function formatUser($user)
    {
        $isVip = UsageLimit::isVip($user);
        $remain = UsageLimit::getRemainCount($user);

        return [
            'id'           => (int)$user['id'],
            'nickname'     => $user['nickname'],
            'avatar'       => $user['avatar'],
            'token'        => $user['token'],
            'is_vip'       => $isVip,
            'vip_expire'   => $user['vip_expire'],
            'remain_count' => $remain,
            'total_use'    => (int)$user['total_use'],
            'today_use'    => (int)$user['today_use'],
        ];
    }

    private static function httpGet($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}
