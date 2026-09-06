<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$logFile = __DIR__ . '/log.txt';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!is_file($logFile)) {
    echo "Chưa có log cron. File log.txt sẽ xuất hiện sau khi cron_traffic_report.php chạy.\n";
    exit;
}

readfile($logFile);
