<?php
/**
 * 简易后台 - 数据统计查看
 * 访问: /admin/index.php  默认密码见 wm_settings.admin_password
 */
session_start();
require_once __DIR__ . '/../core/bootstrap.php';

$db = Database::getInstance();
$adminPwd = UsageLimit::getSetting('admin_password', 'admin123');

// 登录处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPwd) {
        $_SESSION['wm_admin'] = true;
    }
}
if (isset($_GET['logout'])) {
    unset($_SESSION['wm_admin']);
    header('Location: index.php');
    exit;
}

$loggedIn = !empty($_SESSION['wm_admin']);

if (!$loggedIn) {
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>后台登录 - AI去水印</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea,#764ba2);font-family:-apple-system,sans-serif}
.card{background:rgba(255,255,255,.95);padding:40px;border-radius:20px;width:360px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
h2{text-align:center;margin-bottom:24px;color:#333}
input{width:100%;padding:12px 16px;border:2px solid #e8e8e8;border-radius:12px;font-size:15px;margin-bottom:16px}
input:focus{outline:none;border-color:#667eea}
button{width:100%;padding:14px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:12px;font-size:16px;cursor:pointer}
</style>
</head>
<body>
<div class="card">
<h2>管理后台</h2>
<form method="post">
<input type="password" name="password" placeholder="请输入管理密码" required>
<button type="submit">登录</button>
</form>
</div>
</body>
</html>
    <?php
    exit;
}

// 统计数据
$today = date('Y-m-d');
$todayStats = $db->fetch('SELECT * FROM wm_daily_stats WHERE stat_date = ?', [$today]) ?: [];
$totalUsers = $db->fetch('SELECT COUNT(*) as cnt FROM wm_users')['cnt'];
$totalRecords = $db->fetch('SELECT COUNT(*) as cnt FROM wm_records')['cnt'];
$recentStats = $db->fetchAll('SELECT * FROM wm_daily_stats ORDER BY stat_date DESC LIMIT 7');
$recentUsers = $db->fetchAll('SELECT id,nickname,total_use,is_vip,created_at FROM wm_users ORDER BY id DESC LIMIT 10');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>数据统计 - AI去水印后台</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,sans-serif;background:#f0f2f5;padding:20px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.header h1{color:#333;font-size:22px}
.header a{color:#667eea;text-decoration:none}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#fff;padding:20px;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.stat-card .label{color:#999;font-size:13px;margin-bottom:8px}
.stat-card .value{font-size:28px;font-weight:700;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.panel{background:#fff;border-radius:16px;padding:20px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.panel h3{margin-bottom:16px;color:#333}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #f0f0f0}
th{color:#999;font-weight:500}
</style>
</head>
<body>
<div class="header">
<h1>AI智能去水印 - 数据统计</h1>
<a href="?logout=1">退出登录</a>
</div>
<div class="stats">
<div class="stat-card"><div class="label">总用户数</div><div class="value"><?= $totalUsers ?></div></div>
<div class="stat-card"><div class="label">总处理量</div><div class="value"><?= $totalRecords ?></div></div>
<div class="stat-card"><div class="label">今日访问</div><div class="value"><?= $todayStats['pv'] ?? 0 ?></div></div>
<div class="stat-card"><div class="label">今日图片处理</div><div class="value"><?= $todayStats['image_count'] ?? 0 ?></div></div>
<div class="stat-card"><div class="label">今日视频解析</div><div class="value"><?= $todayStats['video_count'] ?? 0 ?></div></div>
<div class="stat-card"><div class="label">今日新增用户</div><div class="value"><?= $todayStats['new_users'] ?? 0 ?></div></div>
</div>
<div class="panel">
<h3>近7日数据</h3>
<table>
<tr><th>日期</th><th>访问</th><th>新用户</th><th>图片</th><th>视频</th><th>涂抹</th><th>广告奖励</th><th>订单</th></tr>
<?php foreach ($recentStats as $s): ?>
<tr>
<td><?= $s['stat_date'] ?></td>
<td><?= $s['pv'] ?></td>
<td><?= $s['new_users'] ?></td>
<td><?= $s['image_count'] ?></td>
<td><?= $s['video_count'] ?></td>
<td><?= $s['brush_count'] ?></td>
<td><?= $s['ad_reward_count'] ?></td>
<td><?= $s['order_count'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<div class="panel">
<h3>最近注册用户</h3>
<table>
<tr><th>ID</th><th>昵称</th><th>累计使用</th><th>会员</th><th>注册时间</th></tr>
<?php foreach ($recentUsers as $u): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= htmlspecialchars($u['nickname']) ?></td>
<td><?= $u['total_use'] ?></td>
<td><?= $u['is_vip'] ? '是' : '否' ?></td>
<td><?= $u['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
