<?php
/**
 * 压力测试专用接口（需密钥，勿在生产公开）
 * GET/POST ?key=xxx&action=ping|login_sim|stats
 */
require_once __DIR__ . '/../core/bootstrap.php';

$cfg = $GLOBALS['wm_config'];
$secret = Env::get('STRESS_TEST_KEY', $cfg['security']['api_secret'] ?? 'stress_test_key');
$key = $_REQUEST['key'] ?? '';

if ($key !== $secret) {
    Response::error('无效的压测密钥', 403);
}

$action = $_REQUEST['action'] ?? 'ping';
$start = microtime(true);

switch ($action) {
    case 'ping':
        Response::success([
            'action' => 'ping',
            'ms'     => round((microtime(true) - $start) * 1000, 2),
        ]);
        break;

    case 'db':
        $n = (int)($_REQUEST['n'] ?? 1);
        $results = [];
        for ($i = 0; $i < min($n, 50); $i++) {
            $t0 = microtime(true);
            Database::getInstance()->fetch('SELECT COUNT(*) as c FROM wm_users');
            $results[] = round((microtime(true) - $t0) * 1000, 2);
        }
        Response::success([
            'action'  => 'db',
            'queries' => count($results),
            'ms_each' => $results,
            'ms_avg'  => count($results) ? round(array_sum($results) / count($results), 2) : 0,
            'total_ms'=> round((microtime(true) - $start) * 1000, 2),
        ]);
        break;

    case 'stats':
        $db = Database::getInstance();
        $today = date('Y-m-d');
        Response::success([
            'users'      => $db->fetch('SELECT COUNT(*) as c FROM wm_users')['c'],
            'records'    => $db->fetch('SELECT COUNT(*) as c FROM wm_records')['c'],
            'today_stat' => $db->fetch('SELECT * FROM wm_daily_stats WHERE stat_date = ?', [$today]),
            'ms'         => round((microtime(true) - $start) * 1000, 2),
        ]);
        break;

    default:
        Response::error('未知 action');
}
