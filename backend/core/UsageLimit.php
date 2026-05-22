<?php
/**
 * 使用次数管控
 */
class UsageLimit
{
    public static function isVip($user)
    {
        if (empty($user['is_vip'])) {
            return false;
        }
        if (empty($user['vip_expire'])) {
            return false;
        }
        return strtotime($user['vip_expire']) > time();
    }

    /** 获取剩余可用次数 */
    public static function getRemainCount($user)
    {
        if (self::isVip($user)) {
            return 9999; // 会员无限
        }
        self::resetDailyIfNeeded($user);
        $user = Database::getInstance()->fetch('SELECT * FROM wm_users WHERE id = ?', [$user['id']]);
        $free = (int)($user['daily_free'] ?: self::getSetting('daily_free_count', 5));
        $used = (int)$user['today_use'];
        $extra = (int)$user['extra_count'];
        return max(0, $free + $extra - $used);
    }

    /** 检查是否可使用，不可用返回错误信息 */
    public static function checkCanUse($user)
    {
        if (self::isVip($user)) {
            return true;
        }
        $remain = self::getRemainCount($user);
        if ($remain <= 0) {
            return false;
        }
        return true;
    }

    /** 消耗一次使用次数 */
    public static function consume($userId)
    {
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM wm_users WHERE id = ?', [$userId]);
        self::resetDailyIfNeeded($user);
        $user = $db->fetch('SELECT * FROM wm_users WHERE id = ?', [$userId]);

        if (self::isVip($user)) {
            $db->query('UPDATE wm_users SET total_use = total_use + 1 WHERE id = ?', [$userId]);
            return;
        }

        $free = (int)($user['daily_free'] ?: 5);
        $used = (int)$user['today_use'];
        $extra = (int)$user['extra_count'];

        if ($used < $free) {
            $db->query(
                'UPDATE wm_users SET today_use = today_use + 1, total_use = total_use + 1, last_use_date = CURDATE() WHERE id = ?',
                [$userId]
            );
        } elseif ($extra > 0) {
            $db->query(
                'UPDATE wm_users SET today_use = today_use + 1, extra_count = extra_count - 1, total_use = total_use + 1 WHERE id = ?',
                [$userId]
            );
        }
    }

    /** 广告奖励增加次数 */
    public static function addRewardCount($userId, $count = 2)
    {
        $db = Database::getInstance();
        $db->query('UPDATE wm_users SET extra_count = extra_count + ? WHERE id = ?', [$count, $userId]);
        Stats::incr('ad_reward_count');
    }

    private static function resetDailyIfNeeded($user)
    {
        $today = date('Y-m-d');
        if ($user['last_use_date'] !== $today) {
            Database::getInstance()->update('wm_users', [
                'today_use'     => 0,
                'extra_count'   => 0,
                'last_use_date' => $today,
            ], 'id = ?', [$user['id']]);
        }
    }

    public static function getSetting($key, $default = '')
    {
        $row = Database::getInstance()->fetch('SELECT svalue FROM wm_settings WHERE skey = ?', [$key]);
        return $row ? $row['svalue'] : $default;
    }
}
