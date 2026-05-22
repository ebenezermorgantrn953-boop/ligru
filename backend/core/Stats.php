<?php
/**
 * 数据统计
 */
class Stats
{
    public static function incr($field, $amount = 1)
    {
        $today = date('Y-m-d');
        $db = Database::getInstance();
        $row = $db->fetch('SELECT id FROM wm_daily_stats WHERE stat_date = ?', [$today]);
        if (!$row) {
            $db->insert('wm_daily_stats', ['stat_date' => $today, $field => $amount]);
        } else {
            $db->query("UPDATE wm_daily_stats SET {$field} = {$field} + ? WHERE stat_date = ?", [$amount, $today]);
        }
    }

    public static function incrProcess($type)
    {
        $map = ['image' => 'image_count', 'video' => 'video_count', 'brush' => 'brush_count'];
        if (isset($map[$type])) {
            self::incr($map[$type]);
        }
    }
}
