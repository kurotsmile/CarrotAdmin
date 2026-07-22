<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require __DIR__ . '/../CarrotCoc/config/database.php';
require __DIR__ . '/includes/schema.php';
require __DIR__ . '/includes/traffic_report.php';

$isCli = PHP_SAPI === 'cli';
$cliOptions = $isCli ? getopt('', ['through-date::', 'delete-raw::']) : [];
$tokenFile = __DIR__ . '/config/traffic_cron_token.php';
$cronToken = '';
if (is_file($tokenFile)) {
    require $tokenFile;
    $cronToken = (string) ($traffic_cron_token ?? '');
}

if (!$isCli) {
    $token = (string) ($_GET['token'] ?? '');
    if ($cronToken === '' || !hash_equals($cronToken, $token)) {
        http_response_code(403);
        echo "Forbidden\n";
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');

$deleteRawValue = $isCli ? (string) ($cliOptions['delete-raw'] ?? '1') : (string) ($_GET['delete_raw'] ?? '1');
$deleteRaw = !in_array($deleteRawValue, ['0', 'false', 'no'], true);
$throughDate = trim($isCli ? (string) ($cliOptions['through-date'] ?? '') : (string) ($_GET['through_date'] ?? '')) ?: null;
$databases = [];

if (isset($pdo) && $pdo instanceof PDO) {
    $databases['main'] = $pdo;
}

try {
    $homePdo = admin_home_pdo_for_traffic_cron();
    if ($homePdo instanceof PDO) {
        $mainDb = isset($pdo) && $pdo instanceof PDO ? (string) $pdo->query('SELECT DATABASE()')->fetchColumn() : '';
        $homeDb = (string) $homePdo->query('SELECT DATABASE()')->fetchColumn();
        if ($homeDb !== '' && $homeDb !== $mainDb) {
            $databases['home'] = $homePdo;
        }
    }
} catch (Throwable $e) {
}

$result = ['status' => 'success', 'delete_raw' => $deleteRaw, 'databases' => []];
foreach ($databases as $name => $dbPdo) {
    try {
        $result['databases'][$name] = admin_traffic_report_run($dbPdo, $throughDate, $deleteRaw);
    } catch (Throwable $e) {
        $result['status'] = 'partial_error';
        $result['databases'][$name] = ['error' => $e->getMessage()];
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

function admin_home_pdo_for_traffic_cron(): ?PDO
{
    $config = __DIR__ . '/../CarrotHome/config/database.php';
    if (!is_file($config)) {
        return null;
    }
    $pdo = null;
    require $config;
    return $pdo instanceof PDO ? $pdo : null;
}
