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
    <style>
        body{background:#eef2f7;color:#172033}
        .glass-panel{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:rgba(255,255,255,.96);box-shadow:0 14px 36px rgba(15,23,42,.06)}
        .brand-mark{width:100%;height:56px;object-fit:contain}
    </style>
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
require __DIR__ . '/includes/schema.php';

$message = '';
$error = '';
$allowedSections = ['overview', 'apps', 'pages', 'bank', 'coc', 'country', 'paypal', 'ai_support'];
$section = in_array($_GET['section'] ?? 'overview', $allowedSections, true) ? ($_GET['section'] ?? 'overview') : 'overview';
$editKey = trim($_GET['edit'] ?? '');
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$cocTab = ($_GET['tab'] ?? 'accounts') === 'orders' ? 'orders' : 'accounts';
$appTab = in_array($_GET['tab'] ?? 'main', ['main', 'photos', 'content'], true) ? ($_GET['tab'] ?? 'main') : 'main';
$countryTab = ($_GET['tab'] ?? 'countries') === 'labels' ? 'labels' : 'countries';
$paypalTab = ($_GET['tab'] ?? 'home') === 'coc' ? 'coc' : 'home';
$editing = null;
$editingLabel = null;
$accounts = [];
$apps = [];
$pages = [];
$banks = [];
$countries = [];
$languageOptions = [];
$labelTranslationMap = [];
$labelKeyOptions = [];
$pageSlugOptions = [];
$textLabels = [];
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
$trafficIpRows = [];
$trafficChartData = admin_empty_visit_hourly();
$accountSort = 'id';
$accountDir = 'DESC';
$orderSort = 'created_at';
$orderDir = 'DESC';
$appSort = 'priority';
$appDir = 'DESC';
$pageSort = 'updated_at';
$pageDir = 'DESC';
$bankSort = 'id';
$bankDir = 'DESC';
$countrySort = 'id';
$countryDir = 'DESC';
$textLabelSort = 'key';
$textLabelDir = 'ASC';
$textLabelSearch = trim($_GET['label_q'] ?? '');
$textLabelPage = max(1, (int) ($_GET['label_page'] ?? 1));
$textLabelPerPage = 25;
$textLabelTotal = 0;
$textLabelTotalPages = 1;
$serverRuntime = null;

if (($_GET['duplicate'] ?? '') === 'page') {
    $message = 'Page với slug và lang này đã tồn tại. Đã mở dữ liệu hiện có để chỉnh sửa.';
}

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
        if (!in_array($typeMedia, ['carrot_app', 'carrot_app_photo', 'coc_images', 'bank', 'country'], true)) {
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

function admin_fetch_text_label(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM text_label WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_paypal_config(PDO $pdo, string $site): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM paypal_config WHERE site = ?');
    $stmt->execute([$site]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_ai_support_config(PDO $pdo): ?array
{
    admin_ensure_ai_support_table($pdo);
    $stmt = $pdo->prepare('SELECT * FROM ai_support WHERE provider = ? ORDER BY priority ASC, id ASC LIMIT 1');
    $stmt->execute(['gemini']);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_ai_support_configs(PDO $pdo, bool $enabledOnly = false): array
{
    admin_ensure_ai_support_table($pdo);
    $sql = 'SELECT * FROM ai_support WHERE provider = ?';
    if ($enabledOnly) {
        $sql .= ' AND enabled = 1 AND TRIM(COALESCE(api_key, "")) <> ""';
    }
    $sql .= ' ORDER BY priority ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['gemini']);
    return $stmt->fetchAll();
}

function admin_mask_secret(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $suffix = substr($value, -4);
    return str_repeat('•', 8) . $suffix;
}

function admin_json_success(array $payload = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => 'success'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function admin_json_error(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function admin_gemini_translate(PDO $pdo, string $sourceText, string $targetLang, string $contentType): string
{
    $configs = admin_fetch_ai_support_configs($pdo, true);
    if (!$configs) {
        throw new RuntimeException('AI Support chưa được bật.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('Server cần bật PHP cURL để gọi Gemini.');
    }

    $errors = [];
    foreach ($configs as $config) {
        $accountName = trim((string) ($config['account_name'] ?? 'Gemini account')) ?: 'Gemini account';
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        if ($apiKey === '') {
            continue;
        }

        $configuredModel = trim((string) ($config['model'] ?? 'gemini-3.5-flash')) ?: 'gemini-3.5-flash';
        $models = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-3.5-flash',
            'gemini-3.1-flash-lite',
            'gemini-2.5-flash-lite',
        ])));
        $endpointTemplate = trim((string) ($config['endpoint'] ?? '')) ?: 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';
        $temperature = max(0, min(2, (float) ($config['temperature'] ?? 0.2)));
        $systemPrompt = trim((string) ($config['system_prompt'] ?? ''));
        if ($systemPrompt === '') {
            $systemPrompt = 'You are a precise translation engine for a website CMS. Translate from English to the target language. Preserve HTML tags, attributes, URLs, whitespace intent, entities, and placeholders. Return only the translated content without markdown fences or explanations.';
        }

        $prompt = $systemPrompt . "\n\nTarget language: " . $targetLang . "\nContent type: " . $contentType . "\n\nSource content:\n" . $sourceText;
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
            ],
        ];

        foreach ($models as $model) {
            $endpoint = str_replace('{model}', rawurlencode($model), $endpointTemplate);
            $curl = curl_init($endpoint);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 90,
            ]);

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);

            if ($response === false) {
                $errors[] = $accountName . ' / ' . $model . ': ' . $curlError;
                continue;
            }

            $data = json_decode($response, true);
            if ($statusCode >= 400 || !is_array($data)) {
                $apiMessage = is_array($data) ? ($data['error']['message'] ?? 'unknown error') : trim($response);
                $errors[] = $accountName . ' / ' . $model . ': ' . $apiMessage;
                $retryable = in_array($statusCode, [401, 403, 404, 429, 503], true) || preg_match('/api key|permission|denied|high demand|overload|rate limit|quota|unavailable|try again|not found/i', $apiMessage);
                if ($retryable) {
                    usleep(300000);
                    continue;
                }

                throw new RuntimeException('Gemini lỗi: ' . $apiMessage);
            }

            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            $text = '';
            foreach ($parts as $part) {
                $text .= (string) ($part['text'] ?? '');
            }

            $text = trim($text);
            if ($text !== '') {
                return preg_replace('/^```(?:html|text)?\s*|\s*```$/i', '', $text) ?? $text;
            }

            $errors[] = $accountName . ' / ' . $model . ': empty response';
        }
    }

    throw new RuntimeException('Gemini lỗi sau khi thử tất cả tài khoản: ' . implode(' | ', $errors));
}

function admin_fetch_language_options(?PDO $pdo): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }

    try {
        return $pdo->query('
            SELECT
              lang_key,
              MIN(lang_country) AS lang_country,
              GROUP_CONCAT(name ORDER BY name ASC SEPARATOR ", ") AS countries,
              COUNT(*) AS country_count
            FROM country
            WHERE lang_key <> ""
            GROUP BY lang_key
            ORDER BY lang_key ASC
        ')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function admin_language_select_options(array $languageOptions, string $selectedValue): string
{
    $html = '';
    $hasSelectedValue = $selectedValue === '';

    foreach ($languageOptions as $language) {
        $langKey = (string) ($language['lang_key'] ?? '');
        if ($langKey === '') {
            continue;
        }

        $isSelected = $langKey === $selectedValue;
        $hasSelectedValue = $hasSelectedValue || $isSelected;
        $labelParts = [$langKey];
        if (!empty($language['countries'])) {
            $labelParts[] = (string) $language['countries'];
        } elseif (!empty($language['name'])) {
            $labelParts[] = (string) $language['name'];
        } elseif (!empty($language['lang_country'])) {
            $labelParts[] = (string) $language['lang_country'];
        }

        $html .= '<option value="' . htmlspecialchars($langKey) . '"' . ($isSelected ? ' selected' : '') . '>' .
            htmlspecialchars(implode(' - ', $labelParts)) . '</option>';
    }

    if (!$hasSelectedValue) {
        $html = '<option value="' . htmlspecialchars($selectedValue) . '" selected>' . htmlspecialchars($selectedValue . ' - Giá trị hiện tại') . '</option>' . $html;
    }

    return $html;
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

function admin_fetch_page_by_slug_lang(PDO $pdo, string $slug, string $lang): ?array
{
    $stmt = $pdo->prepare('
        SELECT id, slug, lang, title
        FROM page
        WHERE slug = ? AND lang = ?
        LIMIT 1
    ');
    $stmt->execute([$slug, $lang]);
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
    unset($params['label_page']);

    $icon = '&harr;';
    if ($activeSort === $key) {
        $icon = $activeDir === 'ASC' ? '&uarr;' : '&darr;';
    }

    return '<a class="text-reset text-decoration-none d-inline-flex align-items-center gap-1" href="index.php?' .
        htmlspecialchars(http_build_query($params)) . '">' . htmlspecialchars($label) . '<span>' . $icon . '</span></a>';
}

function admin_page_url(array $params): string
{
    return 'index.php?' . htmlspecialchars(http_build_query($params));
}

function admin_app_detail_url(string $appId): string
{
    $slug = preg_replace('/\s+/u', '-', trim($appId));
    return 'https://home.carrot28.com/' . rawurlencode($slug ?: $appId);
}

function admin_pagination(array $params, string $pageKey, int $currentPage, int $totalPages, string $label, string $class = 'mt-3'): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $pageStart = max(1, $currentPage - 2);
    $pageEnd = min($totalPages, $currentPage + 2);
    $html = '<nav class="d-flex flex-wrap justify-content-end gap-2 ' . htmlspecialchars($class) . '" aria-label="' . htmlspecialchars($label) . '">';
    $html .= '<a class="btn btn-sm btn-light ' . ($currentPage <= 1 ? 'disabled' : '') . '" href="' . admin_page_url(array_merge($params, [$pageKey => max(1, $currentPage - 1)])) . '">Trước</a>';

    for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++) {
        $html .= '<a class="btn btn-sm ' . ($pageNumber === $currentPage ? 'btn-dark' : 'btn-light') . '" href="' . admin_page_url(array_merge($params, [$pageKey => $pageNumber])) . '">' . number_format($pageNumber) . '</a>';
    }

    $html .= '<a class="btn btn-sm btn-light ' . ($currentPage >= $totalPages ? 'disabled' : '') . '" href="' . admin_page_url(array_merge($params, [$pageKey => min($totalPages, $currentPage + 1)])) . '">Sau</a>';
    $html .= '</nav>';

    return $html;
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

function admin_empty_visit_hourly(): array
{
    return [
        'labels' => array_map(static fn(int $hour): string => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23)),
        'today' => array_fill(0, 24, 0),
        'yesterday' => array_fill(0, 24, 0),
    ];
}

function admin_visit_hourly(?PDO $pdo, string $site): array
{
    $series = admin_empty_visit_hourly();
    if (!$pdo instanceof PDO) {
        return $series;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
              visit_date,
              HOUR(last_seen_at) AS visit_hour,
              COALESCE(SUM(hits), 0) AS hits
            FROM visit_daily_ip
            WHERE site = :site
              AND visit_date IN (CURRENT_DATE, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY))
            GROUP BY visit_date, visit_hour
            ORDER BY visit_date, visit_hour
        ");
        $stmt->execute([':site' => $site]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return $series;
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    foreach ($rows as $row) {
        $hour = max(0, min(23, (int) ($row['visit_hour'] ?? 0)));
        $hits = (int) ($row['hits'] ?? 0);
        if (($row['visit_date'] ?? '') === $today) {
            $series['today'][$hour] += $hits;
        } elseif (($row['visit_date'] ?? '') === $yesterday) {
            $series['yesterday'][$hour] += $hits;
        }
    }

    return $series;
}

function admin_sum_visit_hourly(array $items): array
{
    $total = admin_empty_visit_hourly();
    foreach ($items as $item) {
        for ($hour = 0; $hour < 24; $hour++) {
            $total['today'][$hour] += (int) ($item['today'][$hour] ?? 0);
            $total['yesterday'][$hour] += (int) ($item['yesterday'][$hour] ?? 0);
        }
    }
    return $total;
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

function admin_visit_ip_rows(?PDO $pdo, string $site, string $label): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
              ip_text,
              SUM(CASE WHEN visit_date = CURRENT_DATE THEN hits ELSE 0 END) AS today_hits,
              SUM(CASE WHEN visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) THEN hits ELSE 0 END) AS week_hits,
              COALESCE(SUM(hits), 0) AS total_hits,
              COUNT(*) AS visit_days,
              MAX(last_seen_at) AS last_seen_at,
              SUBSTRING_INDEX(GROUP_CONCAT(request_path ORDER BY last_seen_at DESC SEPARATOR '\\n'), '\\n', 1) AS request_path,
              SUBSTRING_INDEX(GROUP_CONCAT(user_agent ORDER BY last_seen_at DESC SEPARATOR '\\n'), '\\n', 1) AS user_agent
            FROM visit_daily_ip
            WHERE site = :site
            GROUP BY ip_text
            ORDER BY total_hits DESC, last_seen_at DESC
            LIMIT 100
        ");
        $stmt->execute([':site' => $site]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    foreach ($rows as &$row) {
        $row['site_label'] = $label;
    }
    unset($row);

    return $rows;
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
            $trafficChartData = admin_sum_visit_hourly([
                admin_visit_hourly($pdo, 'coc'),
                admin_visit_hourly($overviewHomePdo, 'home'),
            ]);
            $trafficIpRows = array_merge(
                admin_visit_ip_rows($pdo, 'coc', 'COC Shop'),
                admin_visit_ip_rows($overviewHomePdo, 'home', 'CarrotHome')
            );
            usort($trafficIpRows, static function (array $a, array $b): int {
                $hitsCompare = (int) ($b['total_hits'] ?? 0) <=> (int) ($a['total_hits'] ?? 0);
                if ($hitsCompare !== 0) {
                    return $hitsCompare;
                }
                return strcmp((string) ($b['last_seen_at'] ?? ''), (string) ($a['last_seen_at'] ?? ''));
            });
        }

        if (in_array($section, ['apps', 'pages', 'country'], true)) {
            $languageOptions = admin_fetch_language_options($pdo instanceof PDO ? $pdo : null);
        }

        if ($section === 'paypal') {
            admin_ensure_paypal_config_table($pdo);
        }

        if ($section === 'ai_support' || in_array($_POST['action'] ?? '', ['save_ai_support_config', 'ajax_ai_translate_page', 'ajax_ai_translate_label', 'ajax_find_text_label_source', 'ajax_find_app_content_source', 'ajax_ai_translate_app_content'], true)) {
            admin_ensure_ai_support_table($pdo);
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

            if ($section === 'apps' && $action === 'delete_app_photo') {
                $stmt = $pdo->prepare('DELETE FROM app_photo WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa ảnh mô tả.';
            }

            if ($section === 'apps' && $action === 'delete_app_content') {
                $stmt = $pdo->prepare('DELETE FROM app_content WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa nội dung mô tả.';
            }

            if ($section === 'apps' && $action === 'save_app') {
                $originalId = trim($_POST['original_id'] ?? '');
                $id = trim($_POST['id'] ?? '');
                $decription = trim($_POST['decription'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $status = trim($_POST['status'] ?? '');
                $priority = (int) ($_POST['priority'] ?? 0);
                $price = (float) ($_POST['price'] ?? 0);

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
                    $stmt = $pdo->prepare('UPDATE app SET id = ?, decription = ?, github = ?, microsoft_store = ?, icon = ?, itch = ?, exe_file = ?, ipa_file = ?, deb_file = ?, amazon_app_store = ?, huawei_store = ?, youtube_link = ?, google_play = ?, dmg_file = ?, uptodown = ?, simmer = ?, type = ?, apk_file = ?, status = ?, priority = ?, price = ?, category = ? WHERE id = ?');
                    $stmt->execute([$id, $decription, $values['github'], $values['microsoft_store'], $values['icon'], $values['itch'], $values['exe_file'], $values['ipa_file'], $values['deb_file'], $values['amazon_app_store'], $values['huawei_store'], $values['youtube_link'], $values['google_play'], $values['dmg_file'], $values['uptodown'], $values['simmer'], $type, $values['apk_file'], $status, $priority, $price, $values['category'], $originalId]);
                    $message = 'Đã cập nhật app.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app (id, decription, github, microsoft_store, icon, itch, exe_file, ipa_file, deb_file, amazon_app_store, huawei_store, youtube_link, google_play, dmg_file, uptodown, simmer, type, apk_file, status, priority, price, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$id, $decription, $values['github'], $values['microsoft_store'], $values['icon'], $values['itch'], $values['exe_file'], $values['ipa_file'], $values['deb_file'], $values['amazon_app_store'], $values['huawei_store'], $values['youtube_link'], $values['google_play'], $values['dmg_file'], $values['uptodown'], $values['simmer'], $type, $values['apk_file'], $status, $priority, $price, $values['category']]);
                    $message = 'Đã thêm app mới.';
                }
            }

            if ($section === 'apps' && $action === 'save_app_photo') {
                $id = (int) ($_POST['id'] ?? 0);
                $appId = trim($_POST['app_id'] ?? '');
                $imageUrl = trim($_POST['image_url'] ?? '');
                $displayMode = trim($_POST['display_mode'] ?? 'vertical');
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);

                if ($appId === '' || $imageUrl === '') {
                    throw new RuntimeException('Vui lòng chọn app và nhập ảnh mô tả.');
                }

                if (!in_array($displayMode, ['vertical', 'horizontal'], true)) {
                    $displayMode = 'vertical';
                }

                if (!admin_fetch_app($pdo, $appId)) {
                    throw new RuntimeException('App được chọn không tồn tại.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE app_photo SET app_id = ?, image_url = ?, display_mode = ?, sort_order = ? WHERE id = ?');
                    $stmt->execute([$appId, $imageUrl, $displayMode, $sortOrder, $id]);
                    $message = 'Đã cập nhật ảnh mô tả.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app_photo (app_id, image_url, display_mode, sort_order) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$appId, $imageUrl, $displayMode, $sortOrder]);
                    $message = 'Đã thêm ảnh mô tả.';
                }
            }

            if ($section === 'apps' && $action === 'save_app_content') {
                $id = (int) ($_POST['id'] ?? 0);
                $appId = trim($_POST['app_id'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? '');
                $contentHtml = trim($_POST['content_html'] ?? '');

                if ($appId === '' || $langKey === '' || $contentHtml === '') {
                    throw new RuntimeException('Vui lòng chọn app, lang_key và nhập nội dung mô tả.');
                }

                if (!preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z]{2})?$/', $langKey)) {
                    throw new RuntimeException('Lang key nên có dạng vi, en, en-US hoặc en_US.');
                }

                if (!admin_fetch_app($pdo, $appId)) {
                    throw new RuntimeException('App được chọn không tồn tại.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE app_content SET app_id = ?, lang_key = ?, content_html = ? WHERE id = ?');
                    $stmt->execute([$appId, $langKey, $contentHtml, $id]);
                    $message = 'Đã cập nhật nội dung mô tả.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO app_content (app_id, lang_key, content_html)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE content_html = VALUES(content_html)
                    ');
                    $stmt->execute([$appId, $langKey, $contentHtml]);
                    $message = 'Đã lưu nội dung mô tả.';
                }
            }

            if ($section === 'apps' && $action === 'ajax_find_app_content_source') {
                $appId = trim($_POST['app_id'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? '');
                if ($appId === '' || $langKey === '' || $langKey === 'en') {
                    admin_json_success(['source_en' => null]);
                }

                $stmt = $pdo->prepare('
                    SELECT id, app_id, lang_key
                    FROM app_content
                    WHERE app_id = ? AND lang_key = "en" AND TRIM(content_html) <> ""
                    LIMIT 1
                ');
                $stmt->execute([$appId]);
                admin_json_success(['source_en' => $stmt->fetch() ?: null]);
            }

            if ($section === 'apps' && $action === 'ajax_ai_translate_app_content') {
                try {
                    $appId = trim($_POST['app_id'] ?? '');
                    $langKey = trim($_POST['lang_key'] ?? '');
                    if ($appId === '' || $langKey === '' || $langKey === 'en') {
                        throw new RuntimeException('Vui lòng chọn app và lang khác en.');
                    }

                    $stmt = $pdo->prepare('
                        SELECT content_html
                        FROM app_content
                        WHERE app_id = ? AND lang_key = "en" AND TRIM(content_html) <> ""
                        LIMIT 1
                    ');
                    $stmt->execute([$appId]);
                    $sourceHtml = (string) $stmt->fetchColumn();
                    if ($sourceHtml === '') {
                        throw new RuntimeException('Chưa có mô tả app tiếng Anh để dịch.');
                    }

                    $translated = admin_gemini_translate($pdo, $sourceHtml, $langKey, 'app description html');
                    admin_json_success(['content_html' => $translated]);
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'paypal' && $action === 'save_paypal_config') {
                $site = ($_POST['site'] ?? '') === 'coc' ? 'coc' : 'home';
                $enabled = isset($_POST['enabled']) ? 1 : 0;
                $activeMode = ($_POST['active_mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
                $currency = strtoupper(trim($_POST['currency'] ?? 'USD')) ?: 'USD';
                $amount = (float) ($_POST['amount'] ?? 0);
                $sandboxClientId = trim($_POST['sandbox_client_id'] ?? '');
                $sandboxClientSecret = trim($_POST['sandbox_client_secret'] ?? '');
                $liveClientId = trim($_POST['live_client_id'] ?? '');
                $liveClientSecret = trim($_POST['live_client_secret'] ?? '');

                if (!preg_match('/^[A-Z]{3,8}$/', $currency)) {
                    throw new RuntimeException('Currency không hợp lệ, ví dụ USD.');
                }

                $stmt = $pdo->prepare('
                    INSERT INTO paypal_config
                        (site, enabled, active_mode, sandbox_client_id, sandbox_client_secret, live_client_id, live_client_secret, currency, amount)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        enabled = VALUES(enabled),
                        active_mode = VALUES(active_mode),
                        sandbox_client_id = VALUES(sandbox_client_id),
                        sandbox_client_secret = VALUES(sandbox_client_secret),
                        live_client_id = VALUES(live_client_id),
                        live_client_secret = VALUES(live_client_secret),
                        currency = VALUES(currency),
                        amount = VALUES(amount)
                ');
                $stmt->execute([$site, $enabled, $activeMode, $sandboxClientId, $sandboxClientSecret, $liveClientId, $liveClientSecret, $currency, $amount]);
                $message = 'Đã lưu cấu hình PayPal.';
                $paypalTab = $site;
            }

            if ($section === 'ai_support' && $action === 'save_ai_support_config') {
                $id = (int) ($_POST['id'] ?? 0);
                $accountName = trim($_POST['account_name'] ?? 'Gemini account') ?: 'Gemini account';
                $enabled = isset($_POST['enabled']) ? 1 : 0;
                $apiKey = trim($_POST['api_key'] ?? '');
                $model = trim($_POST['model'] ?? 'gemini-3.5-flash') ?: 'gemini-3.5-flash';
                $endpoint = trim($_POST['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent');
                $temperature = max(0, min(2, (float) ($_POST['temperature'] ?? 0.2)));
                $systemPrompt = trim($_POST['system_prompt'] ?? '');
                $priority = (int) ($_POST['priority'] ?? 0);

                if ($endpoint === '' || strpos($endpoint, '{model}') === false) {
                    throw new RuntimeException('Endpoint cần có placeholder {model}.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('
                        UPDATE ai_support
                        SET account_name = ?, enabled = ?, api_key = ?, model = ?, endpoint = ?, temperature = ?, system_prompt = ?, priority = ?
                        WHERE id = ? AND provider = "gemini"
                    ');
                    $stmt->execute([$accountName, $enabled, $apiKey, $model, $endpoint, $temperature, $systemPrompt, $priority, $id]);
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO ai_support
                            (provider, account_name, enabled, api_key, model, endpoint, temperature, system_prompt, priority)
                        VALUES ("gemini", ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$accountName, $enabled, $apiKey, $model, $endpoint, $temperature, $systemPrompt, $priority]);
                }
                $message = 'Đã lưu cấu hình AI Support.';
            }

            if ($section === 'ai_support' && $action === 'delete_ai_support_config') {
                $stmt = $pdo->prepare('DELETE FROM ai_support WHERE id = ? AND provider = "gemini"');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa tài khoản AI.';
            }

            if ($section === 'pages' && $action === 'delete_page') {
                $stmt = $homePdo->prepare('DELETE FROM page WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa page.';
            }

            if ($section === 'pages' && $action === 'ajax_find_page') {
                header('Content-Type: application/json; charset=utf-8');
                $slug = trim($_POST['slug'] ?? '');
                $lang = trim($_POST['lang'] ?? 'vi');

                if ($slug === '' || $lang === '') {
                    echo json_encode(['status' => 'success', 'page' => null], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $page = admin_fetch_page_by_slug_lang($homePdo, $slug, $lang);
                $sourcePage = null;
                if ($lang !== 'en') {
                    $sourceStmt = $homePdo->prepare('
                        SELECT id, slug, lang, title
                        FROM page
                        WHERE slug = ? AND lang = "en" AND TRIM(content_html) <> ""
                        LIMIT 1
                    ');
                    $sourceStmt->execute([$slug]);
                    $sourcePage = $sourceStmt->fetch() ?: null;
                }
                admin_json_success(['page' => $page, 'source_en' => $sourcePage]);
            }

            if ($section === 'pages' && $action === 'ajax_ai_translate_page') {
                try {
                    $slug = trim($_POST['slug'] ?? '');
                    $lang = trim($_POST['lang'] ?? '');
                    if ($slug === '' || $lang === '' || $lang === 'en') {
                        throw new RuntimeException('Vui lòng chọn slug và lang khác en.');
                    }

                    $sourcePage = admin_fetch_page_by_slug_lang($homePdo, $slug, 'en');
                    if (!$sourcePage) {
                        throw new RuntimeException('Chưa có page tiếng Anh để dịch.');
                    }

                    $sourceDetail = admin_fetch_page($homePdo, (int) $sourcePage['id']);
                    $translated = [
                        'content_html' => admin_gemini_translate($pdo, (string) ($sourceDetail['content_html'] ?? ''), $lang, 'html'),
                        'seo_title' => '',
                        'seo_description' => '',
                        'seo_keywords' => '',
                    ];

                    foreach (['seo_title' => 'SEO title', 'seo_description' => 'SEO description', 'seo_keywords' => 'SEO keywords'] as $field => $label) {
                        $sourceValue = trim((string) ($sourceDetail[$field] ?? ''));
                        if ($sourceValue !== '') {
                            $translated[$field] = admin_gemini_translate($pdo, $sourceValue, $lang, $label);
                        }
                    }

                    admin_json_success(array_merge($translated, ['source' => $sourcePage]));
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
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
                if ($slug === '' || $title === '' || $contentHtml === '') {
                    throw new RuntimeException('Vui lòng nhập slug, title và nội dung HTML.');
                }

                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                    throw new RuntimeException('Slug chỉ dùng chữ thường, số và dấu gạch ngang.');
                }

                if ($languageOptions && !in_array($lang, array_column($languageOptions, 'lang_key'), true)) {
                    throw new RuntimeException('Lang key không có trong danh sách country.');
                }

                $existingPage = admin_fetch_page_by_slug_lang($homePdo, $slug, $lang);
                if ($existingPage && (int) $existingPage['id'] !== $id) {
                    header('Location: index.php?section=pages&edit=' . (int) $existingPage['id'] . '&duplicate=page');
                    exit;
                }

                if ($id > 0) {
                    $stmt = $homePdo->prepare('UPDATE page SET slug = ?, lang = ?, title = ?, content_html = ?, seo_title = ?, seo_description = ?, seo_keywords = ? WHERE id = ?');
                    $stmt->execute([$slug, $lang, $title, $contentHtml, $seoTitle, $seoDescription, $seoKeywords, $id]);
                    $message = 'Đã cập nhật page.';
                } else {
                    $stmt = $homePdo->prepare('INSERT INTO page (slug, lang, title, content_html, seo_title, seo_description, seo_keywords) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$slug, $lang, $title, $contentHtml, $seoTitle, $seoDescription, $seoKeywords]);
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

            if ($section === 'country' && $action === 'delete_text_label') {
                $stmt = $pdo->prepare('DELETE FROM text_label WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa nhãn.';
            }

            if ($section === 'country' && $action === 'ajax_find_text_label_source') {
                $labelKey = trim($_POST['key'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? '');
                if ($labelKey === '' || $langKey === '' || $langKey === 'en') {
                    admin_json_success(['source_en' => null]);
                }

                $stmt = $pdo->prepare('SELECT id, `key`, lang_key, value FROM text_label WHERE `key` = ? AND lang_key = "en" AND TRIM(value) <> "" LIMIT 1');
                $stmt->execute([$labelKey]);
                admin_json_success(['source_en' => $stmt->fetch() ?: null]);
            }

            if ($section === 'country' && $action === 'ajax_ai_translate_label') {
                try {
                    $labelKey = trim($_POST['key'] ?? '');
                    $langKey = trim($_POST['lang_key'] ?? '');
                    if ($labelKey === '' || $langKey === '' || $langKey === 'en') {
                        throw new RuntimeException('Vui lòng chọn key và lang khác en.');
                    }

                    $stmt = $pdo->prepare('SELECT value FROM text_label WHERE `key` = ? AND lang_key = "en" LIMIT 1');
                    $stmt->execute([$labelKey]);
                    $sourceValue = (string) $stmt->fetchColumn();
                    if ($sourceValue === '') {
                        throw new RuntimeException('Chưa có nhãn tiếng Anh để dịch.');
                    }

                    $translated = admin_gemini_translate($pdo, $sourceValue, $langKey, 'plain text label');
                    admin_json_success(['value' => $translated]);
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
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

            if ($section === 'country' && $action === 'save_text_label') {
                $id = (int) ($_POST['id'] ?? 0);
                $labelKey = trim($_POST['key'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? '');
                $value = trim($_POST['value'] ?? '');

                if ($labelKey === '' || $langKey === '' || $value === '') {
                    throw new RuntimeException('Vui lòng nhập đủ key, lang_key và value.');
                }

                if (!preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $labelKey)) {
                    throw new RuntimeException('Key chỉ dùng chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.');
                }

                if (!preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z]{2})?$/', $langKey)) {
                    throw new RuntimeException('Lang key nên có dạng vi, en, en-US hoặc en_US.');
                }

                if ($languageOptions && !in_array($langKey, array_column($languageOptions, 'lang_key'), true)) {
                    throw new RuntimeException('Lang key không có trong danh sách country.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE text_label SET `key` = ?, lang_key = ?, value = ? WHERE id = ?');
                    $stmt->execute([$labelKey, $langKey, $value, $id]);
                    $message = 'Đã cập nhật nhãn.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO text_label (`key`, lang_key, value) VALUES (?, ?, ?)');
                    $stmt->execute([$labelKey, $langKey, $value]);
                    $message = 'Đã thêm nhãn mới.';
                }
            }

            if ($section === 'country' && $action === 'save_text_label_batch') {
                $labelKey = trim($_POST['key'] ?? '');
                $translations = $_POST['translations'] ?? [];

                if ($labelKey === '') {
                    throw new RuntimeException('Vui lòng chọn key cần cập nhật.');
                }

                if (!preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $labelKey)) {
                    throw new RuntimeException('Key chỉ dùng chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.');
                }

                if (!is_array($translations)) {
                    throw new RuntimeException('Danh sách bản dịch không hợp lệ.');
                }

                $allowedLangKeys = array_column($languageOptions, 'lang_key');
                $pdo->beginTransaction();
                try {
                    foreach ($translations as $langKey => $value) {
                        $langKey = trim((string) $langKey);
                        $value = trim((string) $value);

                        if (!in_array($langKey, $allowedLangKeys, true)) {
                            continue;
                        }

                        if ($value === '') {
                            $stmt = $pdo->prepare('DELETE FROM text_label WHERE `key` = ? AND lang_key = ?');
                            $stmt->execute([$labelKey, $langKey]);
                            continue;
                        }

                        $stmt = $pdo->prepare('
                            INSERT INTO text_label (`key`, lang_key, value)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE value = VALUES(value)
                        ');
                        $stmt->execute([$labelKey, $langKey, $value]);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
                }

                if (($_POST['ajax'] ?? '') === '1') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật danh sách phiên dịch.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $message = 'Đã cập nhật danh sách phiên dịch.';
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

        if ($section === 'country' && $countryTab === 'countries' && $editId > 0) {
            $editing = admin_fetch_country($pdo, $editId);
        }

        if ($section === 'country' && $countryTab === 'labels' && $editId > 0) {
            $editingLabel = admin_fetch_text_label($pdo, $editId);
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
            'price' => 'price',
            'created_at' => 'created_at',
        ];
        [$appSort, $appDir] = admin_sort_state($appSortColumns, 'priority', 'DESC');

        $pageSortColumns = [
            'id' => 'id',
            'title' => 'title',
            'slug' => 'slug',
            'lang' => 'lang',
            'updated_at' => 'updated_at',
        ];
        [$pageSort, $pageDir] = admin_sort_state($pageSortColumns, 'updated_at', 'DESC');

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

        $textLabelSortColumns = [
            'id' => 'id',
            'key' => '`key`',
            'lang_key' => 'lang_key',
            'updated_at' => 'updated_at',
        ];
        [$textLabelSort, $textLabelDir] = admin_sort_state($textLabelSortColumns, 'key', 'ASC');

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
        if ($section === 'pages') {
            $pageSlugOptions = array_values(array_unique(array_filter(array_map(static fn(array $page): string => (string) ($page['slug'] ?? ''), $pages))));
            sort($pageSlugOptions, SORT_NATURAL | SORT_FLAG_CASE);
        }
        $banks = $section === 'bank'
            ? $pdo->query('SELECT * FROM bank ORDER BY ' . admin_order_by($bankSortColumns, $bankSort, $bankDir))->fetchAll()
            : [];
        $countries = $section === 'country'
            ? $pdo->query('SELECT * FROM country ORDER BY ' . admin_order_by($countrySortColumns, $countrySort, $countryDir))->fetchAll()
            : [];
        $textLabels = ($section === 'country' && $countryTab === 'labels')
            ? []
            : [];
        if ($section === 'country' && $countryTab === 'labels') {
            $labelSearchWhere = '';
            $labelSearchParams = [];
            if ($textLabelSearch !== '') {
                $labelSearchWhere = ' WHERE `key` LIKE :label_q';
                $labelSearchParams[':label_q'] = '%' . $textLabelSearch . '%';
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM text_label' . $labelSearchWhere);
            $countStmt->execute($labelSearchParams);
            $textLabelTotal = (int) $countStmt->fetchColumn();
            $textLabelTotalPages = max(1, (int) ceil($textLabelTotal / $textLabelPerPage));
            $textLabelPage = min($textLabelPage, $textLabelTotalPages);
            $textLabelOffset = ($textLabelPage - 1) * $textLabelPerPage;

            $labelStmt = $pdo->prepare(
                'SELECT * FROM text_label' . $labelSearchWhere .
                ' ORDER BY ' . admin_order_by($textLabelSortColumns, $textLabelSort, $textLabelDir) . ', lang_key ASC LIMIT :limit OFFSET :offset'
            );
            foreach ($labelSearchParams as $paramKey => $paramValue) {
                $labelStmt->bindValue($paramKey, $paramValue);
            }
            $labelStmt->bindValue(':limit', $textLabelPerPage, PDO::PARAM_INT);
            $labelStmt->bindValue(':offset', $textLabelOffset, PDO::PARAM_INT);
            $labelStmt->execute();
            $textLabels = $labelStmt->fetchAll();

            $allLabelRows = $pdo->query('SELECT `key`, lang_key, value FROM text_label ORDER BY `key` ASC, lang_key ASC')->fetchAll();
            foreach ($allLabelRows as $label) {
                $labelKey = (string) ($label['key'] ?? '');
                $langKey = (string) ($label['lang_key'] ?? '');
                if ($labelKey !== '' && $langKey !== '') {
                    $labelTranslationMap[$labelKey][$langKey] = (string) ($label['value'] ?? '');
                }
            }
            $labelKeyOptions = array_keys($labelTranslationMap);
            sort($labelKeyOptions, SORT_NATURAL | SORT_FLAG_CASE);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $accounts = [];
        $apps = [];
        $pages = [];
        $banks = [];
        $countries = [];
        $languageOptions = [];
        $labelTranslationMap = [];
        $labelKeyOptions = [];
        $pageSlugOptions = [];
        $textLabels = [];
        $textLabelTotal = 0;
        $textLabelTotalPages = 1;
        $orders = [];
        $trafficIpRows = [];
    }
}

$photoText = ($section === 'coc' && $editing) ? implode("\n", coc_decode_photos($editing['photos'])) : '';
$pageTitle = ['overview' => 'Tổng quan', 'apps' => 'App', 'pages' => 'Page', 'bank' => 'Bank', 'coc' => 'Coc', 'country' => 'Country', 'paypal' => 'Paypal', 'ai_support' => 'AI Support'][$section] ?? 'Tổng quan';
$sectionLabels = ['overview' => 'tổng quan', 'apps' => 'ứng dụng', 'pages' => 'Page/SEO', 'bank' => 'ngân hàng', 'coc' => 'shop', 'country' => 'quốc gia hỗ trợ', 'paypal' => 'PayPal', 'ai_support' => 'AI Support'];
$sectionTitles = ['overview' => 'Tổng quan', 'apps' => 'App Carrot Home', 'pages' => 'Page Carrot Home', 'bank' => 'Bank', 'coc' => 'Acc Clash of Clans', 'country' => 'Country', 'paypal' => 'Paypal Config', 'ai_support' => 'AI - Support'];
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
$useSelect2 = $section === 'pages' || ($section === 'country' && $countryTab === 'labels');
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
    <?php if ($useSelect2): ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php if ($useSelect2): ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body{background:#eef2f7;color:#172033}
        .brand-mark{width:100%;height:96px;object-fit:contain}
        .muted-text{color:#64748b}
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
        .traffic-view{display:none}
        .traffic-view.active{display:block}
        .traffic-toggle{border:1px solid rgba(15,23,42,.12);border-radius:8px;padding:.25rem;background:#f8fafc}
        .traffic-toggle .btn{border-radius:6px;font-weight:800}
        .traffic-chart-wrap{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:#f8fafc;padding:1rem}
        .traffic-chart-title{font-size:1rem;font-weight:850;color:#172033}
        .traffic-chart-legend{display:flex;align-items:center;gap:1rem;color:#475569;font-size:.82rem;font-weight:800}
        .traffic-chart-legend span{display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap}
        .traffic-dot{display:inline-block;width:10px;height:10px;border-radius:999px}
        .traffic-dot-today{background:#0f766e}
        .traffic-dot-yesterday{background:#f59e0b}
        #traffic_compare_chart{display:block;width:100%;height:260px}
        .glass-panel,.admin-shell{border:1px solid rgba(15,23,42,.08)!important;border-radius:8px!important;background:rgba(255,255,255,.96)!important;box-shadow:0 14px 36px rgba(15,23,42,.06)!important}
        .table{--bs-table-bg:transparent}
        .table thead th{color:#64748b;font-size:.78rem;text-transform:uppercase}
        @media (max-width:1199px){.dashboard-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media (max-width:991px){.dashboard-sidebar{position:static;min-height:auto}.dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:575px){.dashboard-grid{grid-template-columns:1fr}.dashboard-card-value{font-size:1.12rem}.traffic-chart-legend{width:100%;justify-content:space-between}#traffic_compare_chart{height:220px}}
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
                <a class="list-group-item list-group-item-action <?= $section === 'paypal' ? 'active' : '' ?>" href="index.php?section=paypal"><i data-lucide="credit-card"></i><span>Paypal</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'ai_support' ? 'active' : '' ?>" href="index.php?section=ai_support"><i data-lucide="sparkles"></i><span>AI - Support</span></a>
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

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php require __DIR__ . '/section/' . $section . '.php'; ?>
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

document.querySelectorAll('.js-traffic-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const view = button.dataset.trafficView || 'site';
        document.querySelectorAll('.js-traffic-toggle').forEach((item) => {
            const isActive = item === button;
            item.classList.toggle('active', isActive);
            item.classList.toggle('btn-dark', isActive);
            item.classList.toggle('btn-light', !isActive);
        });
        document.querySelectorAll('[data-traffic-panel]').forEach((panel) => {
            panel.classList.toggle('active', panel.dataset.trafficPanel === view);
        });
    });
});

const trafficChartCanvas = document.getElementById('traffic_compare_chart');
const trafficChartDataEl = document.getElementById('traffic_compare_data');
if (trafficChartCanvas && trafficChartDataEl) {
    const trafficChartData = JSON.parse(trafficChartDataEl.textContent || '{}');
    const drawTrafficChart = () => {
        const canvas = trafficChartCanvas;
        const context = canvas.getContext('2d');
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        const width = Math.max(320, Math.floor(rect.width));
        const height = Math.max(200, Math.floor(rect.height || 260));
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.clearRect(0, 0, width, height);

        const labels = Array.isArray(trafficChartData.labels) ? trafficChartData.labels : [];
        const today = Array.isArray(trafficChartData.today) ? trafficChartData.today.map(Number) : [];
        const yesterday = Array.isArray(trafficChartData.yesterday) ? trafficChartData.yesterday.map(Number) : [];
        const values = today.concat(yesterday);
        const maxValue = Math.max(1, ...values);
        const yMax = Math.ceil(maxValue / 5) * 5 || 5;
        const padding = {top: 18, right: 18, bottom: 34, left: 46};
        const chartWidth = width - padding.left - padding.right;
        const chartHeight = height - padding.top - padding.bottom;
        const pointX = (index) => padding.left + (chartWidth * index / Math.max(1, labels.length - 1));
        const pointY = (value) => padding.top + chartHeight - (chartHeight * value / yMax);

        context.font = '12px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        context.lineWidth = 1;
        context.strokeStyle = 'rgba(100, 116, 139, .18)';
        context.fillStyle = '#64748b';
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        for (let step = 0; step <= 4; step++) {
            const value = Math.round(yMax * step / 4);
            const y = pointY(value);
            context.beginPath();
            context.moveTo(padding.left, y);
            context.lineTo(width - padding.right, y);
            context.stroke();
            context.fillText(value.toLocaleString('vi-VN'), padding.left - 10, y);
        }

        context.textAlign = 'center';
        context.textBaseline = 'top';
        labels.forEach((label, index) => {
            if (index % 3 !== 0 && index !== labels.length - 1) {
                return;
            }
            context.fillText(String(label).slice(0, 2), pointX(index), height - padding.bottom + 12);
        });

        const drawLine = (data, color) => {
            context.beginPath();
            data.forEach((value, index) => {
                const x = pointX(index);
                const y = pointY(value);
                if (index === 0) {
                    context.moveTo(x, y);
                } else {
                    context.lineTo(x, y);
                }
            });
            context.lineWidth = 3;
            context.lineJoin = 'round';
            context.lineCap = 'round';
            context.strokeStyle = color;
            context.stroke();

            data.forEach((value, index) => {
                const x = pointX(index);
                const y = pointY(value);
                context.beginPath();
                context.arc(x, y, 3, 0, Math.PI * 2);
                context.fillStyle = '#fff';
                context.fill();
                context.lineWidth = 2;
                context.strokeStyle = color;
                context.stroke();
            });
        };

        drawLine(yesterday, '#f59e0b');
        drawLine(today, '#0f766e');
    };

    drawTrafficChart();
    window.addEventListener('resize', drawTrafficChart);
}

const labelLanguages = <?= json_encode($languageOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const labelTranslations = <?= json_encode($labelTranslationMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

document.querySelectorAll('.js-label-translations').forEach((button) => {
    button.addEventListener('click', async () => {
        const labelKey = button.dataset.labelKey || '';
        const currentTranslations = labelTranslations[labelKey] || {};
        const englishValue = currentTranslations.en || '';
        const rows = labelLanguages.map((language) => {
            const langKey = language.lang_key || '';
            const countryName = language.countries || language.name || language.lang_country || '';
            const translateUrl = englishValue && langKey !== 'en'
                ? `https://translate.google.com/?${new URLSearchParams({
                    hl: 'vi',
                    pl: 'translate',
                    sl: 'en',
                    tl: langKey,
                    text: englishValue,
                    op: 'translate',
                }).toString()}`
                : '';
            return `
                <tr>
                    <td class="text-start font-monospace small">${escapeHtml(langKey)}</td>
                    <td class="text-start">${escapeHtml(countryName)}</td>
                    <td>
                        <textarea class="form-control form-control-sm js-translation-input" data-lang-key="${escapeHtml(langKey)}" rows="2">${escapeHtml(currentTranslations[langKey] || '')}</textarea>
                    </td>
                    <td class="text-end">
                        ${translateUrl ? `<a class="btn btn-sm btn-secondary" href="${escapeHtml(translateUrl)}" target="_blank" rel="noopener noreferrer" title="Dịch từ en" aria-label="Dịch từ en">
                            <i data-lucide="languages" style="width:16px;height:16px"></i>
                        </a>` : ''}
                    </td>
                </tr>
            `;
        }).join('');

        const result = await Swal.fire({
            title: `Dịch nhanh: ${labelKey}`,
            html: `
                <div class="table-responsive" style="max-height:60vh">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th class="text-start">Lang</th>
                                <th class="text-start">Country dùng chung</th>
                                <th class="text-start">Value</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="4" class="text-center text-muted py-4">Chưa có lang.</td></tr>'}</tbody>
                    </table>
                </div>
            `,
            width: 'min(920px, 96vw)',
            showCancelButton: true,
            confirmButtonText: 'Lưu tất cả',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            didOpen: () => {
                if (window.lucide) {
                    lucide.createIcons();
                }
            },
            preConfirm: async () => {
                const formData = new FormData();
                formData.append('action', 'save_text_label_batch');
                formData.append('ajax', '1');
                formData.append('key', labelKey);

                document.querySelectorAll('.swal2-container .js-translation-input').forEach((input) => {
                    formData.append(`translations[${input.dataset.langKey}]`, input.value);
                });

                try {
                    const response = await fetch('index.php?section=country&tab=labels', {
                        method: 'POST',
                        body: formData,
                    });
                    const payload = await response.json();
                    if (!response.ok || payload.status !== 'success') {
                        throw new Error(payload.message || 'Cập nhật thất bại.');
                    }
                    return payload;
                } catch (error) {
                    Swal.showValidationMessage(error.message);
                    return false;
                }
            },
        });

        if (result.isConfirmed) {
            await Swal.fire({
                icon: 'success',
                title: 'Đã cập nhật',
                timer: 900,
                showConfirmButton: false,
            });
            window.location.reload();
        }

    });
});

if (window.jQuery && jQuery.fn.select2) {
    const normalizeSlug = (value) => jQuery.trim(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    jQuery('.js-page-slug-select').select2({
        theme: 'bootstrap-5',
        tags: true,
        width: '100%',
        placeholder: 'Chọn hoặc nhập slug mới',
        allowClear: true,
        createTag: (params) => {
            const slug = normalizeSlug(params.term);
            if (!slug) {
                return null;
            }

            return {
                id: slug,
                text: slug,
                newTag: true,
            };
        },
    }).on('select2:select change', function () {
        const slug = normalizeSlug(this.value);
        if (slug && slug !== this.value) {
            const option = new Option(slug, slug, true, true);
            jQuery(this).append(option).trigger('change.select2');
        }
    });

    jQuery('.js-page-lang-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn lang',
    });

    jQuery('.js-label-key-select').select2({
        theme: 'bootstrap-5',
        tags: true,
        width: '100%',
        placeholder: 'Chọn hoặc nhập key mới',
        allowClear: true,
        createTag: (params) => {
            const term = jQuery.trim(params.term || '');
            if (!term) {
                return null;
            }

            return {
                id: term,
                text: term,
                newTag: true,
            };
        },
    });

    jQuery('.js-label-lang-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn lang',
    });

    jQuery('.js-app-content-lang-select').select2({
        theme: 'bootstrap-5',
        tags: true,
        width: '100%',
        placeholder: 'Chọn hoặc nhập lang',
        createTag: (params) => {
            const term = jQuery.trim(params.term || '');
            if (!term) {
                return null;
            }

            return {
                id: term,
                text: term,
                newTag: true,
            };
        },
    });
}

const bindSimpleEditor = (editorId, sourceId, targetName = '') => {
    const editor = document.getElementById(editorId);
    const source = document.getElementById(sourceId);
    if (!editor || !source) {
        return;
    }

    document.querySelectorAll('[data-editor-command]').forEach((button) => {
        if (targetName && button.dataset.editorTarget && button.dataset.editorTarget !== targetName) {
            return;
        }
        if (targetName && !button.dataset.editorTarget && targetName !== 'page_content') {
            return;
        }

        button.addEventListener('click', () => {
            const command = button.dataset.editorCommand;
            let value = button.dataset.editorValue || null;

            if (command === 'createLink') {
                value = window.prompt('URL');
                if (!value) return;
            }

            editor.focus();
            document.execCommand(command, false, value);
            source.value = editor.innerHTML.trim();
        });
    });

    editor.addEventListener('input', () => {
        source.value = editor.innerHTML.trim();
    });

    const form = editor.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            source.value = editor.innerHTML.trim();
        });
    }
};

bindSimpleEditor('page_content_editor', 'page_content_html', 'page_content');
bindSimpleEditor('app_content_editor', 'app_content_html', 'app_content');

[
    ['app_photo_app_id', 'photos'],
].forEach(([selectId, tab]) => {
    const select = document.getElementById(selectId);
    if (!select) {
        return;
    }
    select.addEventListener('change', () => {
        const appId = select.value.trim();
        if (!appId) {
            return;
        }
        window.location.href = `index.php?section=apps&tab=${tab}&app_id=${encodeURIComponent(appId)}`;
    });
});

const appContentAppInput = document.getElementById('app_content_app_id');
const appContentLangInput = document.getElementById('app_content_lang_key');
const aiAppContentButton = document.querySelector('.js-ai-app-content-generate');
if (appContentAppInput && appContentLangInput) {
    appContentAppInput.addEventListener('change', () => {
        const appId = appContentAppInput.value.trim();
        if (!appId) {
            return;
        }
        window.location.href = `index.php?section=apps&tab=content&app_id=${encodeURIComponent(appId)}`;
    });
}
if (appContentAppInput && appContentLangInput && aiAppContentButton) {
    let appContentLookupTimer = null;
    let hasEnglishAppContentSource = false;

    const checkEnglishAppContentSource = () => {
        window.clearTimeout(appContentLookupTimer);
        appContentLookupTimer = window.setTimeout(async () => {
            const appId = appContentAppInput.value.trim();
            const langKey = appContentLangInput.value.trim();
            hasEnglishAppContentSource = false;
            aiAppContentButton.classList.add('d-none');
            if (!appId || !langKey || langKey === 'en') {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'ajax_find_app_content_source');
            formData.append('app_id', appId);
            formData.append('lang_key', langKey);

            try {
                const response = await fetch('index.php?section=apps&tab=content', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                hasEnglishAppContentSource = Boolean(payload.source_en);
                if (response.ok && payload.status === 'success' && hasEnglishAppContentSource) {
                    aiAppContentButton.classList.remove('d-none');
                }
            } catch (error) {
            }
        }, 250);
    };

    appContentAppInput.addEventListener('change', checkEnglishAppContentSource);
    appContentLangInput.addEventListener('change', checkEnglishAppContentSource);

    aiAppContentButton.addEventListener('click', async () => {
        const appId = appContentAppInput.value.trim();
        const langKey = appContentLangInput.value.trim();
        if (!appId || !langKey || langKey === 'en' || !hasEnglishAppContentSource) {
            return;
        }

        aiAppContentButton.disabled = true;
        const originalHtml = aiAppContentButton.innerHTML;
        aiAppContentButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang tạo</span>';

        const formData = new FormData();
        formData.append('action', 'ajax_ai_translate_app_content');
        formData.append('app_id', appId);
        formData.append('lang_key', langKey);

        try {
            const response = await fetch('index.php?section=apps&tab=content', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Không tạo được mô tả app bằng AI.');
            }

            const editor = document.getElementById('app_content_editor');
            const source = document.getElementById('app_content_html');
            if (editor && source) {
                editor.innerHTML = payload.content_html || '';
                source.value = editor.innerHTML.trim();
            }
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
        } finally {
            aiAppContentButton.disabled = false;
            aiAppContentButton.innerHTML = originalHtml;
            if (window.lucide) {
                lucide.createIcons();
            }
        }
    });

    checkEnglishAppContentSource();
}

const pageSlugInput = document.getElementById('page_slug');
const pageLangInput = document.getElementById('page_lang');
const pageIdInput = pageSlugInput ? pageSlugInput.closest('form')?.querySelector('input[name="id"]') : null;
const aiPageButton = document.querySelector('.js-ai-page-generate');
if (pageSlugInput && pageLangInput && pageIdInput) {
    let pageLookupTimer = null;
    let lastPageLookup = '';
    let hasEnglishPageSource = false;

    const checkExistingPage = () => {
        window.clearTimeout(pageLookupTimer);
        pageLookupTimer = window.setTimeout(async () => {
            const slug = pageSlugInput.value.trim();
            const lang = pageLangInput.value.trim();
            const currentId = Number(pageIdInput.value || 0);
            const lookupKey = `${slug}|${lang}|${currentId}`;

            hasEnglishPageSource = false;
            if (aiPageButton) {
                aiPageButton.classList.add('d-none');
            }
            if (!slug || !lang || lookupKey === lastPageLookup) {
                return;
            }
            lastPageLookup = lookupKey;

            const formData = new FormData();
            formData.append('action', 'ajax_find_page');
            formData.append('slug', slug);
            formData.append('lang', lang);

            try {
                const response = await fetch('index.php?section=pages', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                hasEnglishPageSource = Boolean(payload.source_en);
                if (aiPageButton && hasEnglishPageSource && lang !== 'en') {
                    aiPageButton.classList.remove('d-none');
                }
                const page = payload.page || null;
                if (!response.ok || payload.status !== 'success' || !page || Number(page.id) === currentId) {
                    return;
                }

                const result = await Swal.fire({
                    icon: 'info',
                    title: 'Page đã tồn tại',
                    text: `Slug "${slug}" với lang "${lang}" đã có dữ liệu. Mở page này để chỉnh sửa?`,
                    showCancelButton: true,
                    confirmButtonText: 'Mở để edit',
                    cancelButtonText: 'Ở lại',
                });

                if (result.isConfirmed) {
                    window.location.href = `index.php?section=pages&edit=${encodeURIComponent(page.id)}`;
                }
            } catch (error) {
            }
        }, 350);
    };

    pageSlugInput.addEventListener('input', checkExistingPage);
    pageSlugInput.addEventListener('change', checkExistingPage);
    pageSlugInput.addEventListener('blur', checkExistingPage);
    pageLangInput.addEventListener('change', checkExistingPage);

    if (aiPageButton) {
        aiPageButton.addEventListener('click', async () => {
            const slug = pageSlugInput.value.trim();
            const lang = pageLangInput.value.trim();
            if (!slug || !lang || lang === 'en' || !hasEnglishPageSource) {
                return;
            }

            aiPageButton.disabled = true;
            const originalHtml = aiPageButton.innerHTML;
            aiPageButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang tạo</span>';

            const formData = new FormData();
            formData.append('action', 'ajax_ai_translate_page');
            formData.append('slug', slug);
            formData.append('lang', lang);

            try {
                const response = await fetch('index.php?section=pages', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 'success') {
                    throw new Error(payload.message || 'Không tạo được nội dung AI.');
                }

                const editor = document.getElementById('page_content_editor');
                const source = document.getElementById('page_content_html');
                if (editor && source) {
                    editor.innerHTML = payload.content_html || '';
                    source.value = editor.innerHTML.trim();
                }
                [
                    ['seo_title', payload.seo_title],
                    ['seo_description', payload.seo_description],
                    ['seo_keywords', payload.seo_keywords],
                ].forEach(([fieldId, value]) => {
                    const field = document.getElementById(fieldId);
                    if (field && typeof value === 'string' && value !== '') {
                        field.value = value;
                    }
                });
            } catch (error) {
                await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
            } finally {
                aiPageButton.disabled = false;
                aiPageButton.innerHTML = originalHtml;
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        });
    }

    checkExistingPage();
}

const labelKeyInput = document.getElementById('label_key');
const labelLangInput = document.getElementById('label_lang_key');
const aiLabelButton = document.querySelector('.js-ai-label-generate');
if (labelKeyInput && labelLangInput && aiLabelButton) {
    let labelLookupTimer = null;
    let hasEnglishLabelSource = false;

    const checkEnglishLabelSource = () => {
        window.clearTimeout(labelLookupTimer);
        labelLookupTimer = window.setTimeout(async () => {
            const labelKey = labelKeyInput.value.trim();
            const langKey = labelLangInput.value.trim();
            hasEnglishLabelSource = false;
            aiLabelButton.classList.add('d-none');
            if (!labelKey || !langKey || langKey === 'en') {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'ajax_find_text_label_source');
            formData.append('key', labelKey);
            formData.append('lang_key', langKey);

            try {
                const response = await fetch('index.php?section=country&tab=labels', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                hasEnglishLabelSource = Boolean(payload.source_en);
                if (response.ok && payload.status === 'success' && hasEnglishLabelSource) {
                    aiLabelButton.classList.remove('d-none');
                }
            } catch (error) {
            }
        }, 250);
    };

    labelKeyInput.addEventListener('input', checkEnglishLabelSource);
    labelKeyInput.addEventListener('change', checkEnglishLabelSource);
    labelLangInput.addEventListener('change', checkEnglishLabelSource);

    aiLabelButton.addEventListener('click', async () => {
        const labelKey = labelKeyInput.value.trim();
        const langKey = labelLangInput.value.trim();
        if (!labelKey || !langKey || langKey === 'en' || !hasEnglishLabelSource) {
            return;
        }

        aiLabelButton.disabled = true;
        const originalHtml = aiLabelButton.innerHTML;
        aiLabelButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang tạo</span>';

        const formData = new FormData();
        formData.append('action', 'ajax_ai_translate_label');
        formData.append('key', labelKey);
        formData.append('lang_key', langKey);

        try {
            const response = await fetch('index.php?section=country&tab=labels', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Không tạo được nhãn AI.');
            }

            const valueInput = document.getElementById('label_value');
            if (valueInput) {
                valueInput.value = payload.value || '';
            }
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
        } finally {
            aiLabelButton.disabled = false;
            aiLabelButton.innerHTML = originalHtml;
            if (window.lucide) {
                lucide.createIcons();
            }
        }
    });

    checkEnglishLabelSource();
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
