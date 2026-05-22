# AI去水印 API 压力测试脚本 (PowerShell)
# 用法: .\stress_test.ps1 -BaseUrl "https://你的域名" -Concurrent 20 -Requests 200

param(
    [string]$BaseUrl = "http://127.0.0.1:8080",
    [int]$Concurrent = 10,
    [int]$Requests = 100,
    [string]$StressKey = "change_this_secret_key_2024",
    [string]$Endpoint = "health"
)

$ErrorActionPreference = "SilentlyContinue"
$BaseUrl = $BaseUrl.TrimEnd("/")

$paths = @{
    "health" = "/api/health.php"
    "ping"   = "/api/stress.php?key=$StressKey&action=ping"
    "db"     = "/api/stress.php?key=$StressKey&action=db&n=1"
    "root"   = "/index.php"
}

$path = $paths[$Endpoint]
if (-not $path) { Write-Host "未知 Endpoint: $Endpoint"; exit 1 }
$url = "$BaseUrl$path"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " 压力测试: $url"
Write-Host " 并发: $Concurrent | 总请求: $Requests"
Write-Host "========================================" -ForegroundColor Cyan

$results = [System.Collections.Concurrent.ConcurrentBag[object]]::new()
$jobs = @()
$perJob = [math]::Ceiling($Requests / $Concurrent)

for ($j = 0; $j - $Concurrent; $j++) {
    $jobs += Start-Job -ScriptBlock {
        param($Url, $Count)
        $local = @()
        for ($i = 0; $i - $Count; $i++) {
            $sw = [System.Diagnostics.Stopwatch]::StartNew()
            try {
                $r = Invoke-WebRequest -Uri $Url -Method GET -TimeoutSec 30 -UseBasicParsing
                $sw.Stop()
                $local += [PSCustomObject]@{
                    Ok = ($r.StatusCode -eq 200)
                    Ms = $sw.ElapsedMilliseconds
                    Code = $r.StatusCode
                }
            } catch {
                $sw.Stop()
                $local += [PSCustomObject]@{
                    Ok = $false
                    Ms = $sw.ElapsedMilliseconds
                    Code = 0
                }
            }
        }
        return $local
    } -ArgumentList $url, $perJob
}

Write-Host "测试中..." -NoNewline
$jobs | Wait-Job | Out-Null
Write-Host " 完成`n"

foreach ($job in $jobs) {
    $data = Receive-Job $job
    foreach ($d in $data) { $results.Add($d) }
    Remove-Job $job
}

$all = @($results)
$ok = @($all | Where-Object { $_.Ok })
$fail = $all.Count - $ok.Count
$ms = @($ok | ForEach-Object { $_.Ms })

if ($ms.Count -eq 0) {
    Write-Host "全部失败，请检查 URL 与服务是否启动" -ForegroundColor Red
    exit 1
}

$sorted = $ms | Sort-Object
$p50 = $sorted[[int]($sorted.Count * 0.5)]
$p95 = $sorted[[int]($sorted.Count * 0.95)]
$p99 = $sorted[[int]($sorted.Count * 0.99)]

Write-Host "结果汇总" -ForegroundColor Green
Write-Host "  总请求:   $($all.Count)"
Write-Host "  成功:     $($ok.Count)"
Write-Host "  失败:     $fail"
Write-Host "  成功率:   $([math]::Round($ok.Count / $all.Count * 100, 2))%"
Write-Host "  QPS:      $([math]::Round($ok.Count / (($ms | Measure-Object -Sum).Sum / 1000 / $Concurrent), 2)) (估算)"
Write-Host "  平均延迟: $([math]::Round(($ms | Measure-Object -Average).Average, 2)) ms"
Write-Host "  P50:      $p50 ms"
Write-Host "  P95:      $p95 ms"
Write-Host "  P99:      $p99 ms"
Write-Host "  最小/最大: $($sorted[0]) / $($sorted[-1]) ms"

$reportPath = Join-Path $PSScriptRoot "report_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
@"
压力测试报告
时间: $(Get-Date)
URL: $url
并发: $Concurrent  请求: $($all.Count)
成功: $($ok.Count)  失败: $fail
平均: $([math]::Round(($ms | Measure-Object -Average).Average, 2))ms  P95: $p95 ms  P99: $p99 ms
"@ | Out-File $reportPath -Encoding UTF8
Write-Host "`n报告已保存: $reportPath" -ForegroundColor Gray
