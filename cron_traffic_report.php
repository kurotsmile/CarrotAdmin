<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

const ADMIN_TRAFFIC_CRON_LOG_FILE = __DIR__ . '/log.txt';

admin_traffic_cron_append_log('started');
register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (is_array($error) && in_array($error['type'] ?? 0, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        admin_traffic_cron_append_log('fatal_error', [
            'message' => (string) ($error['message'] ?? ''),
            'file' => (string) ($error['file'] ?? ''),
            'line' => (int) ($error['line'] ?? 0),
        ]);
    }
});

require __DIR__ . '/../CarrotCoc/config/database.php';
require __DIR__ . '/includes/schema.php';
require __DIR__ . '/includes/traffic_report.php';
require __DIR__ . '/includes/orders_cleanup.php';

$isCli = PHP_SAPI === 'cli';
$cliOptions = $isCli ? getopt('', ['through-date::', 'delete-raw::', 'delete-created::', 'max-days::']) : [];
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

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

$deleteRawValue = $isCli ? (string) ($cliOptions['delete-raw'] ?? '1') : (string) ($_GET['delete_raw'] ?? '1');
$deleteRaw = !in_array($deleteRawValue, ['0', 'false', 'no'], true);
$deleteCreatedValue = $isCli ? (string) ($cliOptions['delete-created'] ?? '1') : (string) ($_GET['delete_created'] ?? '1');
$deleteCreated = !in_array($deleteCreatedValue, ['0', 'false', 'no'], true);
$throughDate = trim($isCli ? (string) ($cliOptions['through-date'] ?? '') : (string) ($_GET['through_date'] ?? '')) ?: null;
$maxDaysValue = $isCli ? (string) ($cliOptions['max-days'] ?? '0') : (string) ($_GET['max_days'] ?? '0');
$maxDays = max(0, (int) $maxDaysValue);
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

$result = ['status' => 'success', 'delete_raw' => $deleteRaw, 'delete_created' => $deleteCreated, 'databases' => []];
foreach ($databases as $name => $dbPdo) {
    try {
        $result['databases'][$name] = admin_traffic_report_run($dbPdo, $throughDate, $deleteRaw, $maxDays);
    } catch (Throwable $e) {
        $result['status'] = 'partial_error';
        $result['databases'][$name] = ['error' => $e->getMessage()];
    }
}

if ($deleteCreated) {
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $result['created_orders'] = admin_delete_created_orders($pdo);
        } catch (Throwable $e) {
            $result['status'] = 'partial_error';
            $result['created_orders'] = ['error' => $e->getMessage()];
        }
    } else {
        $result['status'] = 'partial_error';
        $result['created_orders'] = ['error' => 'Không có kết nối database chính.'];
    }
}

admin_traffic_cron_append_log('finished', [
    'status' => $result['status'],
    'delete_raw' => $deleteRaw,
    'delete_created' => $deleteCreated,
    'through_date' => $throughDate,
    'max_days' => $maxDays,
    'databases' => array_keys($result['databases']),
    'created_orders_total' => (int) ($result['created_orders']['total'] ?? 0),
]);

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

function admin_traffic_cron_append_log(string $event, array $context = []): void
{
    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'event' => $event,
        'source' => PHP_SAPI === 'cli' ? 'cli' : 'web',
    ];

    if (PHP_SAPI !== 'cli') {
        $entry['ip'] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    if ($context !== []) {
        $entry['context'] = $context;
    }

    @file_put_contents(
        ADMIN_TRAFFIC_CRON_LOG_FILE,
        json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    @chmod(ADMIN_TRAFFIC_CRON_LOG_FILE, 0644);
}
