<?php
session_start();
require __DIR__ . '/config/account.php';
require __DIR__ . '/includes/schema.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli && empty($_SESSION['admin_user'])) {
    header('Location: index.php');
    exit;
}

function install_carrot_home_pdo(): PDO
{
    $homePdo = null;
    $homeError = null;
    (static function () use (&$homePdo, &$homeError): void {
        require __DIR__ . '/../CarrotHome/config/database.php';
        $homePdo = $pdo ?? null;
        $homeError = $db_error ?? null;
    })();

    if (!$homePdo instanceof PDO) {
        throw new RuntimeException('Không thể kết nối CarrotHome database: ' . ($homeError ?: 'unknown error'));
    }

    return $homePdo;
}

function install_run_step(string $name, callable $callback): array
{
    try {
        $message = $callback();
        return ['name' => $name, 'status' => 'success', 'message' => $message ?: 'OK'];
    } catch (Throwable $e) {
        return ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

require __DIR__ . '/../CarrotCoc/config/database.php';
$cocPdo = $pdo ?? null;
$cocError = $db_error ?? null;

$results = [];
$results[] = install_run_step('CarrotCoc schema.sql', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    $schemaFile = __DIR__ . '/../CarrotCoc/sql/schema.sql';
    if (!is_file($schemaFile)) {
        throw new RuntimeException('Không tìm thấy file schema: ' . $schemaFile);
    }

    $cocPdo->exec(file_get_contents($schemaFile));
});

$results[] = install_run_step('CarrotCoc app table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_app_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc app store table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_app_store_table($cocPdo);
});

$results[] = install_run_step('CarrotHome app view table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotHome database.');
    }

    admin_ensure_app_view_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc app photo table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_app_photo_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc app content table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_app_content_table($cocPdo);
});

$results[] = install_run_step('CarrotMusic tables', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_music_tables($cocPdo);
});

$results[] = install_run_step('CarrotCoc app category tables', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_app_category_tables($cocPdo);
});

$results[] = install_run_step('CarrotHome app orders table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotHome database.');
    }

    admin_ensure_app_order_table($cocPdo);
});

$results[] = install_run_step('PayPal config table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối database.');
    }

    admin_ensure_paypal_config_table($cocPdo);
});

$results[] = install_run_step('AI support config table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối database.');
    }

    admin_ensure_ai_support_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc country table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_country_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc country seed data', static function () use ($cocPdo, $cocError): string {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    return admin_seed_country_table($cocPdo) . ' countries';
});

$results[] = install_run_step('CarrotCoc text label table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_text_label_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc visit daily IP table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_visit_daily_ip_table($cocPdo, 'coc');
});

$results[] = install_run_step('CarrotMusic visit daily IP table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotMusic database.');
    }

    admin_ensure_visit_daily_ip_table($cocPdo, 'music');
});

$results[] = install_run_step('CarrotCoc bank table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_bank_table($cocPdo);
});

$results[] = install_run_step('CarrotCoc sites table', static function () use ($cocPdo, $cocError): void {
    if (!$cocPdo instanceof PDO) {
        throw new RuntimeException($cocError ?? 'Không thể kết nối CarrotCoc database.');
    }

    admin_ensure_sites_table($cocPdo);
});

$results[] = install_run_step('CarrotHome page table', static function (): void {
    admin_ensure_page_table(install_carrot_home_pdo());
});

$results[] = install_run_step('CarrotHome users table', static function (): void {
    admin_ensure_user_table(install_carrot_home_pdo());
});

$results[] = install_run_step('CarrotHome API config table', static function (): void {
    admin_ensure_api_table(install_carrot_home_pdo());
});

$results[] = install_run_step('CarrotHome PayPal config table', static function (): void {
    admin_ensure_paypal_config_table(install_carrot_home_pdo());
});

$results[] = install_run_step('CarrotHome visit daily IP table', static function (): void {
    admin_ensure_visit_daily_ip_table(install_carrot_home_pdo(), 'home');
});

$hasError = array_reduce($results, static fn(bool $carry, array $result): bool => $carry || $result['status'] !== 'success', false);

if ($isCli) {
    foreach ($results as $result) {
        echo '[' . strtoupper($result['status']) . '] ' . $result['name'] . ': ' . $result['message'] . PHP_EOL;
    }
    exit($hasError ? 1 : 0);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrot Admin - Install</title>
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <link rel="shortcut icon" href="favicon/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#eef2f7;color:#172033}
        .glass-panel{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:rgba(255,255,255,.96);box-shadow:0 14px 36px rgba(15,23,42,.06)}
        .muted-text{color:#64748b}
    </style>
</head>
<body>
<div class="container py-5">
    <div class="glass-panel p-4 mx-auto" style="max-width: 760px">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <p class="muted-text text-uppercase fw-bold mb-1">Carrot Admin</p>
                <h1 class="h3 mb-0">Install / Update database</h1>
            </div>
            <a class="btn btn-secondary fw-bold" href="index.php">Về admin</a>
        </div>

        <?php if ($hasError): ?>
            <div class="alert alert-warning">Có bước cập nhật bị lỗi. Vui lòng xem chi tiết bên dưới.</div>
        <?php else: ?>
            <div class="alert alert-success">Đã cập nhật database thành công.</div>
        <?php endif; ?>

        <div class="table-responsive-sm">
            <table class="table table-striped table-hover align-middle">
                <thead>
                <tr>
                    <th>Bước</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?= htmlspecialchars($result['name']) ?></td>
                        <td>
                            <span class="badge <?= $result['status'] === 'success' ? 'text-bg-success' : 'text-bg-danger' ?>">
                                <?= htmlspecialchars(strtoupper($result['status'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($result['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
