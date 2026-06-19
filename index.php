<?php
session_start();
require __DIR__ . '/config/account.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (isset($auth_users[$username]) && password_verify($password, $auth_users[$username])) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $username;
        header('Location: index.php');
        exit;
    }

    $loginError = 'Sai tài khoản hoặc mật khẩu.';
}

if (empty($_SESSION['admin_user'])):
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrot Admin - Login</title>
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <link rel="shortcut icon" href="favicon/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/CarrotCoc/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <form class="glass-panel p-4" style="width:min(100%, 420px)" method="post">
        <input type="hidden" name="action" value="login">
        <div class="text-center mb-4">
            <img class="brand-mark" src="carrot_28.png" alt="Carrot Admin">
            <h1 class="h4 mt-3 mb-0">Đăng nhập</h1>
        </div>
        <?php if ($loginError): ?><div class="alert alert-warning"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-success fw-bold w-100" type="submit">Đăng nhập</button>
    </form>
</div>
</body>
</html>
<?php
exit;
endif;

require __DIR__ . '/../CarrotCoc/config/database.php';
require __DIR__ . '/../CarrotCoc/includes/coc_helpers.php';

$message = '';
$error = '';
$allowedSections = ['overview', 'apps', 'pages', 'bank', 'coc', 'country'];
$section = in_array($_GET['section'] ?? 'overview', $allowedSections, true) ? ($_GET['section'] ?? 'overview') : 'overview';
$editKey = trim($_GET['edit'] ?? '');
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$cocTab = ($_GET['tab'] ?? 'accounts') === 'orders' ? 'orders' : 'accounts';
$editing = null;
$accounts = [];
$apps = [];
$pages = [];
$banks = [];
$countries = [];
$orders = [];
$dashboardMetrics = [
    'apps' => 0,
    'pages' => 0,
    'coc' => 0,
    'bank' => 0,
    'country' => 0,
];
$trafficMetrics = [
    'coc' => [],
    'home' => [],
    'total' => [
        'today_unique' => 0,
        'today_hits' => 0,
        'week_unique' => 0,
        'week_hits' => 0,
        'total_unique' => 0,
        'total_hits' => 0,
    ],
];
$accountSort = 'id';
$accountDir = 'DESC';
$orderSort = 'created_at';
$orderDir = 'DESC';
$appSort = 'priority';
$appDir = 'DESC';
$pageSort = 'priority';
$pageDir = 'DESC';
$bankSort = 'id';
$bankDir = 'DESC';
$countrySort = 'id';
$countryDir = 'DESC';
$serverRuntime = null;

function admin_nas_upload_endpoint(): string
{
    return 'https://nas.carrot28.com/index.php?api=upload_image';
}

function admin_upload_image_to_nas(array $file, string $typeMedia): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload ảnh lỗi, mã: ' . (int) $file['error']);
    }

    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('File upload không hợp lệ.');
    }

    if (!function_exists('curl_init') || !function_exists('curl_file_create')) {
        throw new RuntimeException('Server cần bật PHP cURL để tải ảnh lên CarrotNas.');
    }

    $curl = curl_init(admin_nas_upload_endpoint());
    $postFields = [
        'type_media' => $typeMedia,
        'file' => curl_file_create(
            $file['tmp_name'],
            mime_content_type($file['tmp_name']) ?: 'application/octet-stream',
            $file['name'] ?? 'image'
        ),
    ];

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($response === false) {
        throw new RuntimeException('Không gọi được API CarrotNas: ' . $curlError);
    }

    $payload = json_decode($response, true);
    if ($statusCode >= 400 || !is_array($payload) || ($payload['status'] ?? '') !== 'success' || empty($payload['url'])) {
        $apiMessage = is_array($payload) ? ($payload['message'] ?? 'unknown error') : trim($response);
        throw new RuntimeException('CarrotNas upload thất bại: ' . $apiMessage);
    }

    return (string) $payload['url'];
}

function admin_ajax_upload(): void
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $typeMedia = trim($_POST['type_media'] ?? '');
        if (!in_array($typeMedia, ['carrot_app', 'coc_images', 'bank', 'country'], true)) {
            throw new RuntimeException('Type media không hợp lệ.');
        }

        $url = admin_upload_image_to_nas($_FILES['file'] ?? [], $typeMedia);
        if ($url === '') {
            throw new RuntimeException('Vui lòng chọn file để upload.');
        }

        echo json_encode(['status' => 'success', 'url' => $url], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    exit;
}

function admin_fetch_app(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM app WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_bank(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bank WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_country(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM country WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_home_pdo(): ?PDO
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

function admin_fetch_page(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM page WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_excerpt(?string $text, int $limit = 70): string
{
    $text = trim((string) $text);
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...');
    }

    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

function admin_sort_state(array $allowed, string $defaultSort, string $defaultDir = 'DESC'): array
{
    $sort = $_GET['sort'] ?? $defaultSort;
    $dir = strtoupper($_GET['dir'] ?? $defaultDir);

    if (!isset($allowed[$sort])) {
        $sort = $defaultSort;
    }

    if (!in_array($dir, ['ASC', 'DESC'], true)) {
        $dir = $defaultDir;
    }

    return [$sort, $dir];
}

function admin_order_by(array $allowed, string $sort, string $dir): string
{
    return $allowed[$sort] . ' ' . $dir;
}

function admin_sort_link(string $key, string $label, string $activeSort, string $activeDir): string
{
    $params = $_GET;
    $params['sort'] = $key;
    $params['dir'] = ($activeSort === $key && $activeDir === 'ASC') ? 'DESC' : 'ASC';
    unset($params['edit']);

    $icon = '&harr;';
    if ($activeSort === $key) {
        $icon = $activeDir === 'ASC' ? '&uarr;' : '&darr;';
    }

    return '<a class="text-reset text-decoration-none d-inline-flex align-items-center gap-1" href="index.php?' .
        htmlspecialchars(http_build_query($params)) . '">' . htmlspecialchars($label) . '<span>' . $icon . '</span></a>';
}

function admin_runtime_identity(): string
{
    $pid = function_exists('getmypid') ? (string) getmypid() : 'web';
    $parentPid = function_exists('posix_getppid') ? (string) posix_getppid() : '';
    return sha1(($parentPid ?: $pid) . '|' . ($_SERVER['SERVER_SOFTWARE'] ?? 'server'));
}

function admin_server_runtime(): array
{
    $storageDir = __DIR__ . '/storage';
    $runtimeFile = $storageDir . '/server_runtime.json';
    $identity = admin_runtime_identity();
    $now = time();
    $payload = null;

    if (is_file($runtimeFile)) {
        $payload = json_decode((string) file_get_contents($runtimeFile), true);
    }

    if (!is_array($payload) || ($payload['identity'] ?? '') !== $identity || empty($payload['started_at'])) {
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $payload = [
            'identity' => $identity,
            'started_at' => $now,
        ];
        if (is_dir($storageDir) && is_writable($storageDir)) {
            @file_put_contents($runtimeFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    return [
        'started_at' => (int) $payload['started_at'],
        'uptime_seconds' => max(0, $now - (int) $payload['started_at']),
    ];
}

function admin_format_uptime(int $seconds): string
{
    $days = intdiv($seconds, 86400);
    $seconds %= 86400;
    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;
    $minutes = intdiv($seconds, 60);
    $seconds %= 60;

    if ($days > 0) {
        return sprintf('%dd %02dh %02dm %02ds', $days, $hours, $minutes, $seconds);
    }

    return sprintf('%02dh %02dm %02ds', $hours, $minutes, $seconds);
}

function admin_count_table(PDO $pdo, string $table): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}

function admin_safe_count_table(?PDO $pdo, string $table): int
{
    if (!$pdo instanceof PDO) {
        return 0;
    }

    try {
        return admin_count_table($pdo, $table);
    } catch (Throwable $e) {
        return 0;
    }
}

function admin_empty_visit_metrics(): array
{
    return [
        'today_unique' => 0,
        'today_hits' => 0,
        'week_unique' => 0,
        'week_hits' => 0,
        'total_unique' => 0,
        'total_hits' => 0,
    ];
}

function admin_visit_metrics(?PDO $pdo, string $site): array
{
    if (!$pdo instanceof PDO) {
        return admin_empty_visit_metrics();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
              SUM(CASE WHEN visit_date = CURRENT_DATE THEN 1 ELSE 0 END) AS today_unique,
              SUM(CASE WHEN visit_date = CURRENT_DATE THEN hits ELSE 0 END) AS today_hits,
              SUM(CASE WHEN visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) THEN 1 ELSE 0 END) AS week_unique,
              SUM(CASE WHEN visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) THEN hits ELSE 0 END) AS week_hits,
              COUNT(*) AS total_unique,
              COALESCE(SUM(hits), 0) AS total_hits
            FROM visit_daily_ip
            WHERE site = :site
        ");
        $stmt->execute([':site' => $site]);
        $row = $stmt->fetch() ?: [];
    } catch (Throwable $e) {
        return admin_empty_visit_metrics();
    }

    return [
        'today_unique' => (int) ($row['today_unique'] ?? 0),
        'today_hits' => (int) ($row['today_hits'] ?? 0),
        'week_unique' => (int) ($row['week_unique'] ?? 0),
        'week_hits' => (int) ($row['week_hits'] ?? 0),
        'total_unique' => (int) ($row['total_unique'] ?? 0),
        'total_hits' => (int) ($row['total_hits'] ?? 0),
    ];
}

function admin_sum_visit_metrics(array $items): array
{
    $total = admin_empty_visit_metrics();
    foreach ($items as $item) {
        foreach ($total as $key => $value) {
            $total[$key] += (int) ($item[$key] ?? 0);
        }
    }
    return $total;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajax_upload') {
    admin_ajax_upload();
}

if (!$pdo instanceof PDO && !in_array($section, ['overview', 'pages'], true)) {
    $error = 'Không thể kết nối database: ' . ($db_error ?? 'unknown error');
} else {
    try {
        $homePdo = null;
        if ($section === 'pages') {
            $homePdo = admin_home_pdo();
        }

        if ($section === 'overview') {
            $serverRuntime = admin_server_runtime();
            $dashboardMetrics['apps'] = admin_safe_count_table($pdo, 'app');
            $dashboardMetrics['coc'] = admin_safe_count_table($pdo, 'coc');
            $dashboardMetrics['bank'] = admin_safe_count_table($pdo, 'bank');
            $dashboardMetrics['country'] = admin_safe_count_table($pdo, 'country');
            $overviewHomePdo = null;
            try {
                $overviewHomePdo = admin_home_pdo();
                $dashboardMetrics['pages'] = admin_safe_count_table($overviewHomePdo, 'page');
            } catch (Throwable $e) {
                $dashboardMetrics['pages'] = 0;
            }
            $trafficMetrics['coc'] = admin_visit_metrics($pdo, 'coc');
            $trafficMetrics['home'] = admin_visit_metrics($overviewHomePdo, 'home');
            $trafficMetrics['total'] = admin_sum_visit_metrics([$trafficMetrics['coc'], $trafficMetrics['home']]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($section === 'coc' && $action === 'delete') {
                $stmt = $pdo->prepare('DELETE FROM coc WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa acc.';
            }

            if ($section === 'coc' && $action === 'save') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $hall = (int) ($_POST['hall'] ?? 0);
                $data = trim($_POST['data'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $photoUrls = coc_decode_photos(coc_photos_to_json($_POST['photos'] ?? ''));
                $username = trim($_POST['username'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $price = (float) ($_POST['price'] ?? 0);

                if ($name === '' || $data === '' || $username === '' || $password === '') {
                    throw new RuntimeException('Vui lòng nhập đủ name, data, username và password.');
                }

                if ($hall < 1 || $hall > 99) {
                    throw new RuntimeException('Town Hall phải nằm trong khoảng 1 đến 99.');
                }

                if (json_decode($data, true) === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Trường data phải là JSON hợp lệ.');
                }

                $photos = coc_photos_to_json(implode("\n", array_unique($photoUrls)));

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE coc SET name = ?, hall = ?, data = ?, avatar = ?, photos = ?, username = ?, password = ?, price = ? WHERE id = ?');
                    $stmt->execute([$name, $hall, $data, $avatar, $photos, $username, $password, $price, $id]);
                    $message = 'Đã cập nhật acc.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO coc (name, hall, data, avatar, photos, username, password, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $hall, $data, $avatar, $photos, $username, $password, $price]);
                    $message = 'Đã thêm acc mới.';
                }
            }

            if ($section === 'apps' && $action === 'delete_app') {
                $stmt = $pdo->prepare('DELETE FROM app WHERE id = ?');
                $stmt->execute([trim($_POST['id'] ?? '')]);
                $message = 'Đã xóa app.';
            }

            if ($section === 'apps' && $action === 'save_app') {
                $originalId = trim($_POST['original_id'] ?? '');
                $id = trim($_POST['id'] ?? '');
                $decription = trim($_POST['decription'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $status = trim($_POST['status'] ?? '');
                $syncStatus = (int) ($_POST['sync_status'] ?? 0);
                $priority = (int) ($_POST['priority'] ?? 0);

                if ($id === '') {
                    throw new RuntimeException('Vui lòng nhập ID app.');
                }

                $textFields = [
                    'github', 'microsoft_store', 'icon', 'itch', 'exe_file', 'ipa_file', 'deb_file',
                    'amazon_app_store', 'huawei_store', 'youtube_link', 'google_play', 'dmg_file',
                    'uptodown', 'simmer', 'apk_file', 'category',
                ];
                $values = [];
                foreach ($textFields as $field) {
                    $values[$field] = trim($_POST[$field] ?? '');
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('UPDATE app SET id = ?, decription = ?, github = ?, microsoft_store = ?, icon = ?, itch = ?, exe_file = ?, ipa_file = ?, deb_file = ?, amazon_app_store = ?, huawei_store = ?, youtube_link = ?, google_play = ?, dmg_file = ?, uptodown = ?, simmer = ?, type = ?, apk_file = ?, status = ?, sync_status = ?, priority = ?, category = ? WHERE id = ?');
                    $stmt->execute([$id, $decription, $values['github'], $values['microsoft_store'], $values['icon'], $values['itch'], $values['exe_file'], $values['ipa_file'], $values['deb_file'], $values['amazon_app_store'], $values['huawei_store'], $values['youtube_link'], $values['google_play'], $values['dmg_file'], $values['uptodown'], $values['simmer'], $type, $values['apk_file'], $status, $syncStatus, $priority, $values['category'], $originalId]);
                    $message = 'Đã cập nhật app.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app (id, decription, github, microsoft_store, icon, itch, exe_file, ipa_file, deb_file, amazon_app_store, huawei_store, youtube_link, google_play, dmg_file, uptodown, simmer, type, apk_file, status, sync_status, priority, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$id, $decription, $values['github'], $values['microsoft_store'], $values['icon'], $values['itch'], $values['exe_file'], $values['ipa_file'], $values['deb_file'], $values['amazon_app_store'], $values['huawei_store'], $values['youtube_link'], $values['google_play'], $values['dmg_file'], $values['uptodown'], $values['simmer'], $type, $values['apk_file'], $status, $syncStatus, $priority, $values['category']]);
                    $message = 'Đã thêm app mới.';
                }
            }

            if ($section === 'pages' && $action === 'delete_page') {
                $stmt = $homePdo->prepare('DELETE FROM page WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa page.';
            }

            if ($section === 'pages' && $action === 'save_page') {
                $id = (int) ($_POST['id'] ?? 0);
                $slug = trim($_POST['slug'] ?? '');
                $lang = trim($_POST['lang'] ?? 'vi');
                $title = trim($_POST['title'] ?? '');
                $contentHtml = trim($_POST['content_html'] ?? '');
                $seoTitle = trim($_POST['seo_title'] ?? '');
                $seoDescription = trim($_POST['seo_description'] ?? '');
                $seoKeywords = trim($_POST['seo_keywords'] ?? '');
                $status = trim($_POST['status'] ?? 'draft');
                $priority = (int) ($_POST['priority'] ?? 0);
                $publishedAt = trim($_POST['published_at'] ?? '');

                if ($slug === '' || $title === '' || $contentHtml === '') {
                    throw new RuntimeException('Vui lòng nhập slug, title và nội dung HTML.');
                }

                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                    throw new RuntimeException('Slug chỉ dùng chữ thường, số và dấu gạch ngang.');
                }

                if (!in_array($status, ['public', 'draft', 'trash'], true)) {
                    throw new RuntimeException('Status page không hợp lệ.');
                }

                $publishedAt = $publishedAt !== '' ? str_replace('T', ' ', $publishedAt) . (strlen($publishedAt) === 16 ? ':00' : '') : null;

                if ($id > 0) {
                    $stmt = $homePdo->prepare('UPDATE page SET slug = ?, lang = ?, title = ?, content_html = ?, seo_title = ?, seo_description = ?, seo_keywords = ?, status = ?, priority = ?, published_at = ? WHERE id = ?');
                    $stmt->execute([$slug, $lang, $title, $contentHtml, $seoTitle, $seoDescription, $seoKeywords, $status, $priority, $publishedAt, $id]);
                    $message = 'Đã cập nhật page.';
                } else {
                    $stmt = $homePdo->prepare('INSERT INTO page (slug, lang, title, content_html, seo_title, seo_description, seo_keywords, status, priority, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$slug, $lang, $title, $contentHtml, $seoTitle, $seoDescription, $seoKeywords, $status, $priority, $publishedAt]);
                    $message = 'Đã thêm page mới.';
                }
            }

            if ($section === 'bank' && $action === 'delete_bank') {
                $stmt = $pdo->prepare('DELETE FROM bank WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa bank.';
            }

            if ($section === 'bank' && $action === 'save_bank') {
                $originalId = (int) ($_POST['original_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $banner = trim($_POST['banner'] ?? '');
                $qr = trim($_POST['qr'] ?? '');
                $accountName = trim($_POST['account_name'] ?? '');
                $accountNumber = trim($_POST['account_number'] ?? '');

                if ($name === '' || $avatar === '' || $banner === '' || $qr === '' || $accountName === '' || $accountNumber === '') {
                    throw new RuntimeException('Vui lòng nhập đủ thông tin bank.');
                }

                if ($originalId > 0) {
                    $stmt = $pdo->prepare('UPDATE bank SET name = ?, avatar = ?, banner = ?, qr = ?, account_name = ?, account_number = ? WHERE id = ?');
                    $stmt->execute([$name, $avatar, $banner, $qr, $accountName, $accountNumber, $originalId]);
                    $message = 'Đã cập nhật bank.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO bank (name, avatar, banner, qr, account_name, account_number) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $avatar, $banner, $qr, $accountName, $accountNumber]);
                    $message = 'Đã thêm bank mới.';
                }
            }

            if ($section === 'country' && $action === 'delete_country') {
                $stmt = $pdo->prepare('DELETE FROM country WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa country.';
            }

            if ($section === 'country' && $action === 'save_country') {
                $id = (int) ($_POST['id'] ?? 0);
                $icon = trim($_POST['icon'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? '');
                $langCountry = strtoupper(trim($_POST['lang_country'] ?? ''));

                if ($icon === '' || $name === '' || $langKey === '' || $langCountry === '') {
                    throw new RuntimeException('Vui lòng nhập đủ icon, name, lang_key và lang_country.');
                }

                if (!preg_match('/^[a-z]{2,3}(?:[-_][A-Z]{2})?$/', $langKey)) {
                    throw new RuntimeException('Lang key nên có dạng vi, en, en-US hoặc en_US.');
                }

                if (!preg_match('/^[A-Z]{2}$/', $langCountry)) {
                    throw new RuntimeException('Lang country nên là mã quốc gia 2 chữ hoa, ví dụ VN, US, JP.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE country SET icon = ?, name = ?, lang_key = ?, lang_country = ? WHERE id = ?');
                    $stmt->execute([$icon, $name, $langKey, $langCountry, $id]);
                    $message = 'Đã cập nhật country.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO country (icon, name, lang_key, lang_country) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$icon, $name, $langKey, $langCountry]);
                    $message = 'Đã thêm country mới.';
                }
            }
        }

        if ($section === 'coc' && $editId > 0) {
            $editing = coc_fetch_account($pdo, $editId);
        }

        if ($section === 'apps' && $editKey !== '') {
            $editing = admin_fetch_app($pdo, $editKey);
        }

        if ($section === 'pages' && $editId > 0) {
            $editing = admin_fetch_page($homePdo, $editId);
        }

        if ($section === 'bank' && $editId > 0) {
            $editing = admin_fetch_bank($pdo, $editId);
        }

        if ($section === 'country' && $editId > 0) {
            $editing = admin_fetch_country($pdo, $editId);
        }

        $accountSortColumns = [
            'id' => 'id',
            'name' => 'name',
            'hall' => 'hall',
            'username' => 'username',
            'price' => 'price',
            'updated_at' => 'updated_at',
        ];
        [$accountSort, $accountDir] = admin_sort_state($accountSortColumns, 'id', 'DESC');

        $orderSortColumns = [
            'id' => 'coc_orders.id',
            'account' => 'coc.name',
            'paypal_order_id' => 'coc_orders.paypal_order_id',
            'status' => 'coc_orders.status',
            'amount' => 'coc_orders.amount',
            'payer_email' => 'coc_orders.payer_email',
            'created_at' => 'coc_orders.created_at',
            'paid_at' => 'coc_orders.paid_at',
        ];
        [$orderSort, $orderDir] = admin_sort_state($orderSortColumns, 'created_at', 'DESC');

        $appSortColumns = [
            'id' => 'id',
            'type' => 'type',
            'status' => 'status',
            'priority' => 'priority',
            'created_at' => 'created_at',
        ];
        [$appSort, $appDir] = admin_sort_state($appSortColumns, 'priority', 'DESC');

        $pageSortColumns = [
            'id' => 'id',
            'title' => 'title',
            'slug' => 'slug',
            'lang' => 'lang',
            'status' => 'status',
            'priority' => 'priority',
            'updated_at' => 'updated_at',
        ];
        [$pageSort, $pageDir] = admin_sort_state($pageSortColumns, 'priority', 'DESC');

        $bankSortColumns = [
            'id' => 'id',
            'name' => 'name',
            'account_name' => 'account_name',
            'account_number' => 'account_number',
        ];
        [$bankSort, $bankDir] = admin_sort_state($bankSortColumns, 'id', 'DESC');

        $countrySortColumns = [
            'id' => 'id',
            'name' => 'name',
            'lang_key' => 'lang_key',
            'lang_country' => 'lang_country',
            'updated_at' => 'updated_at',
        ];
        [$countrySort, $countryDir] = admin_sort_state($countrySortColumns, 'id', 'DESC');

        $accounts = $section === 'coc'
            ? $pdo->query('SELECT * FROM coc ORDER BY ' . admin_order_by($accountSortColumns, $accountSort, $accountDir))->fetchAll()
            : [];
        $orders = ($section === 'coc' && $cocTab === 'orders')
            ? $pdo->query('
                SELECT coc_orders.*, coc.name AS coc_name, coc.username AS coc_username
                FROM coc_orders
                LEFT JOIN coc ON coc.id = coc_orders.coc_id
                ORDER BY ' . admin_order_by($orderSortColumns, $orderSort, $orderDir) . ', coc_orders.id DESC
            ')->fetchAll()
            : [];
        $apps = $section === 'apps'
            ? $pdo->query('SELECT * FROM app ORDER BY ' . admin_order_by($appSortColumns, $appSort, $appDir) . ', id ASC')->fetchAll()
            : [];
        $pages = $section === 'pages'
            ? $homePdo->query('SELECT * FROM page ORDER BY ' . admin_order_by($pageSortColumns, $pageSort, $pageDir) . ', id DESC')->fetchAll()
            : [];
        $banks = $section === 'bank'
            ? $pdo->query('SELECT * FROM bank ORDER BY ' . admin_order_by($bankSortColumns, $bankSort, $bankDir))->fetchAll()
            : [];
        $countries = $section === 'country'
            ? $pdo->query('SELECT * FROM country ORDER BY ' . admin_order_by($countrySortColumns, $countrySort, $countryDir))->fetchAll()
            : [];
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $accounts = [];
        $apps = [];
        $pages = [];
        $banks = [];
        $countries = [];
        $orders = [];
    }
}

$photoText = ($section === 'coc' && $editing) ? implode("\n", coc_decode_photos($editing['photos'])) : '';
$pageTitle = ['overview' => 'Tổng quan', 'apps' => 'App', 'pages' => 'Page', 'bank' => 'Bank', 'coc' => 'Coc', 'country' => 'Country'][$section] ?? 'Tổng quan';
$sectionLabels = ['overview' => 'tổng quan', 'apps' => 'ứng dụng', 'pages' => 'Page/SEO', 'bank' => 'ngân hàng', 'coc' => 'shop', 'country' => 'quốc gia hỗ trợ'];
$sectionTitles = ['overview' => 'Tổng quan', 'apps' => 'App Carrot Home', 'pages' => 'Page Carrot Home', 'bank' => 'Bank', 'coc' => 'Acc Clash of Clans', 'country' => 'Country'];
$dashboardCards = [
    ['label' => 'App', 'value' => $dashboardMetrics['apps'], 'icon' => 'boxes'],
    ['label' => 'Page', 'value' => $dashboardMetrics['pages'], 'icon' => 'file-text'],
    ['label' => 'Coc', 'value' => $dashboardMetrics['coc'], 'icon' => 'shield'],
    ['label' => 'Bank', 'value' => $dashboardMetrics['bank'], 'icon' => 'landmark'],
    ['label' => 'Country', 'value' => $dashboardMetrics['country'], 'icon' => 'globe-2'],
    ['label' => 'IP hôm nay', 'value' => $trafficMetrics['total']['today_unique'], 'icon' => 'activity'],
];
$trafficRows = [
    ['label' => 'COC Shop', 'url' => 'https://coc.carrot28.com/', 'metrics' => $trafficMetrics['coc']],
    ['label' => 'CarrotHome', 'url' => 'https://home.carrot28.com/', 'metrics' => $trafficMetrics['home']],
    ['label' => 'Tổng cộng', 'url' => '', 'metrics' => $trafficMetrics['total']],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrot Admin - <?= htmlspecialchars($pageTitle) ?></title>
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">
    <link rel="shortcut icon" href="favicon/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/CarrotCoc/assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body{background:#eef2f7;color:#172033}
        .dashboard-layout{min-height:100vh;background:linear-gradient(180deg,#f8fafc 0,#eef2f7 42%,#e9edf4 100%)}
        .dashboard-sidebar{position:sticky;top:16px;min-height:calc(100vh - 32px);border:1px solid rgba(15,23,42,.08);border-radius:8px;background:rgba(255,255,255,.92);box-shadow:0 18px 48px rgba(15,23,42,.08)}
        .dashboard-brand{padding:.25rem .25rem 1rem;border-bottom:1px solid rgba(15,23,42,.08)}
        .dashboard-brand-title{font-weight:800;line-height:1.1}
        .dashboard-brand-subtitle{font-size:.78rem;color:#64748b}
        .dashboard-nav{gap:.35rem}
        .dashboard-nav .list-group-item{display:flex;align-items:center;gap:.75rem;border:0;border-radius:8px;margin-bottom:.25rem;background:transparent;color:#334155;font-weight:700}
        .dashboard-nav .list-group-item i{width:18px;height:18px;color:#64748b}
        .dashboard-nav .list-group-item.active{background:#172033;color:#fff}
        .dashboard-nav .list-group-item.active i{color:#fff}
        .dashboard-main{min-width:0}
        .dashboard-topbar{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:rgba(255,255,255,.94);box-shadow:0 18px 48px rgba(15,23,42,.07)}
        .dashboard-eyebrow{color:#64748b;font-size:.76rem;letter-spacing:0;text-transform:uppercase}
        .dashboard-actions .btn{border-radius:8px}
        .dashboard-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.75rem}
        .dashboard-card{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.05);padding:.72rem .85rem}
        .dashboard-card-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:#f1f5f9;color:#172033}
        .dashboard-card-icon i{width:16px;height:16px}
        .dashboard-card-label{font-size:.7rem;color:#64748b;font-weight:800;text-transform:uppercase}
        .dashboard-card-value{font-size:1.18rem;font-weight:850;line-height:1.05}
        .dashboard-uptime{background:#172033;color:#fff}
        .dashboard-uptime .dashboard-card-label{color:#cbd5e1}
        .dashboard-uptime .dashboard-card-icon{background:rgba(255,255,255,.12);color:#fff}
        .dashboard-uptime-start{font-size:.78rem;color:#cbd5e1}
        .overview-panel-title{display:flex;align-items:center;gap:.6rem;font-weight:850}
        .overview-panel-title i{width:18px;height:18px}
        .traffic-site-link{font-weight:800;color:#172033;text-decoration:none}
        .traffic-site-link:hover{text-decoration:underline}
        .glass-panel,.admin-shell{border:1px solid rgba(15,23,42,.08)!important;border-radius:8px!important;background:rgba(255,255,255,.96)!important;box-shadow:0 14px 36px rgba(15,23,42,.06)!important}
        .table{--bs-table-bg:transparent}
        .table thead th{color:#64748b;font-size:.78rem;text-transform:uppercase}
        @media (max-width:1199px){.dashboard-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media (max-width:991px){.dashboard-sidebar{position:static;min-height:auto}.dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:575px){.dashboard-grid{grid-template-columns:1fr}.dashboard-card-value{font-size:1.12rem}}
    </style>
    <?php if ($section === 'pages'): ?>
    <style>
        .simple-editor-toolbar{display:flex;flex-wrap:wrap;gap:.35rem;padding:.5rem;border:1px solid rgba(0,0,0,.15);border-bottom:0;border-radius:.375rem .375rem 0 0;background:rgba(255,255,255,.7)}
        .simple-editor-toolbar button{min-width:36px}
        .simple-editor-canvas{min-height:360px;padding:1rem;border:1px solid rgba(0,0,0,.15);border-radius:0 0 .375rem .375rem;background:#fff;color:#111;line-height:1.65;outline:0}
        .simple-editor-canvas:focus{box-shadow:0 0 0 .25rem rgba(25,135,84,.25)}
    </style>
    <?php endif; ?>
</head>
<body>
<div class="container-fluid dashboard-layout">
    <div class="row min-vh-100 g-0">
        <aside class="col-lg-2 dashboard-sidebar m-3 p-3 align-self-start">
            <div class="dashboard-brand d-flex align-items-center gap-3 mb-3">
                <img class="brand-mark" src="carrot_28.png" alt="Carrot Admin">
            </div>
            <div class="list-group dashboard-nav">
                <a class="list-group-item list-group-item-action <?= $section === 'overview' ? 'active' : '' ?>" href="index.php"><i data-lucide="layout-dashboard"></i><span>Tổng quan</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'apps' ? 'active' : '' ?>" href="index.php?section=apps"><i data-lucide="boxes"></i><span>App</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'pages' ? 'active' : '' ?>" href="index.php?section=pages"><i data-lucide="file-text"></i><span>Page</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'coc' ? 'active' : '' ?>" href="index.php?section=coc"><i data-lucide="shield"></i><span>Coc</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'bank' ? 'active' : '' ?>" href="index.php?section=bank"><i data-lucide="landmark"></i><span>Bank</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'country' ? 'active' : '' ?>" href="index.php?section=country"><i data-lucide="globe-2"></i><span>Country</span></a>
            </div>
        </aside>

        <main class="col dashboard-main p-3 p-lg-4">
            <div class="dashboard-topbar p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <p class="dashboard-eyebrow fw-bold mb-1">Quản lý <?= $sectionLabels[$section] ?? 'shop' ?></p>
                        <h1 class="h3 mb-0"><?= $sectionTitles[$section] ?? 'Acc Clash of Clans' ?></h1>
                    </div>
                    <div class="dashboard-actions d-flex flex-wrap gap-2">
                        <?php if ($section === 'coc'): ?>
                            <a class="btn btn-secondary fw-bold" href="https://coc.carrot28.com/" target="_blank" rel="noopener noreferrer">Xem shop</a>
                        <?php endif; ?>
                        <?php if ($editing): ?>
                            <a class="btn btn-success fw-bold" href="index.php<?= $section === 'apps' ? '?section=apps' : ($section === 'pages' ? '?section=pages' : ($section === 'bank' ? '?section=bank' : ($section === 'country' ? '?section=country' : '?section=coc'))) ?>">Thêm mới</a>
                        <?php endif; ?>
                        <a class="btn btn-danger fw-bold" href="index.php?logout=1" title="Đăng xuất">
                            <span class="d-inline-flex align-items-center gap-2"><i data-lucide="log-out" style="width:16px;height:16px"></i><?= htmlspecialchars($_SESSION['admin_user']) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($section === 'overview'): ?>
            <div class="dashboard-grid mb-4">
                <?php foreach ($dashboardCards as $card): ?>
                    <div class="dashboard-card">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div class="dashboard-card-label"><?= htmlspecialchars($card['label']) ?></div>
                            <span class="dashboard-card-icon"><i data-lucide="<?= htmlspecialchars($card['icon']) ?>"></i></span>
                        </div>
                        <div class="dashboard-card-value"><?= number_format((int) $card['value']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (is_array($serverRuntime)): ?>
                <div class="dashboard-card dashboard-uptime">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div class="dashboard-card-label">XAMPP uptime</div>
                        <span class="dashboard-card-icon"><i data-lucide="timer"></i></span>
                    </div>
                    <div class="dashboard-card-value font-monospace" id="server_uptime" data-started-at="<?= (int) $serverRuntime['started_at'] ?>"><?= htmlspecialchars(admin_format_uptime($serverRuntime['uptime_seconds'])) ?></div>
                    <div class="dashboard-uptime-start mt-2">Start: <?= htmlspecialchars(date('Y-m-d H:i:s', $serverRuntime['started_at'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="glass-panel p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0 overview-panel-title"><i data-lucide="chart-no-axes-combined"></i><span>Lưu lượng truy cập</span></h2>
                    <span class="dashboard-eyebrow fw-bold">IP không lặp trong ngày</span>
                </div>
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Site</th>
                            <th class="text-end">IP hôm nay</th>
                            <th class="text-end">Hits hôm nay</th>
                            <th class="text-end">IP 7 ngày</th>
                            <th class="text-end">Hits 7 ngày</th>
                            <th class="text-end">Tổng IP/ngày</th>
                            <th class="text-end">Tổng hits</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($trafficRows as $row): ?>
                            <?php $metrics = $row['metrics']; ?>
                            <tr>
                                <td>
                                    <?php if ($row['url']): ?>
                                        <a class="traffic-site-link" href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($row['label']) ?></a>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($row['label']) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format((int) $metrics['today_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['today_hits']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['week_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['week_hits']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['total_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['total_hits']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($section === 'coc'): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $cocTab === 'accounts' ? 'active' : '' ?>" href="index.php?section=coc&tab=accounts">Các Tài khoản</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $cocTab === 'orders' ? 'active' : '' ?>" href="index.php?section=coc&tab=orders">Đơn Đặt hàng</a>
                </li>
            </ul>

            <?php if ($cocTab === 'accounts'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật acc' : 'Thêm acc mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="price">Giá USD</label>
                                <input class="form-control" id="price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($editing['price'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="hall">Town Hall</label>
                                <input class="form-control" id="hall" name="hall" type="number" min="1" max="99" step="1" value="<?= htmlspecialchars((string) ($editing ? coc_account_hall($editing) : '')) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="avatar">Avatar URL</label>
                            <div class="input-group">
                                <input class="form-control" id="avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="avatar" data-type-media="coc_images" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" id="username" name="username" value="<?= htmlspecialchars($editing['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control" id="password" name="password" value="<?= htmlspecialchars($editing['password'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="photos">Photos URL, mỗi dòng một ảnh</label>
                            <div class="input-group">
                                <textarea class="form-control" id="photos" name="photos" rows="4"><?= htmlspecialchars($photoText) ?></textarea>
                                <button class="btn btn-secondary js-upload" type="button" data-target="photos" data-type-media="coc_images" data-mode="append" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="data">Data JSON Supercell</label>
                            <textarea class="form-control font-monospace small" id="data" name="data" rows="12" required><?= htmlspecialchars($editing['data'] ?? '') ?></textarea>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm acc' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách acc</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Acc', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('hall', 'Town Hall', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('price', 'Giá', $accountSort, $accountDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($accounts as $account):
                                    $th = coc_account_hall($account);
                                    ?>
                                    <tr>
                                        <td><?= (int) $account['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= htmlspecialchars($account['avatar']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <div>
                                                    <strong><?= htmlspecialchars($account['name']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars($account['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $th ?: 'N/A' ?></td>
                                        <td><?= coc_money($account['price']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=coc&edit=<?= (int) $account['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa acc này?">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$accounts): ?>
                                    <tr><td colspan="5" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($cocTab === 'orders'): ?>
            <div class="glass-panel p-4">
                <h2 class="h5 mb-3">Đơn Đặt hàng</h2>
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead>
                        <tr>
                            <th><?= admin_sort_link('id', 'ID', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('account', 'Acc', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('paypal_order_id', 'PayPal Order', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('status', 'Status', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('amount', 'Amount', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('payer_email', 'Payer', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('created_at', 'Created', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('paid_at', 'Paid', $orderSort, $orderDir) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= (int) $order['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($order['coc_name'] ?? ('#' . $order['coc_id'])) ?></strong>
                                    <div class="muted-text small"><?= htmlspecialchars($order['coc_username'] ?? '') ?></div>
                                </td>
                                <td class="font-monospace small"><?= htmlspecialchars($order['paypal_order_id']) ?></td>
                                <td><?= htmlspecialchars($order['status']) ?></td>
                                <td><?= coc_money($order['amount']) ?></td>
                                <td><?= htmlspecialchars($order['payer_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td><?= htmlspecialchars($order['paid_at'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$orders): ?>
                            <tr><td colspan="8" class="text-center muted-text py-4">Chưa có đơn đặt hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($section === 'apps'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_app">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật app' : 'Thêm app mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="id">ID</label>
                            <input class="form-control" id="id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="decription">Decription</label>
                            <textarea class="form-control" id="decription" name="decription" rows="4"><?= htmlspecialchars($editing['decription'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="type">Type</label>
                                <input class="form-control" id="type" name="type" value="<?= htmlspecialchars($editing['type'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <input class="form-control" id="status" name="status" value="<?= htmlspecialchars($editing['status'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="priority">Priority</label>
                                <input class="form-control" id="priority" name="priority" type="number" value="<?= htmlspecialchars((string) ($editing['priority'] ?? 0)) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="sync_status">Sync status</label>
                                <input class="form-control" id="sync_status" name="sync_status" type="number" value="<?= htmlspecialchars((string) ($editing['sync_status'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category">Category</label>
                                <input class="form-control" id="category" name="category" value="<?= htmlspecialchars($editing['category'] ?? '') ?>">
                            </div>
                        </div>

                        <?php
                        $appFields = [
                            'icon' => 'Icon URL',
                            'github' => 'Github',
                            'google_play' => 'Google Play',
                            'microsoft_store' => 'Microsoft Store',
                            'amazon_app_store' => 'Amazon App Store',
                            'huawei_store' => 'Huawei Store',
                            'itch' => 'Itch',
                            'uptodown' => 'Uptodown',
                            'simmer' => 'Simmer',
                            'youtube_link' => 'Youtube link',
                            'apk_file' => 'APK file',
                            'exe_file' => 'EXE file',
                            'deb_file' => 'DEB file',
                            'dmg_file' => 'DMG file',
                            'ipa_file' => 'IPA file',
                        ];
                        foreach ($appFields as $field => $label):
                        ?>
                            <div class="mb-3">
                                <label class="form-label" for="<?= $field ?>"><?= $label ?></label>
                                <?php if (in_array($field, ['icon', 'apk_file', 'exe_file', 'deb_file', 'dmg_file', 'ipa_file'], true)): ?>
                                    <div class="input-group">
                                        <input class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= htmlspecialchars($editing[$field] ?? '') ?>">
                                        <button class="btn btn-secondary js-upload" type="button" data-target="<?= $field ?>" data-type-media="carrot_app" data-mode="replace" data-accept="<?= $field === 'icon' ? 'image/*' : '' ?>">Upload</button>
                                    </div>
                                <?php else: ?>
                                    <input class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= htmlspecialchars($editing[$field] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm app' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách app</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('id', 'App', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('type', 'Type', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('status', 'Status', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('priority', 'Priority', $appSort, $appDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($apps as $app): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($app['id']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($app['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($app['icon']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($app['id']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars(admin_excerpt($app['decription'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($app['type']) ?></td>
                                        <td><?= htmlspecialchars($app['status']) ?></td>
                                        <td><?= (int) $app['priority'] ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                            <a class="btn btn-sm btn-warning" href="index.php?section=apps&edit=<?= urlencode($app['id']) ?>" title="Cập nhật" aria-label="Cập nhật">
                                                <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                            </a>
                                            <form class="js-delete" method="post" data-confirm="Xóa app này?">
                                                <input type="hidden" name="action" value="delete_app">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($app['id']) ?>">
                                                <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                    <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$apps): ?>
                                    <tr><td colspan="6" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($section === 'pages'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_page">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật page' : 'Thêm page mới' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="page_title">Title</label>
                                <input class="form-control" id="page_title" name="title" value="<?= htmlspecialchars($editing['title'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="page_lang">Lang key</label>
                                <input class="form-control" id="page_lang" name="lang" maxlength="12" value="<?= htmlspecialchars($editing['lang'] ?? 'vi') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-12">
                                <label class="form-label" for="page_slug">Slug</label>
                                <input class="form-control" id="page_slug" name="slug" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= htmlspecialchars($editing['slug'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="page_content_html">HTML content</label>
                            <div class="simple-editor-toolbar" aria-label="Editor toolbar">
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="bold"><strong>B</strong></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="italic"><em>I</em></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="formatBlock" data-editor-value="h2">H2</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="formatBlock" data-editor-value="p">P</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="insertUnorderedList">List</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="createLink">Link</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="removeFormat">Clear</button>
                            </div>
                            <div class="simple-editor-canvas" id="page_content_editor" contenteditable="true"><?= $editing['content_html'] ?? '<p></p>' ?></div>
                            <textarea class="form-control font-monospace d-none" id="page_content_html" name="content_html" required><?= htmlspecialchars($editing['content_html'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="seo_title">SEO title</label>
                            <input class="form-control" id="seo_title" name="seo_title" maxlength="255" value="<?= htmlspecialchars($editing['seo_title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="seo_description">SEO description</label>
                            <textarea class="form-control" id="seo_description" name="seo_description" maxlength="320" rows="2"><?= htmlspecialchars($editing['seo_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="seo_keywords">SEO keywords</label>
                            <input class="form-control" id="seo_keywords" name="seo_keywords" maxlength="500" value="<?= htmlspecialchars($editing['seo_keywords'] ?? '') ?>">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="page_status">Status</label>
                                <select class="form-control" id="page_status" name="status">
                                    <?php foreach (['draft', 'public', 'trash'] as $statusOption): ?>
                                        <option value="<?= $statusOption ?>" <?= ($editing['status'] ?? 'draft') === $statusOption ? 'selected' : '' ?>><?= ucfirst($statusOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="page_priority">Priority</label>
                                <input class="form-control" id="page_priority" name="priority" type="number" value="<?= htmlspecialchars((string) ($editing['priority'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="published_at">Published at</label>
                                <input class="form-control" id="published_at" name="published_at" type="datetime-local" value="<?= !empty($editing['published_at']) ? htmlspecialchars(str_replace(' ', 'T', substr($editing['published_at'], 0, 16))) : '' ?>">
                            </div>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm page' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách page</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $pageSort, $pageDir) ?></th>
                                    <th><?= admin_sort_link('title', 'Page', $pageSort, $pageDir) ?></th>
                                    <th><?= admin_sort_link('lang', 'Lang', $pageSort, $pageDir) ?></th>
                                    <th><?= admin_sort_link('status', 'Status', $pageSort, $pageDir) ?></th>
                                    <th><?= admin_sort_link('priority', 'Priority', $pageSort, $pageDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pages as $page): ?>
                                    <tr>
                                        <td><?= (int) $page['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($page['title']) ?></strong>
                                            <div class="muted-text small">
                                                <?= htmlspecialchars($page['slug']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($page['lang']) ?></td>
                                        <td><?= htmlspecialchars($page['status']) ?></td>
                                        <td><?= (int) $page['priority'] ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <?php if (($page['status'] ?? '') === 'public'): ?>
                                                    <a class="btn btn-sm btn-secondary" href="/index.php?page=<?= urlencode($page['slug']) ?>&lang=<?= urlencode($page['lang']) ?>" target="_blank" rel="noopener noreferrer" title="Xem page" aria-label="Xem page">
                                                        <i data-lucide="external-link" style="width:16px;height:16px"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a class="btn btn-sm btn-warning" href="index.php?section=pages&edit=<?= (int) $page['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa page này?">
                                                    <input type="hidden" name="action" value="delete_page">
                                                    <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$pages): ?>
                                    <tr><td colspan="6" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($section === 'bank'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_bank">
                        <input type="hidden" name="original_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật bank' : 'Thêm bank mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="bank_name">Name</label>
                            <input class="form-control" id="bank_name" name="name" maxlength="50" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="bank_avatar">Avatar URL</label>
                            <div class="input-group">
                                <input class="form-control" id="bank_avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="bank_avatar" data-type-media="bank" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="bank_banner">Banner URL</label>
                            <div class="input-group">
                                <input class="form-control" id="bank_banner" name="banner" value="<?= htmlspecialchars($editing['banner'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="bank_banner" data-type-media="bank" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="bank_qr">QR URL</label>
                            <div class="input-group">
                                <input class="form-control" id="bank_qr" name="qr" value="<?= htmlspecialchars($editing['qr'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="bank_qr" data-type-media="bank" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="account_name">Account name</label>
                                <input class="form-control" id="account_name" name="account_name" maxlength="20" value="<?= htmlspecialchars($editing['account_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="account_number">Account number</label>
                                <input class="form-control" id="account_number" name="account_number" maxlength="50" value="<?= htmlspecialchars($editing['account_number'] ?? '') ?>" required>
                            </div>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm bank' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách bank</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $bankSort, $bankDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Bank', $bankSort, $bankDir) ?></th>
                                    <th><?= admin_sort_link('account_name', 'Account name', $bankSort, $bankDir) ?></th>
                                    <th><?= admin_sort_link('account_number', 'Account number', $bankSort, $bankDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($banks as $bank): ?>
                                    <tr>
                                        <td><?= (int) $bank['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= htmlspecialchars($bank['avatar']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <div>
                                                    <strong><?= htmlspecialchars($bank['name']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars(admin_excerpt($bank['banner'] ?? '', 45)) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($bank['account_name']) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars($bank['account_number']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=bank&edit=<?= (int) $bank['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa bank này?">
                                                    <input type="hidden" name="action" value="delete_bank">
                                                    <input type="hidden" name="id" value="<?= (int) $bank['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$banks): ?>
                                    <tr><td colspan="5" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($section === 'country'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_country">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật country' : 'Thêm country mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="country_name">Name</label>
                            <input class="form-control" id="country_name" name="name" maxlength="120" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="country_icon">Icon URL</label>
                            <div class="input-group">
                                <input class="form-control" id="country_icon" name="icon" value="<?= htmlspecialchars($editing['icon'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="country_icon" data-type-media="country" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="country_lang_key">Lang key</label>
                                <input class="form-control" id="country_lang_key" name="lang_key" maxlength="24" placeholder="vi" value="<?= htmlspecialchars($editing['lang_key'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="country_lang_country">Lang country</label>
                                <input class="form-control text-uppercase" id="country_lang_country" name="lang_country" maxlength="24" placeholder="VN" value="<?= htmlspecialchars($editing['lang_country'] ?? '') ?>" required>
                            </div>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm country' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách country</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $countrySort, $countryDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Country', $countrySort, $countryDir) ?></th>
                                    <th><?= admin_sort_link('lang_key', 'Lang key', $countrySort, $countryDir) ?></th>
                                    <th><?= admin_sort_link('lang_country', 'Lang country', $countrySort, $countryDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($countries as $country): ?>
                                    <tr>
                                        <td><?= (int) $country['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($country['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($country['icon']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($country['name']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="font-monospace small"><?= htmlspecialchars($country['lang_key']) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars($country['lang_country']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=country&edit=<?= (int) $country['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa country này?">
                                                    <input type="hidden" name="action" value="delete_country">
                                                    <input type="hidden" name="id" value="<?= (int) $country['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$countries): ?>
                                    <tr><td colspan="5" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script>
document.querySelectorAll('.js-delete').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa',
            text: form.dataset.confirm || 'Bạn chắc chắn muốn xóa mục này?',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#dc3545',
        });

        if (result.isConfirmed) {
            form.submit();
        }
    });
});

document.querySelectorAll('.js-upload').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.target);
        if (!target) {
            return;
        }

        const accept = button.dataset.accept || '';
        const result = await Swal.fire({
            title: 'Upload file',
            html: `<input id="admin-upload-file" class="swal2-file" type="file" ${accept ? `accept="${accept}"` : ''}>`,
            showCancelButton: true,
            confirmButtonText: 'Upload',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            preConfirm: async () => {
                const fileInput = document.getElementById('admin-upload-file');
                const file = fileInput && fileInput.files ? fileInput.files[0] : null;
                if (!file) {
                    Swal.showValidationMessage('Vui lòng chọn file.');
                    return false;
                }

                const formData = new FormData();
                formData.append('action', 'ajax_upload');
                formData.append('type_media', button.dataset.typeMedia || 'coc_images');
                formData.append('file', file);

                try {
                    const response = await fetch('index.php', {
                        method: 'POST',
                        body: formData,
                    });
                    const payload = await response.json();
                    if (!response.ok || payload.status !== 'success') {
                        throw new Error(payload.message || 'Upload thất bại.');
                    }
                    return payload.url;
                } catch (error) {
                    Swal.showValidationMessage(error.message);
                    return false;
                }
            },
        });

        if (!result.isConfirmed || !result.value) {
            return;
        }

        if (button.dataset.mode === 'append') {
            const current = target.value.trim();
            target.value = current ? `${current}\n${result.value}` : result.value;
        } else {
            target.value = result.value;
        }

        Swal.fire({
            icon: 'success',
            title: 'Đã upload',
            text: result.value,
            timer: 1600,
            showConfirmButton: false,
        });
    });
});

const pageEditor = document.getElementById('page_content_editor');
const pageEditorSource = document.getElementById('page_content_html');
if (pageEditor && pageEditorSource) {
    document.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.dataset.editorCommand;
            let value = button.dataset.editorValue || null;

            if (command === 'createLink') {
                value = window.prompt('URL');
                if (!value) return;
            }

            pageEditor.focus();
            document.execCommand(command, false, value);
            pageEditorSource.value = pageEditor.innerHTML.trim();
        });
    });

    pageEditor.addEventListener('input', () => {
        pageEditorSource.value = pageEditor.innerHTML.trim();
    });

    const pageForm = pageEditor.closest('form');
    if (pageForm) {
        pageForm.addEventListener('submit', () => {
            pageEditorSource.value = pageEditor.innerHTML.trim();
        });
    }
}

const serverUptime = document.getElementById('server_uptime');
if (serverUptime) {
    const startedAt = Number(serverUptime.dataset.startedAt || 0) * 1000;
    const formatUptime = (totalSeconds) => {
        let seconds = Math.max(0, Math.floor(totalSeconds));
        const days = Math.floor(seconds / 86400);
        seconds %= 86400;
        const hours = Math.floor(seconds / 3600);
        seconds %= 3600;
        const minutes = Math.floor(seconds / 60);
        seconds %= 60;
        const pad = (value) => String(value).padStart(2, '0');
        return days > 0
            ? `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`
            : `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
    };
    const refreshUptime = () => {
        serverUptime.textContent = formatUptime((Date.now() - startedAt) / 1000);
    };

    refreshUptime();
    window.setInterval(refreshUptime, 1000);
}

if (window.lucide) {
    lucide.createIcons();
}
</script>
</body>
</html>
