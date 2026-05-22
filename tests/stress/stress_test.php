<?php
/**
 * CLI 压力测试: php stress_test.php [base_url] [concurrent] [requests]
 * 示例: php stress_test.php http://127.0.0.1:8080 20 200 health
 */
$baseUrl = $argv[1] ?? 'http://127.0.0.1:8080';
$concurrent = (int)($argv[2] ?? 10);
$total = (int)($argv[3] ?? 100);
$endpoint = $argv[4] ?? 'health';
$key = $argv[5] ?? 'change_this_secret_key_2024';

$paths = [
    'health' => '/api/health.php',
    'ping'   => '/api/stress.php?key=' . urlencode($key) . '&action=ping',
    'db'     => '/api/stress.php?key=' . urlencode($key) . '&action=db&n=1',
];
$url = rtrim($baseUrl, '/') . ($paths[$endpoint] ?? '/api/health.php');

echo "Stress Test: $url\n";
echo "Concurrent: $concurrent, Total: $total\n\n";

$results = [];
$perProc = (int)ceil($total / $concurrent);
$pids = [];

for ($i = 0; $i < $concurrent; $i++) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        // Windows 无 pcntl，单进程顺序测
        break;
    } elseif ($pid == 0) {
        $local = [];
        for ($j = 0; $j < $perProc; $j++) {
            $t0 = microtime(true);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $ms = (microtime(true) - $t0) * 1000;
            $local[] = ['ok' => $code === 200, 'ms' => $ms];
        }
        file_put_contents(sys_get_temp_dir() . '/wm_stress_' . getmypid() . '.json', json_encode($local));
        exit(0);
    } else {
        $pids[] = $pid;
    }
}

if (empty($pids)) {
    // 无 fork：单线程
    for ($i = 0; $i < $total; $i++) {
        $t0 = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $results[] = ['ok' => $code === 200, 'ms' => (microtime(true) - $t0) * 1000];
    }
} else {
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
    foreach (glob(sys_get_temp_dir() . '/wm_stress_*.json') as $f) {
        $results = array_merge($results, json_decode(file_get_contents($f), true));
        unlink($f);
    }
}

$ok = array_filter($results, fn($r) => $r['ok']);
$ms = array_column($ok, 'ms');
sort($ms);
$n = count($ms);

echo "Total: " . count($results) . "\n";
echo "Success: " . count($ok) . "\n";
echo "Fail: " . (count($results) - count($ok)) . "\n";
if ($n > 0) {
    echo "Avg: " . round(array_sum($ms) / $n, 2) . " ms\n";
    echo "P50: " . $ms[(int)($n * 0.5)] . " ms\n";
    echo "P95: " . $ms[(int)($n * 0.95)] . " ms\n";
    echo "P99: " . $ms[min((int)($n * 0.99), $n - 1)] . " ms\n";
}
