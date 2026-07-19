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
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'login') {
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
$allowedSections = ['overview', 'apps', 'ebook', 'music', 'pages', 'users', 'api', 'bank', 'sites', 'coc', 'country', 'paypal', 'ai_support', 'cloud', 'backup'];
$section = in_array($_GET['section'] ?? 'overview', $allowedSections, true) ? ($_GET['section'] ?? 'overview') : 'overview';
$editKey = trim($_GET['edit'] ?? '');
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$cocTab = ($_GET['tab'] ?? 'accounts') === 'orders' ? 'orders' : 'accounts';
$appTab = in_array($_GET['tab'] ?? 'main', ['main', 'photos', 'content', 'categories', 'stores', 'orders'], true) ? ($_GET['tab'] ?? 'main') : 'main';
$ebookTab = in_array($_GET['tab'] ?? 'books', ['books', 'categories', 'stores', 'orders'], true) ? ($_GET['tab'] ?? 'books') : 'books';
$musicTab = in_array($_GET['tab'] ?? 'songs', ['songs', 'artists', 'genres', 'orders', 'search_log'], true) ? ($_GET['tab'] ?? 'songs') : 'songs';
$countryTab = ($_GET['tab'] ?? 'countries') === 'labels' ? 'labels' : 'countries';
$paypalTab = in_array($_GET['tab'] ?? 'home', ['home', 'ebook', 'coc', 'music', 'cloud'], true) ? ($_GET['tab'] ?? 'home') : 'home';
$cloudTab = in_array($_GET['tab'] ?? 'plans', ['plans', 'langs', 'subscriptions'], true) ? ($_GET['tab'] ?? 'plans') : 'plans';
$editing = null;
$editingLabel = null;
$accounts = [];
$apps = [];
$songs = [];
$songArtistOptions = [];
$songArtists = [];
$songGenres = [];
$songOrders = [];
$songSearchLogs = [];
$songSearchLogStats = ['total_rows' => 0, 'unique_queries' => 0, 'unique_ips' => 0];
$pages = [];
$users = [];
$apiConfigs = [];
$apiSiteOptions = [];
$banks = [];
$sites = [];
$cloudPlans = [];
$cloudLangs = [];
$cloudSubscriptions = [];
$countries = [];
$languageOptions = [];
$labelTranslationMap = [];
$labelKeyOptions = [];
$pageSlugOptions = [];
$textLabels = [];
$orders = [];
$appOrders = [];
$ebooks = [];
$ebookCategories = [];
$ebookStoreLinks = [];
$ebookOrders = [];
$ebookCategoryOptions = [];
$dashboardMetrics = [
    'apps' => 0,
    'pages' => 0,
    'users' => 0,
    'coc' => 0,
    'songs' => 0,
    'ebook' => 0,
    'bank' => 0,
    'sites' => 0,
    'cloud' => 0,
    'country' => 0,
];
$trafficMetrics = [
    'coc' => [],
    'home' => [],
    'ebook' => [],
    'music' => [],
    'total' => [
        'today_unique' => 0,
        'today_hits' => 0,
        'week_unique' => 0,
        'week_hits' => 0,
        'range_unique' => 0,
        'range_hits' => 0,
        'total_unique' => 0,
        'total_hits' => 0,
    ],
];
$trafficIpRows = [];
$trafficChartData = admin_empty_visit_chart([
    'from' => date('Y-m-d'),
    'to' => date('Y-m-d'),
    'label' => date('Y-m-d'),
]);
$trafficDateRange = [
    'preset' => 'today',
    'from' => date('Y-m-d'),
    'to' => date('Y-m-d'),
    'label' => date('Y-m-d'),
];
$backupItems = [];
$accountSort = 'id';
$accountDir = 'DESC';
$orderSort = 'created_at';
$orderDir = 'DESC';
$appSort = 'priority';
$appDir = 'DESC';
$ebookSort = 'updated_at';
$ebookDir = 'DESC';
$pageSort = 'updated_at';
$pageDir = 'DESC';
$userSort = 'created_at';
$userDir = 'DESC';
$apiSort = 'updated_at';
$apiDir = 'DESC';
$bankSort = 'id';
$bankDir = 'DESC';
$cloudSort = 'sort_order';
$cloudDir = 'ASC';
$countrySort = 'id';
$countryDir = 'DESC';
$textLabelSort = 'key';
$textLabelDir = 'ASC';
$textLabelSearch = trim($_GET['label_q'] ?? '');
$textLabelPage = max(1, (int) ($_GET['label_page'] ?? 1));
$textLabelPerPage = 25;
$textLabelTotal = 0;
$textLabelTotalPages = 1;
$songPage = max(1, (int) ($_GET['song_page'] ?? 1));
$songPerPage = 25;
$songTotal = 0;
$songTotalPages = 1;
$songSearch = trim($_GET['song_q'] ?? '');
$songLangFilter = trim($_GET['song_lang'] ?? '');
$songSearchLogQuery = trim($_GET['search_log_q'] ?? '');
$songSearchLogPage = max(1, (int) ($_GET['search_log_page'] ?? 1));
$songSearchLogPerPage = 50;
$songSearchLogTotal = 0;
$songSearchLogTotalPages = 1;
$artistSearch = trim($_GET['artist_q'] ?? '');
$artistLangFilter = trim($_GET['artist_lang'] ?? '');
$serverRuntime = null;

if (($_GET['duplicate'] ?? '') === 'page') {
    $message = 'Page với slug và lang này đã tồn tại. Đã mở dữ liệu hiện có để chỉnh sửa.';
}

function admin_nas_upload_endpoint(): string
{
    return 'https://nas.carrot28.com/index.php?api=upload_image';
}

function admin_nas_delete_endpoint(): string
{
    return 'https://nas.carrot28.com/index.php?api=delete_media';
}

function admin_allowed_media_types(): array
{
    return ['carrot_app', 'carrot_app_photo', 'carrot_ebook_cover', 'carrot_ebook_file', 'coc_images', 'bank', 'sites', 'country', 'song_avatar', 'song_mp3', 'artist_avatar', 'genre_avatar'];
}

function admin_upload_image_to_nas(array $file, string $typeMedia): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file lỗi, mã: ' . (int) $file['error']);
    }

    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('File upload không hợp lệ.');
    }

    if (!function_exists('curl_init') || !function_exists('curl_file_create')) {
        throw new RuntimeException('Server cần bật PHP cURL để tải file lên CarrotNas.');
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
        if (!in_array($typeMedia, admin_allowed_media_types(), true)) {
            throw new RuntimeException('Type media không hợp lệ.');
        }

        $file = $_FILES['file'] ?? [];
        $mimeType = is_file($file['tmp_name'] ?? '') ? (mime_content_type($file['tmp_name']) ?: '') : '';
        if (in_array($typeMedia, ['song_avatar', 'artist_avatar', 'genre_avatar'], true) && strpos($mimeType, 'image/') !== 0) {
            throw new RuntimeException('Vui lòng chọn tệp ảnh.');
        }
        if ($typeMedia === 'song_mp3' && !in_array($mimeType, ['audio/mpeg', 'audio/mp3'], true) && !preg_match('/\.mp3$/i', (string) ($file['name'] ?? ''))) {
            throw new RuntimeException('Vui lòng chọn tệp MP3.');
        }
        if ($typeMedia === 'carrot_ebook_cover' && strpos($mimeType, 'image/') !== 0) {
            throw new RuntimeException('Vui lòng chọn tệp ảnh bìa.');
        }
        if ($typeMedia === 'carrot_ebook_file') {
            $fileName = (string) ($file['name'] ?? '');
            $allowedEbookMimes = ['application/epub+zip', 'application/pdf', 'text/plain', 'text/markdown', 'text/html'];
            if (!in_array($mimeType, $allowedEbookMimes, true) && !preg_match('/\.(epub|pdf|txt|md|html)$/i', $fileName)) {
                throw new RuntimeException('Vui lòng chọn tệp EPUB, PDF hoặc file văn bản.');
            }
        }

        $url = admin_upload_image_to_nas($file, $typeMedia);
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

function admin_delete_file_from_nas(string $imageUrl, string $typeMedia = '', string $filename = ''): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Server cần bật PHP cURL để xóa file trên CarrotNas.');
    }

    $postFields = [];
    if ($imageUrl !== '') {
        $postFields['image_url'] = $imageUrl;
    }
    if ($typeMedia !== '') {
        $postFields['type_media'] = $typeMedia;
    }
    if ($filename !== '') {
        $postFields['filename'] = $filename;
    }

    if (!$postFields) {
        throw new RuntimeException('Thiếu URL hoặc filename để xóa.');
    }

    $curl = curl_init(admin_nas_delete_endpoint());
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($response === false) {
        throw new RuntimeException('Không gọi được API CarrotNas: ' . $curlError);
    }

    $payload = json_decode($response, true);
    if ($statusCode >= 400 || !is_array($payload) || ($payload['status'] ?? '') !== 'success') {
        $apiMessage = is_array($payload) ? ($payload['message'] ?? 'unknown error') : trim($response);
        throw new RuntimeException('CarrotNas xóa file thất bại: ' . $apiMessage);
    }

    return $payload;
}

function admin_ajax_delete_file(): void
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $typeMedia = trim($_POST['type_media'] ?? '');
        if ($typeMedia !== '' && !in_array($typeMedia, admin_allowed_media_types(), true)) {
            throw new RuntimeException('Type media không hợp lệ.');
        }

        $imageUrl = trim($_POST['image_url'] ?? '');
        $filename = trim($_POST['filename'] ?? '');
        $payload = admin_delete_file_from_nas($imageUrl, $typeMedia, $filename);

        echo json_encode(['status' => 'success', 'file' => $payload], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

function admin_fetch_ebook(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM ebook WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_ebook_category(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM ebook_categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_ebook_store_link(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM ebook_store_links WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_app_category_ids(PDO $pdo, string $appId): array
{
    try {
        $stmt = $pdo->prepare('SELECT category_id FROM category_app WHERE app_id = ? ORDER BY category_id ASC');
        $stmt->execute([$appId]);
        return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $e) {
        return [];
    }
}

function admin_sync_app_categories(PDO $pdo, string $appId, array $categoryIds): void
{
    $categoryIds = array_values(array_unique(array_filter(array_map(static function ($value): string {
        return trim((string) $value);
    }, $categoryIds))));

    $pdo->prepare('DELETE FROM category_app WHERE app_id = ?')->execute([$appId]);
    if (!$categoryIds) {
        return;
    }

    $insert = $pdo->prepare('INSERT IGNORE INTO category_app (app_id, category_id) VALUES (?, ?)');
    foreach ($categoryIds as $categoryId) {
        $insert->execute([$appId, $categoryId]);
    }
}

function admin_fetch_app_category(PDO $pdo, string $categoryId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM app_category WHERE category_id = ?');
    $stmt->execute([$categoryId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_song(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM song WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_song_artist(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM song_artist WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_song_artist_ids(PDO $pdo, string $songId): array
{
    try {
        $stmt = $pdo->prepare('SELECT artist_id FROM song_artist_map WHERE song_id = ? ORDER BY artist_id ASC');
        $stmt->execute([$songId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

function admin_sync_song_artists(PDO $pdo, string $songId, array $artistIds): void
{
    $artistIds = array_values(array_unique(array_filter(array_map('intval', $artistIds))));
    $pdo->prepare('DELETE FROM song_artist_map WHERE song_id = ?')->execute([$songId]);
    if (!$artistIds) {
        return;
    }

    $insert = $pdo->prepare('INSERT IGNORE INTO song_artist_map (song_id, artist_id) VALUES (?, ?)');
    foreach ($artistIds as $artistId) {
        $insert->execute([$songId, $artistId]);
    }
}

function admin_fetch_bank(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bank WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_sites(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM sites WHERE id = ?');
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

function admin_fetch_cloud_plan(PDO $pdo, int $id): ?array
{
    admin_ensure_cloud_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM cloud WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_cloud_lang(PDO $pdo, int $id): ?array
{
    admin_ensure_cloud_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM cloud_lang WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_cloud_langs(PDO $pdo, int $cloudId): array
{
    admin_ensure_cloud_tables($pdo);
    $stmt = $pdo->prepare('SELECT * FROM cloud_lang WHERE cloud_id = ? ORDER BY lang_key ASC');
    $stmt->execute([$cloudId]);
    return $stmt->fetchAll();
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

function admin_fetch_youtube_api_key(PDO $apiPdo, ?PDO $aiPdo = null, ?int $siteId = null): string
{
    admin_ensure_api_table($apiPdo);
    if ($siteId !== null && $siteId > 0) {
        $stmt = $apiPdo->prepare('
            SELECT api_key, config_json
            FROM api_config
            WHERE provider = "youtube"
              AND enabled = 1
              AND (site_id = ? OR site_id IS NULL OR site_id = 0)
              AND (
                  TRIM(COALESCE(api_key, "")) <> ""
                  OR TRIM(COALESCE(config_json, "")) <> ""
              )
            ORDER BY CASE WHEN site_id = ? THEN 0 ELSE 1 END, id DESC
            LIMIT 1
        ');
        $stmt->execute([$siteId, $siteId]);
    } else {
        $stmt = $apiPdo->prepare('
            SELECT api_key, config_json
            FROM api_config
            WHERE provider = "youtube"
              AND enabled = 1
              AND (site_id IS NULL OR site_id = 0)
              AND (
                  TRIM(COALESCE(api_key, "")) <> ""
                  OR TRIM(COALESCE(config_json, "")) <> ""
              )
            ORDER BY id DESC
            LIMIT 1
        ');
        $stmt->execute();
    }
    $config = $stmt->fetch();
    if ($config) {
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        if ($apiKey !== '') {
            return $apiKey;
        }

        $json = json_decode((string) ($config['config_json'] ?? ''), true);
        if (is_array($json)) {
            foreach (['api_key', 'key', 'youtube_api_key', 'youtubeKey'] as $keyName) {
                $apiKey = trim((string) ($json[$keyName] ?? ''));
                if ($apiKey !== '') {
                    return $apiKey;
                }
            }
        }
    }

    $hasAiSupportKey = false;
    if ($aiPdo instanceof PDO) {
        admin_ensure_ai_support_table($aiPdo);
        $stmt = $aiPdo->prepare('SELECT 1 FROM ai_support WHERE enabled = 1 AND TRIM(COALESCE(api_key, "")) <> "" LIMIT 1');
        $stmt->execute();
        $hasAiSupportKey = (bool) $stmt->fetchColumn();
    }

    if ($hasAiSupportKey) {
        throw new RuntimeException('Bạn đã có key ở AI Support, nhưng nút này cần YouTube Data API key riêng trong mục API. Vào API, chọn Provider = YouTube API, bật Enabled và dán key vào ô API key / anon key.');
    }

    throw new RuntimeException('Chưa cấu hình YouTube Data API key. Vào mục API, chọn Provider = YouTube API, bật Enabled và dán key vào ô API key / anon key.');
}

function admin_extract_youtube_video_id(string $url): string
{
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($url === '') {
        return '';
    }

    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
        return $url;
    }

    $parts = parse_url($url);
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        if (!empty($query['v']) && preg_match('/^[A-Za-z0-9_-]{6,}$/', (string) $query['v'])) {
            return (string) $query['v'];
        }
    }

    $path = trim((string) ($parts['path'] ?? ''), '/');
    if ($path !== '') {
        $segments = array_values(array_filter(explode('/', $path), static fn(string $segment): bool => $segment !== ''));
        if (!empty($segments)) {
            $host = strtolower((string) ($parts['host'] ?? ''));
            if (strpos($host, 'youtu.be') !== false && preg_match('/^[A-Za-z0-9_-]{6,}$/', $segments[0])) {
                return $segments[0];
            }
            foreach (['shorts', 'embed', 'live'] as $prefix) {
                $index = array_search($prefix, $segments, true);
                if ($index !== false && !empty($segments[$index + 1]) && preg_match('/^[A-Za-z0-9_-]{6,}$/', $segments[$index + 1])) {
                    return $segments[$index + 1];
                }
            }
        }
    }

    if (preg_match('/(?:v=|youtu\.be\/|shorts\/|embed\/|live\/)([A-Za-z0-9_-]{6,})/', $url, $matches)) {
        return $matches[1];
    }

    return '';
}

function admin_find_song_by_youtube_video_id(PDO $pdo, string $videoId): ?array
{
    if ($videoId === '') {
        return null;
    }

    $stmt = $pdo->query('
        SELECT id, name, artist, link_ytb
        FROM song
        WHERE TRIM(COALESCE(link_ytb, "")) <> ""
        ORDER BY id ASC
    ');
    while ($song = $stmt->fetch()) {
        if (admin_extract_youtube_video_id((string) ($song['link_ytb'] ?? '')) === $videoId) {
            return $song;
        }
    }

    return null;
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

function admin_substr(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
}

function admin_gemini_complete(PDO $pdo, string $prompt, float $temperature = 0.7): string
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
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => max(0, min(2, $temperature)),
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
                return $text;
            }

            $errors[] = $accountName . ' / ' . $model . ': empty response';
        }
    }

    throw new RuntimeException('Gemini lỗi sau khi thử tất cả tài khoản: ' . implode(' | ', $errors));
}

function admin_gemini_generate_page(PDO $pdo, string $idea, string $slug, string $lang, string $currentTitle = '', string $currentContentHtml = ''): array
{
    $prompt = <<<PROMPT
You are a senior website content writer and SEO editor for a CMS.
Write a complete page from the admin's request and return only valid JSON.

Target language: {$lang}
Page slug: {$slug}
Current title: {$currentTitle}
Current HTML content, if any:
{$currentContentHtml}

Admin request:
{$idea}

Return exactly this JSON shape:
{
  "title": "natural page title",
  "content_html": "<h2>...</h2><p>...</p>",
  "seo_title": "SEO title, max 60 characters if possible",
  "seo_description": "SEO description, max 160 characters if possible",
  "seo_keywords": "comma separated keywords"
}

Rules:
- content_html must be clean HTML only, without html/body tags, script tags, markdown fences, or inline event handlers.
- Use useful headings, short paragraphs, and lists where helpful.
- Make title, SEO title, description, and keywords consistent with the content and target language.
- Do not include explanations outside the JSON.
PROMPT;

    $text = admin_gemini_complete($pdo, $prompt, 0.7);
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text);
    $data = json_decode($text, true);
    if (!is_array($data)) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
        }
    }

    if (!is_array($data)) {
        throw new RuntimeException('AI không trả về JSON hợp lệ.');
    }

    $contentHtml = trim((string) ($data['content_html'] ?? ''));
    $contentHtml = preg_replace('/<\/?(?:html|body)[^>]*>/i', '', $contentHtml) ?? $contentHtml;
    $contentHtml = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $contentHtml) ?? $contentHtml;
    $contentHtml = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $contentHtml) ?? $contentHtml;

    $result = [
        'title' => trim((string) ($data['title'] ?? '')),
        'content_html' => trim($contentHtml),
        'seo_title' => admin_substr(trim((string) ($data['seo_title'] ?? '')), 255),
        'seo_description' => admin_substr(trim((string) ($data['seo_description'] ?? '')), 320),
        'seo_keywords' => admin_substr(trim((string) ($data['seo_keywords'] ?? '')), 500),
    ];

    if ($result['title'] === '' || $result['content_html'] === '') {
        throw new RuntimeException('AI chưa tạo đủ title và HTML content.');
    }

    return $result;
}

function admin_gemini_generate_song_artist_description(PDO $pdo, string $idea, string $artistName, string $lang, string $currentDescription = ''): array
{
    $prompt = <<<PROMPT
You are a senior music profile writer for a CMS.
Write or improve the artist biography from the admin's request and return only valid JSON.

Target language: {$lang}
Artist name: {$artistName}
Current description, if any:
{$currentDescription}

Admin request:
{$idea}

Return exactly this JSON shape:
{
  "description": "<h2>...</h2><p>...</p>"
}

Rules:
- description must be clean HTML only, without html/body tags, script tags, markdown fences, or inline event handlers.
- Keep the tone concise, engaging, and suitable for an artist profile page.
- Do not include explanations outside the JSON.
PROMPT;

    $text = admin_gemini_complete($pdo, $prompt, 0.7);
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text);
    $data = json_decode($text, true);
    if (!is_array($data)) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
        }
    }

    if (!is_array($data)) {
        throw new RuntimeException('AI không trả về JSON hợp lệ.');
    }

    $descriptionHtml = trim((string) ($data['description'] ?? ''));
    $descriptionHtml = preg_replace('/<\/?(?:html|body)[^>]*>/i', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $descriptionHtml) ?? $descriptionHtml;

    $result = [
        'description' => trim($descriptionHtml),
    ];

    if ($result['description'] === '') {
        throw new RuntimeException('AI chưa tạo đủ description.');
    }

    return $result;
}

function admin_gemini_generate_song_genre_description(PDO $pdo, string $idea, string $genreId, string $title, string $lang, string $currentDescription = ''): array
{
    $prompt = <<<PROMPT
You are a senior music taxonomy editor for a CMS.
Write or improve the music genre description from the admin's request and return only valid JSON.

Target language: {$lang}
Genre ID: {$genreId}
Genre title: {$title}
Current description, if any:
{$currentDescription}

Admin request:
{$idea}

Return exactly this JSON shape:
{
  "description": "<h2>...</h2><p>...</p>"
}

Rules:
- description must be clean HTML only, without html/body tags, script tags, markdown fences, or inline event handlers.
- Keep the tone concise, engaging, and suitable for a music genre page.
- Mention listening mood, style, or discovery value when relevant.
- Do not include explanations outside the JSON.
PROMPT;

    $text = admin_gemini_complete($pdo, $prompt, 0.7);
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text);
    $data = json_decode($text, true);
    if (!is_array($data)) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
        }
    }

    if (!is_array($data)) {
        throw new RuntimeException('AI không trả về JSON hợp lệ.');
    }

    $descriptionHtml = trim((string) ($data['description'] ?? ''));
    $descriptionHtml = preg_replace('/<\/?(?:html|body)[^>]*>/i', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $descriptionHtml) ?? $descriptionHtml;
    $descriptionHtml = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $descriptionHtml) ?? $descriptionHtml;

    $result = [
        'description' => trim($descriptionHtml),
    ];

    if ($result['description'] === '') {
        throw new RuntimeException('AI chưa tạo đủ description.');
    }

    return $result;
}

function admin_gemini_generate_category_content(PDO $pdo, string $idea, string $categoryId, string $lang, string $currentTitle = '', string $currentDescription = ''): array
{
    $prompt = <<<PROMPT
You are a senior app store taxonomy editor.
Write or improve category copy from the admin's request and return only valid JSON.

Target language: {$lang}
Category ID: {$categoryId}
Current title: {$currentTitle}
Current description:
{$currentDescription}

Admin request:
{$idea}

Return exactly this JSON shape:
{
  "title": "short natural category title",
  "description": "clear category description, 1-3 concise sentences"
}

Rules:
- Keep title suitable for a navigation/category card.
- Description should be useful for an app/game category page.
- Do not include markdown, HTML, code fences, or explanations outside the JSON.
PROMPT;

    $text = admin_gemini_complete($pdo, $prompt, 0.7);
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text);
    $data = json_decode($text, true);
    if (!is_array($data)) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
        }
    }

    if (!is_array($data)) {
        throw new RuntimeException('AI không trả về JSON hợp lệ.');
    }

    $result = [
        'title' => admin_substr(trim((string) ($data['title'] ?? '')), 255),
        'description' => trim((string) ($data['description'] ?? '')),
    ];

    if ($result['title'] === '' || $result['description'] === '') {
        throw new RuntimeException('AI chưa tạo đủ title và description.');
    }

    return $result;
}

function admin_gemini_generate_site_description(PDO $pdo, string $idea, string $name, string $url, string $currentDescription = ''): array
{
    $prompt = <<<PROMPT
You are a senior website copywriter for a CMS.
Write or improve a short site description from the admin's request and return only valid JSON.

Site name: {$name}
Site URL: {$url}
Current description:
{$currentDescription}

Admin request:
{$idea}

Return exactly this JSON shape:
{
  "description": "clear site description, 1-3 concise sentences"
}

Rules:
- Keep the description useful for a footer/list of related Carrot sites.
- Use the same language as the admin request unless the request says otherwise.
- Do not include markdown, HTML, code fences, URLs, or explanations outside the JSON.
PROMPT;

    $text = admin_gemini_complete($pdo, $prompt, 0.7);
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text);
    $data = json_decode($text, true);
    if (!is_array($data)) {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
        }
    }

    if (!is_array($data)) {
        throw new RuntimeException('AI không trả về JSON hợp lệ.');
    }

    $description = trim(strip_tags((string) ($data['description'] ?? '')));
    if ($description === '') {
        throw new RuntimeException('AI chưa tạo đủ description.');
    }

    return ['description' => $description];
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

function admin_carrothome_cache_dir(): string
{
    $homeRoot = realpath(__DIR__ . '/../CarrotHome');
    if ($homeRoot === false) {
        $homeRoot = __DIR__ . '/../CarrotHome';
    }

    return $homeRoot . '/storage/cache';
}

function admin_clear_carrothome_cache(): int
{
    $dir = admin_carrothome_cache_dir();
    if (!is_dir($dir)) {
        return 0;
    }

    $deleted = 0;
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        if (is_file($file) && @unlink($file)) {
            $deleted++;
        }
    }

    return $deleted;
}

function admin_carrotmusic_cache_dir(): string
{
    $musicRoot = realpath(__DIR__ . '/../CarrotMusic');
    if ($musicRoot === false) {
        $musicRoot = __DIR__ . '/../CarrotMusic';
    }

    return $musicRoot . '/storage/cache';
}

function admin_clear_carrotmusic_cache(): int
{
    $dir = admin_carrotmusic_cache_dir();
    if (!is_dir($dir)) {
        return 0;
    }

    $deleted = 0;
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        if (is_file($file) && @unlink($file)) {
            $deleted++;
        }
    }

    return $deleted;
}

function admin_cache_dir(): string
{
    return __DIR__ . '/storage/cache';
}

function admin_cache_path(string $key): string
{
    $key = preg_replace('/[^a-z0-9_.-]+/i', '-', $key);
    return admin_cache_dir() . '/' . $key . '.json';
}

function admin_cache_get(string $key, int $ttlSeconds): ?array
{
    if ($ttlSeconds <= 0) {
        return null;
    }

    $path = admin_cache_path($key);
    if (!is_file($path) || (time() - (int) filemtime($path)) > $ttlSeconds) {
        return null;
    }

    $payload = json_decode((string) @file_get_contents($path), true);
    return is_array($payload) ? $payload : null;
}

function admin_cache_set(string $key, array $payload): void
{
    $dir = admin_cache_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return;
    }

    $path = admin_cache_path($key);
    $tmp = $path . '.' . getmypid() . '.tmp';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }

    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
        @rename($tmp, $path);
    }
}

function admin_clear_internal_cache(string $prefix = ''): int
{
    $dir = admin_cache_dir();
    if (!is_dir($dir)) {
        return 0;
    }

    $prefix = preg_replace('/[^a-z0-9_.-]+/i', '-', $prefix);
    $pattern = $dir . '/' . ($prefix !== '' ? $prefix . '*' : '*') . '.json';
    $deleted = 0;
    foreach (glob($pattern) ?: [] as $file) {
        if (is_file($file) && @unlink($file)) {
            $deleted++;
        }
    }

    return $deleted;
}

function admin_fetch_page(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM page WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_api_config(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM api_config WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_fetch_api_site_options(PDO $pdo): array
{
    try {
        admin_ensure_sites_table($pdo);
        return $pdo->query('SELECT id, name, url FROM sites ORDER BY sort_order ASC, name ASC, id ASC')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function admin_site_id_by_key(PDO $pdo, string $siteKey, array $aliases = []): ?int
{
    try {
        admin_ensure_sites_table($pdo);
        $names = array_values(array_unique(array_filter(array_map(static fn($value): string => strtolower(trim((string) $value)), array_merge([$siteKey], $aliases)))));
        if ($names) {
            $stmt = $pdo->prepare('SELECT id FROM sites WHERE LOWER(name) IN (' . implode(',', array_fill(0, count($names), '?')) . ') ORDER BY sort_order ASC, id ASC LIMIT 1');
            $stmt->execute($names);
            $siteId = (int) $stmt->fetchColumn();
            if ($siteId > 0) {
                return $siteId;
            }
        }

        $hostMap = [
            'CarrotHome' => 'home.carrot28.com',
            'CarrotMusic' => 'music.carrot28.com',
            'CarrotCoc' => 'coc.carrot28.com',
        ];
        $host = $hostMap[$siteKey] ?? '';
        if ($host !== '') {
            $stmt = $pdo->prepare('SELECT id FROM sites WHERE LOWER(url) LIKE ? ORDER BY sort_order ASC, id ASC LIMIT 1');
            $stmt->execute(['%' . strtolower($host) . '%']);
            $siteId = (int) $stmt->fetchColumn();
            if ($siteId > 0) {
                return $siteId;
            }
        }
    } catch (Throwable $e) {
        return null;
    }

    return null;
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

function admin_cached_count_table(?PDO $pdo, string $cacheKey, string $table, int $ttlSeconds = 86400): int
{
    if (!$pdo instanceof PDO) {
        return 0;
    }

    $cached = admin_cache_get('overview_count_' . $cacheKey, $ttlSeconds);
    if (is_array($cached) && array_key_exists('value', $cached)) {
        return (int) $cached['value'];
    }

    $count = admin_safe_count_table($pdo, $table);
    admin_cache_set('overview_count_' . $cacheKey, [
        'value' => $count,
        'table' => $table,
        'created_at' => date('c'),
    ]);

    return $count;
}

function admin_empty_visit_metrics(): array
{
    return [
        'today_unique' => 0,
        'today_hits' => 0,
        'week_unique' => 0,
        'week_hits' => 0,
        'range_unique' => 0,
        'range_hits' => 0,
        'total_unique' => 0,
        'total_hits' => 0,
    ];
}

function admin_parse_traffic_date_range(): array
{
    $today = date('Y-m-d');
    $preset = trim($_GET['traffic_range'] ?? 'today');
    $allowedPresets = ['today', 'yesterday', '7days', '1month', '1year', 'custom'];
    if (!in_array($preset, $allowedPresets, true)) {
        $preset = 'today';
    }

    $from = $today;
    $to = $today;
    if ($preset === 'yesterday') {
        $from = $to = date('Y-m-d', strtotime('-1 day'));
    } elseif ($preset === '7days') {
        $from = date('Y-m-d', strtotime('-6 days'));
    } elseif ($preset === '1month') {
        $from = date('Y-m-d', strtotime('-1 month +1 day'));
    } elseif ($preset === '1year') {
        $from = date('Y-m-d', strtotime('-1 year +1 day'));
    } elseif ($preset === 'custom') {
        $from = trim($_GET['traffic_from'] ?? $today);
        $to = trim($_GET['traffic_to'] ?? $today);
    }

    $fromDate = DateTime::createFromFormat('!Y-m-d', $from);
    $toDate = DateTime::createFromFormat('!Y-m-d', $to);
    if (!$fromDate || $fromDate->format('Y-m-d') !== $from) {
        $from = $today;
        $fromDate = DateTime::createFromFormat('!Y-m-d', $from);
    }
    if (!$toDate || $toDate->format('Y-m-d') !== $to) {
        $to = $today;
        $toDate = DateTime::createFromFormat('!Y-m-d', $to);
    }
    if ($fromDate > $toDate) {
        [$from, $to] = [$to, $from];
    }

    return [
        'preset' => $preset,
        'from' => $from,
        'to' => $to,
        'label' => $from === $to ? $from : $from . ' - ' . $to,
    ];
}

function admin_visit_metrics(?PDO $pdo, string $site, array $dateRange): array
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
              SUM(CASE WHEN visit_date BETWEEN :range_from_unique AND :range_to_unique THEN 1 ELSE 0 END) AS range_unique,
              SUM(CASE WHEN visit_date BETWEEN :range_from_hits AND :range_to_hits THEN hits ELSE 0 END) AS range_hits,
              COUNT(*) AS total_unique,
              COALESCE(SUM(hits), 0) AS total_hits
            FROM visit_daily_ip
            WHERE site = :site
        ");
        $stmt->execute([
            ':site' => $site,
            ':range_from_unique' => $dateRange['from'],
            ':range_to_unique' => $dateRange['to'],
            ':range_from_hits' => $dateRange['from'],
            ':range_to_hits' => $dateRange['to'],
        ]);
        $row = $stmt->fetch() ?: [];
    } catch (Throwable $e) {
        return admin_empty_visit_metrics();
    }

    return [
        'today_unique' => (int) ($row['today_unique'] ?? 0),
        'today_hits' => (int) ($row['today_hits'] ?? 0),
        'week_unique' => (int) ($row['week_unique'] ?? 0),
        'week_hits' => (int) ($row['week_hits'] ?? 0),
        'range_unique' => (int) ($row['range_unique'] ?? 0),
        'range_hits' => (int) ($row['range_hits'] ?? 0),
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

function admin_visit_chart_mode(array $dateRange): string
{
    return ($dateRange['from'] ?? '') === ($dateRange['to'] ?? '') ? 'hourly' : 'daily';
}

function admin_empty_visit_chart(array $dateRange): array
{
    $mode = admin_visit_chart_mode($dateRange);
    if ($mode === 'hourly') {
        return [
            'mode' => 'hourly',
            'label' => $dateRange['label'] ?? '',
            'labels' => array_map(static fn(int $hour): string => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23)),
            'hits' => array_fill(0, 24, 0),
            'unique' => array_fill(0, 24, 0),
        ];
    }

    $labels = [];
    $fromDate = DateTime::createFromFormat('!Y-m-d', (string) ($dateRange['from'] ?? date('Y-m-d')));
    $toDate = DateTime::createFromFormat('!Y-m-d', (string) ($dateRange['to'] ?? date('Y-m-d')));
    if (!$fromDate || !$toDate) {
        $fromDate = DateTime::createFromFormat('!Y-m-d', date('Y-m-d'));
        $toDate = clone $fromDate;
    }

    while ($fromDate <= $toDate) {
        $labels[] = $fromDate->format('Y-m-d');
        $fromDate->modify('+1 day');
    }

    return [
        'mode' => 'daily',
        'label' => $dateRange['label'] ?? '',
        'labels' => $labels,
        'hits' => array_fill(0, count($labels), 0),
        'unique' => array_fill(0, count($labels), 0),
    ];
}

function admin_visit_chart(?PDO $pdo, string $site, array $dateRange): array
{
    $series = admin_empty_visit_chart($dateRange);
    if (!$pdo instanceof PDO) {
        return $series;
    }

    try {
        if (($series['mode'] ?? '') === 'hourly') {
            $stmt = $pdo->prepare("
                SELECT
                  HOUR(last_seen_at) AS chart_key,
                  COALESCE(SUM(hits), 0) AS hits,
                  COUNT(*) AS unique_count
                FROM visit_daily_ip
                WHERE site = :site
                  AND visit_date = :range_from
                GROUP BY chart_key
                ORDER BY chart_key
            ");
            $stmt->execute([
                ':site' => $site,
                ':range_from' => $dateRange['from'],
            ]);
        } else {
            $stmt = $pdo->prepare("
                SELECT
                  visit_date AS chart_key,
                  COALESCE(SUM(hits), 0) AS hits,
                  COUNT(*) AS unique_count
                FROM visit_daily_ip
                WHERE site = :site
                  AND visit_date BETWEEN :range_from AND :range_to
                GROUP BY visit_date
                ORDER BY visit_date
            ");
            $stmt->execute([
                ':site' => $site,
                ':range_from' => $dateRange['from'],
                ':range_to' => $dateRange['to'],
            ]);
        }
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return $series;
    }

    $labelIndex = array_flip($series['labels']);
    foreach ($rows as $row) {
        $key = (string) ($row['chart_key'] ?? '');
        if (($series['mode'] ?? '') === 'hourly') {
            $key = str_pad((string) max(0, min(23, (int) $key)), 2, '0', STR_PAD_LEFT) . ':00';
        }
        if (!array_key_exists($key, $labelIndex)) {
            continue;
        }
        $index = (int) $labelIndex[$key];
        $series['hits'][$index] += (int) ($row['hits'] ?? 0);
        $series['unique'][$index] += (int) ($row['unique_count'] ?? 0);
    }

    return $series;
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

function admin_sum_visit_chart(array $items, array $dateRange): array
{
    $total = admin_empty_visit_chart($dateRange);
    foreach ($items as $item) {
        foreach ($total['labels'] as $index => $label) {
            $total['hits'][$index] += (int) ($item['hits'][$index] ?? 0);
            $total['unique'][$index] += (int) ($item['unique'][$index] ?? 0);
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

function admin_visit_ip_rows(?PDO $pdo, string $site, string $label, array $dateRange): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
              range_rows.ip_text,
              COALESCE(today_rows.today_hits, 0) AS today_hits,
              COALESCE(week_rows.week_hits, 0) AS week_hits,
              range_rows.range_hits,
              COALESCE(total_rows.total_hits, 0) AS total_hits,
              range_rows.visit_days,
              range_rows.last_seen_at,
              range_rows.request_path,
              range_rows.user_agent
            FROM (
              SELECT
                ip_text,
                COALESCE(SUM(hits), 0) AS range_hits,
                COUNT(*) AS visit_days,
                MAX(last_seen_at) AS last_seen_at,
                SUBSTRING_INDEX(GROUP_CONCAT(request_path ORDER BY last_seen_at DESC SEPARATOR '\\n'), '\\n', 1) AS request_path,
                SUBSTRING_INDEX(GROUP_CONCAT(user_agent ORDER BY last_seen_at DESC SEPARATOR '\\n'), '\\n', 1) AS user_agent
              FROM visit_daily_ip
              WHERE site = :range_site
                AND visit_date BETWEEN :range_from AND :range_to
              GROUP BY ip_text
            ) AS range_rows
            LEFT JOIN (
              SELECT ip_text, COALESCE(SUM(hits), 0) AS today_hits
              FROM visit_daily_ip
              WHERE site = :today_site
                AND visit_date = CURRENT_DATE
              GROUP BY ip_text
            ) AS today_rows ON today_rows.ip_text = range_rows.ip_text
            LEFT JOIN (
              SELECT ip_text, COALESCE(SUM(hits), 0) AS week_hits
              FROM visit_daily_ip
              WHERE site = :week_site
                AND visit_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
              GROUP BY ip_text
            ) AS week_rows ON week_rows.ip_text = range_rows.ip_text
            LEFT JOIN (
              SELECT ip_text, COALESCE(SUM(hits), 0) AS total_hits
              FROM visit_daily_ip
              WHERE site = :total_site
              GROUP BY ip_text
            ) AS total_rows ON total_rows.ip_text = range_rows.ip_text
            ORDER BY range_rows.range_hits DESC, total_rows.total_hits DESC, range_rows.last_seen_at DESC
            LIMIT 100
        ");
        $stmt->execute([
            ':range_site' => $site,
            ':range_from' => $dateRange['from'],
            ':range_to' => $dateRange['to'],
            ':today_site' => $site,
            ':week_site' => $site,
            ':total_site' => $site,
        ]);
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

function admin_backup_dir(): string
{
    return __DIR__ . '/storage/backups';
}

function admin_ensure_backup_dir(): string
{
    $dir = admin_backup_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        throw new RuntimeException('Thư mục storage/backups không ghi được.');
    }
    return $dir;
}

function admin_backup_safe_file(string $file): string
{
    $base = basename($file);
    if (!preg_match('/^carrot_home_[0-9]{8}_[0-9]{6}(?:_[a-f0-9]{6})?\.sql$/', $base)) {
        throw new RuntimeException('File backup không hợp lệ.');
    }
    $path = admin_backup_dir() . '/' . $base;
    if (!is_file($path)) {
        throw new RuntimeException('Không tìm thấy file backup.');
    }
    return $path;
}

function admin_backup_state_file_path(array $state): string
{
    $file = (string) ($state['file'] ?? '');
    if ($file === '' && !empty($state['file_path'])) {
        $file = basename((string) $state['file_path']);
    }
    return admin_backup_safe_file($file);
}

function admin_backup_state_path(string $jobId): string
{
    if (!preg_match('/^[a-z0-9_]+$/', $jobId)) {
        throw new RuntimeException('Job backup không hợp lệ.');
    }
    return admin_backup_dir() . '/' . $jobId . '.json';
}

function admin_sql_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function admin_sql_value($value): string
{
    if ($value === null) {
        return 'NULL';
    }
    return "'" . strtr((string) $value, [
        "\\" => "\\\\",
        "\0" => "\\0",
        "\n" => "\\n",
        "\r" => "\\r",
        "\t" => "\\t",
        "\x1a" => "\\Z",
        "'" => "\\'",
    ]) . "'";
}

function admin_backup_packet_limit(PDO $pdo): int
{
    try {
        $packet = (int) $pdo->query('SELECT @@max_allowed_packet')->fetchColumn();
        if ($packet > 0) {
            return max(1024 * 64, (int) floor($packet * 0.75));
        }
    } catch (Throwable $e) {
    }
    return 1024 * 1024;
}

function admin_backup_set_large_packet(PDO $pdo): void
{
    try {
        $pdo->exec('SET SESSION max_allowed_packet=67108864');
    } catch (Throwable $e) {
    }
}

function admin_backup_error_message(Throwable $e): string
{
    $message = $e->getMessage();
    if (stripos($message, 'max_allowed_packet') !== false || stripos($message, 'Got a packet bigger') !== false) {
        return 'Gói dữ liệu backup/restore lớn hơn max_allowed_packet của MySQL. Hệ thống đã tự chia nhỏ INSERT theo giới hạn hiện tại; nếu vẫn lỗi, có một bản ghi đơn lẻ quá lớn và cần tăng max_allowed_packet trên MySQL lên 64M hoặc 128M rồi khởi động lại MySQL.';
    }
    return $message;
}

function admin_backup_write(string $path, string $content): void
{
    if (@file_put_contents($path, $content, FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException('Không ghi được file backup.');
    }
}

function admin_backup_write_insert_rows(string $filePath, string $table, array $columns, array $rows, int $packetLimit): void
{
    if (!$rows) {
        return;
    }

    $columnSql = implode(', ', array_map('admin_sql_ident', $columns));
    $prefix = 'INSERT INTO ' . admin_sql_ident($table) . ' (' . $columnSql . ") VALUES\n";
    $statement = $prefix;
    $statementRows = 0;

    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $column) {
            $values[] = admin_sql_value($row[$column] ?? null);
        }
        $line = '(' . implode(', ', $values) . ')';
        $candidateLength = strlen($statement) + ($statementRows > 0 ? 2 : 0) + strlen($line) + 2;

        if ($statementRows > 0 && $candidateLength > $packetLimit) {
            admin_backup_write($filePath, $statement . ";\n");
            $statement = $prefix;
            $statementRows = 0;
        }

        if ($statementRows > 0) {
            $statement .= ",\n";
        }
        $statement .= $line;
        $statementRows++;
    }

    if ($statementRows > 0) {
        admin_backup_write($filePath, $statement . ";\n");
    }
}

function admin_backup_tables(PDO $pdo): array
{
    $tables = [];
    $rows = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        if (!empty($row[0])) {
            $tables[] = (string) $row[0];
        }
    }
    return $tables;
}

function admin_backup_list_items(): array
{
    $dir = admin_ensure_backup_dir();
    $items = [];
    foreach (glob($dir . '/carrot_home_*.sql') ?: [] as $path) {
        $base = basename($path);
        $metaPath = $path . '.meta.json';
        $meta = is_file($metaPath) ? json_decode((string) file_get_contents($metaPath), true) : [];
        $status = is_array($meta) ? ($meta['status'] ?? 'incomplete') : 'incomplete';
        $items[] = [
            'file' => $base,
            'size' => filesize($path) ?: 0,
            'size_label' => admin_format_bytes(filesize($path) ?: 0),
            'created_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
            'status' => $status,
            'complete' => $status === 'complete',
            'tables' => is_array($meta) ? (int) ($meta['tables'] ?? 0) : 0,
            'rows' => is_array($meta) ? (int) ($meta['rows'] ?? 0) : 0,
        ];
    }
    usort($items, static fn(array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at']));
    return $items;
}

function admin_backup_cleanup_incomplete(int $olderThanSeconds = 60): int
{
    $dir = admin_ensure_backup_dir();
    $now = time();
    $deleted = 0;
    foreach (glob($dir . '/carrot_home_*.sql') ?: [] as $path) {
        $metaPath = $path . '.meta.json';
        $meta = is_file($metaPath) ? json_decode((string) file_get_contents($metaPath), true) : [];
        if (is_array($meta) && ($meta['status'] ?? '') === 'complete') {
            continue;
        }
        if ($olderThanSeconds > 0 && ($now - (filemtime($path) ?: $now)) < $olderThanSeconds) {
            continue;
        }
        $base = basename($path, '.sql');
        @unlink($path);
        @unlink($metaPath);
        foreach (glob($dir . '/backup_' . substr($base, strlen('carrot_home_')) . '*.json') ?: [] as $statePath) {
            @unlink($statePath);
        }
        $deleted++;
    }
    return $deleted;
}

function admin_backup_delete_file(string $file): void
{
    $path = admin_backup_safe_file($file);
    $base = basename($path, '.sql');
    @unlink($path);
    @unlink($path . '.meta.json');
    foreach (glob(admin_backup_dir() . '/backup_' . substr($base, strlen('carrot_home_')) . '*.json') ?: [] as $statePath) {
        @unlink($statePath);
    }
}

function admin_format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $value = max(0, $bytes);
    $unitIndex = 0;
    while ($value >= 1024 && $unitIndex < count($units) - 1) {
        $value /= 1024;
        $unitIndex++;
    }
    return ($unitIndex === 0 ? (string) $value : number_format($value, 2)) . ' ' . $units[$unitIndex];
}

function admin_backup_progress(array $state): array
{
    $totalRows = max(1, (int) ($state['total_rows'] ?? 0));
    $doneRows = max(0, (int) ($state['done_rows'] ?? 0));
    return [
        'job_id' => $state['job_id'] ?? '',
        'file' => (string) ($state['file'] ?? basename((string) ($state['file_path'] ?? ''))),
        'job_status' => $state['status'] ?? 'running',
        'table' => $state['current_table'] ?? '',
        'done_rows' => $doneRows,
        'total_rows' => (int) ($state['total_rows'] ?? 0),
        'percent' => min(100, (int) floor(($doneRows / $totalRows) * 100)),
    ];
}

function admin_backup_start(PDO $pdo, string $dbName): array
{
    admin_backup_set_large_packet($pdo);
    $dir = admin_ensure_backup_dir();
    admin_backup_cleanup_incomplete(60);
    $stamp = date('Ymd_His');
    $jobId = 'backup_' . $stamp . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
    $suffix = substr((string) strrchr($jobId, '_'), 1);
    $filePath = $dir . '/carrot_home_' . $stamp . '_' . $suffix . '.sql';
    $tables = admin_backup_tables($pdo);
    if (!$tables) {
        throw new RuntimeException('Database không có bảng để sao lưu.');
    }

    $tableRows = [];
    $totalRows = 0;
    foreach ($tables as $table) {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . admin_sql_ident($table))->fetchColumn();
        $tableRows[$table] = $count;
        $totalRows += $count;
    }

    admin_backup_write($filePath, "-- Carrot Admin backup\n-- Database: {$dbName}\n-- Created: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");
    $state = [
        'job_id' => $jobId,
        'status' => 'running',
        'file' => basename($filePath),
        'tables' => $tables,
        'table_rows' => $tableRows,
        'table_index' => 0,
        'offset' => 0,
        'done_rows' => 0,
        'total_rows' => $totalRows,
        'schema_written' => [],
        'current_table' => $tables[0],
        'started_at' => date('Y-m-d H:i:s'),
    ];
    file_put_contents(admin_backup_state_path($jobId), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return admin_backup_progress($state);
}

function admin_backup_process(PDO $pdo, string $jobId, int $chunkSize = 100): array
{
    admin_backup_set_large_packet($pdo);
    $statePath = admin_backup_state_path($jobId);
    $state = is_file($statePath) ? json_decode((string) file_get_contents($statePath), true) : null;
    if (!is_array($state)) {
        throw new RuntimeException('Không tìm thấy trạng thái backup.');
    }
    if (($state['status'] ?? '') === 'complete') {
        return admin_backup_progress($state);
    }

    $tables = $state['tables'] ?? [];
    $tableIndex = (int) ($state['table_index'] ?? 0);
    if (!isset($tables[$tableIndex])) {
        $filePath = admin_backup_state_file_path($state);
        admin_backup_write($filePath, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        $state['status'] = 'complete';
        $state['finished_at'] = date('Y-m-d H:i:s');
        file_put_contents($filePath . '.meta.json', json_encode([
            'status' => 'complete',
            'tables' => count($tables),
            'rows' => (int) ($state['done_rows'] ?? 0),
            'started_at' => $state['started_at'] ?? '',
            'finished_at' => $state['finished_at'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return admin_backup_progress($state);
    }

    $table = (string) $tables[$tableIndex];
    $filePath = admin_backup_state_file_path($state);
    $state['current_table'] = $table;
    if (empty($state['schema_written'][$table])) {
        $createStmt = $pdo->query('SHOW CREATE TABLE ' . admin_sql_ident($table))->fetch(PDO::FETCH_NUM);
        $createSql = (string) ($createStmt[1] ?? '');
        admin_backup_write($filePath, "\nDROP TABLE IF EXISTS " . admin_sql_ident($table) . ";\n" . $createSql . ";\n\n");
        $state['schema_written'][$table] = true;
    }

    $offset = (int) ($state['offset'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM ' . admin_sql_ident($table) . ' LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        $state['table_index'] = $tableIndex + 1;
        $state['offset'] = 0;
        $state['current_table'] = $tables[$tableIndex + 1] ?? '';
    } else {
        $columns = array_keys($rows[0]);
        admin_backup_write_insert_rows($filePath, $table, $columns, $rows, admin_backup_packet_limit($pdo));
        $state['offset'] = $offset + count($rows);
        $state['done_rows'] = (int) ($state['done_rows'] ?? 0) + count($rows);
    }

    file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return admin_backup_progress($state);
}

function admin_restore_start(string $file): array
{
    admin_ensure_backup_dir();
    $filePath = admin_backup_safe_file($file);
    $metaPath = $filePath . '.meta.json';
    $meta = is_file($metaPath) ? json_decode((string) file_get_contents($metaPath), true) : [];
    if (!is_array($meta) || ($meta['status'] ?? '') !== 'complete') {
        throw new RuntimeException('Chỉ có thể khôi phục file backup đã hoàn tất.');
    }
    $jobId = 'restore_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
    $state = [
        'job_id' => $jobId,
        'status' => 'running',
        'file' => basename($filePath),
        'offset' => 0,
        'pending' => '',
        'done_statements' => 0,
        'total_bytes' => filesize($filePath) ?: 1,
        'started_at' => date('Y-m-d H:i:s'),
    ];
    file_put_contents(admin_backup_state_path($jobId), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return admin_restore_progress($state);
}

function admin_restore_progress(array $state): array
{
    $totalBytes = max(1, (int) ($state['total_bytes'] ?? 1));
    $offset = max(0, (int) ($state['offset'] ?? 0));
    return [
        'job_id' => $state['job_id'] ?? '',
        'file' => (string) ($state['file'] ?? basename((string) ($state['file_path'] ?? ''))),
        'job_status' => $state['status'] ?? 'running',
        'done_statements' => (int) ($state['done_statements'] ?? 0),
        'percent' => min(100, (int) floor(($offset / $totalBytes) * 100)),
    ];
}

function admin_restore_process(PDO $pdo, string $jobId): array
{
    admin_backup_set_large_packet($pdo);
    $statePath = admin_backup_state_path($jobId);
    $state = is_file($statePath) ? json_decode((string) file_get_contents($statePath), true) : null;
    if (!is_array($state)) {
        throw new RuntimeException('Không tìm thấy trạng thái khôi phục.');
    }
    if (($state['status'] ?? '') === 'complete') {
        return admin_restore_progress($state);
    }

    $filePath = admin_backup_state_file_path($state);
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        throw new RuntimeException('Không mở được file backup.');
    }
    fseek($handle, (int) ($state['offset'] ?? 0));
    $pending = (string) ($state['pending'] ?? '');
    $statements = 0;
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    while (!feof($handle) && $statements < 80) {
        $line = fgets($handle);
        if ($line === false) {
            break;
        }
        $trimmed = trim($line);
        if ($pending === '' && ($trimmed === '' || substr($trimmed, 0, 2) === '--')) {
            continue;
        }
        $pending .= $line;
        if (preg_match('/;\s*$/', $trimmed)) {
            $sql = trim(preg_replace('/;\s*$/', '', $pending) ?? $pending);
            $pending = '';
            if ($sql !== '') {
                $pdo->exec($sql);
                $state['done_statements'] = (int) ($state['done_statements'] ?? 0) + 1;
                $statements++;
            }
        }
    }

    $state['offset'] = ftell($handle);
    fclose($handle);
    $state['pending'] = $pending;
    if ((int) $state['offset'] >= (int) ($state['total_bytes'] ?? 0) && trim($pending) === '') {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $state['status'] = 'complete';
        $state['finished_at'] = date('Y-m-d H:i:s');
    }

    file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return admin_restore_progress($state);
}

function admin_backup_ajax(PDO $pdo, string $dbName): void
{
    try {
        $task = trim($_POST['backup_task'] ?? '');
        if ($task === 'list') {
            admin_backup_cleanup_incomplete(300);
            admin_json_success(['items' => admin_backup_list_items()]);
        }
        if ($task === 'cleanup') {
            admin_json_success(['deleted' => admin_backup_cleanup_incomplete(0), 'items' => admin_backup_list_items()]);
        }
        if ($task === 'delete') {
            admin_backup_delete_file(trim($_POST['file'] ?? ''));
            admin_json_success(['items' => admin_backup_list_items()]);
        }
        if ($task === 'start_backup') {
            admin_json_success(admin_backup_start($pdo, $dbName));
        }
        if ($task === 'process_backup') {
            admin_json_success(admin_backup_process($pdo, trim($_POST['job_id'] ?? '')));
        }
        if ($task === 'start_restore') {
            admin_json_success(admin_restore_start(trim($_POST['file'] ?? '')));
        }
        if ($task === 'process_restore') {
            admin_json_success(admin_restore_process($pdo, trim($_POST['job_id'] ?? '')));
        }
        throw new RuntimeException('Tác vụ backup không hợp lệ.');
    } catch (Throwable $e) {
        admin_json_error(admin_backup_error_message($e));
    }
}

if (($_POST['action'] ?? '') === 'backup_ajax') {
    if (!$pdo instanceof PDO) {
        admin_json_error('Không thể kết nối database: ' . ($db_error ?? 'unknown error'));
    }
    admin_backup_ajax($pdo, $db_name ?? 'carrot_home');
}

if (($_GET['action'] ?? '') === 'backup_download') {
    try {
        $path = admin_backup_safe_file(trim($_GET['file'] ?? ''));
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    } catch (Throwable $e) {
        http_response_code(404);
        echo htmlspecialchars($e->getMessage());
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajax_upload') {
    admin_ajax_upload();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajax_delete_file') {
    admin_ajax_delete_file();
}

if (!$pdo instanceof PDO && !in_array($section, ['overview', 'pages', 'users', 'api'], true)) {
    $error = 'Không thể kết nối database: ' . ($db_error ?? 'unknown error');
} else {
    try {
        $homePdo = null;
        if (in_array($section, ['pages', 'users', 'api', 'music'], true)) {
            $homePdo = admin_home_pdo();
        }

        if ($section === 'overview' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_system_cache') {
            $deletedAdminCacheFiles = admin_clear_internal_cache();
            $deletedHomeCacheFiles = admin_clear_carrothome_cache();
            $deletedMusicCacheFiles = admin_clear_carrotmusic_cache();
            $message = 'Đã clear cache hệ thống: CarrotAdmin (' . number_format($deletedAdminCacheFiles) . ' file), CarrotHome (' . number_format($deletedHomeCacheFiles) . ' file), CarrotMusic (' . number_format($deletedMusicCacheFiles) . ' file).';
        }

        if ($section === 'overview') {
            $trafficDateRange = admin_parse_traffic_date_range();
            $serverRuntime = admin_server_runtime();
            $dashboardMetrics['apps'] = admin_cached_count_table($pdo, 'main_app', 'app');
            $dashboardMetrics['coc'] = admin_cached_count_table($pdo, 'main_coc', 'coc');
            $dashboardMetrics['songs'] = admin_cached_count_table($pdo, 'main_song', 'song');
            $dashboardMetrics['ebook'] = admin_cached_count_table($pdo, 'main_ebook', 'ebook');
            $dashboardMetrics['bank'] = admin_cached_count_table($pdo, 'main_bank', 'bank');
            $dashboardMetrics['sites'] = admin_cached_count_table($pdo, 'main_sites', 'sites');
            admin_ensure_cloud_tables($pdo);
            $dashboardMetrics['cloud'] = admin_cached_count_table($pdo, 'main_cloud', 'cloud');
            $dashboardMetrics['country'] = admin_cached_count_table($pdo, 'main_country', 'country');
            $overviewHomePdo = null;
            try {
                $overviewHomePdo = admin_home_pdo();
                $dashboardMetrics['pages'] = admin_cached_count_table($overviewHomePdo, 'home_page', 'page');
                $dashboardMetrics['users'] = admin_cached_count_table($overviewHomePdo, 'home_users', 'users');
            } catch (Throwable $e) {
                $dashboardMetrics['pages'] = 0;
                $dashboardMetrics['users'] = 0;
            }
            $trafficMetrics['coc'] = admin_visit_metrics($pdo, 'coc', $trafficDateRange);
            $trafficMetrics['home'] = admin_visit_metrics($overviewHomePdo, 'home', $trafficDateRange);
            $trafficMetrics['ebook'] = admin_visit_metrics($pdo, 'ebook', $trafficDateRange);
            $trafficMetrics['music'] = admin_visit_metrics($pdo, 'music', $trafficDateRange);
            $trafficMetrics['total'] = admin_sum_visit_metrics([$trafficMetrics['coc'], $trafficMetrics['home'], $trafficMetrics['ebook'], $trafficMetrics['music']]);
            $trafficChartData = admin_sum_visit_chart([
                admin_visit_chart($pdo, 'coc', $trafficDateRange),
                admin_visit_chart($overviewHomePdo, 'home', $trafficDateRange),
                admin_visit_chart($pdo, 'ebook', $trafficDateRange),
                admin_visit_chart($pdo, 'music', $trafficDateRange),
            ], $trafficDateRange);
            $trafficIpRows = array_merge(
                admin_visit_ip_rows($pdo, 'coc', 'COC Shop', $trafficDateRange),
                admin_visit_ip_rows($overviewHomePdo, 'home', 'CarrotHome', $trafficDateRange),
                admin_visit_ip_rows($pdo, 'ebook', 'CarrotEbook', $trafficDateRange),
                admin_visit_ip_rows($pdo, 'music', 'CarrotMusic', $trafficDateRange)
            );
            usort($trafficIpRows, static function (array $a, array $b): int {
                $rangeCompare = (int) ($b['range_hits'] ?? 0) <=> (int) ($a['range_hits'] ?? 0);
                if ($rangeCompare !== 0) {
                    return $rangeCompare;
                }
                $hitsCompare = (int) ($b['total_hits'] ?? 0) <=> (int) ($a['total_hits'] ?? 0);
                if ($hitsCompare !== 0) {
                    return $hitsCompare;
                }
                return strcmp((string) ($b['last_seen_at'] ?? ''), (string) ($a['last_seen_at'] ?? ''));
            });
        }

        if ($section === 'backup') {
            $backupItems = admin_backup_list_items();
        }

        if (in_array($section, ['apps', 'music', 'pages', 'country', 'cloud'], true)) {
            $languageOptions = admin_fetch_language_options($pdo instanceof PDO ? $pdo : null);
        }

        if ($section === 'apps') {
            admin_ensure_app_table($pdo);
            admin_ensure_app_store_table($pdo);
            admin_ensure_app_category_tables($pdo);
            admin_ensure_app_order_table($pdo);
        }

        if ($section === 'ebook') {
            admin_ensure_ebook_tables($pdo);
        }

        if ($section === 'music') {
            admin_ensure_music_tables($pdo);
        }

        if ($section === 'users') {
            admin_ensure_user_table($homePdo);
        }

        if ($section === 'api') {
            admin_ensure_api_table($homePdo);
        }

        if ($section === 'paypal') {
            admin_ensure_paypal_config_table($pdo);
        }

        if ($section === 'sites') {
            admin_ensure_sites_table($pdo);
        }

        if ($section === 'cloud') {
            admin_ensure_cloud_tables($pdo);
        }

        if ($section === 'ai_support' || in_array($_POST['action'] ?? '', ['save_ai_support_config', 'ajax_ai_translate_page', 'ajax_ai_translate_label', 'ajax_find_text_label_source', 'ajax_find_app_content_source', 'ajax_ai_translate_app_content', 'ajax_find_app_category_content_source', 'ajax_ai_translate_app_category_content', 'ajax_ai_request_app_category_content', 'ajax_ai_request_song_artist', 'ajax_ai_request_song_genre', 'ajax_ai_request_site_description'], true)) {
            admin_ensure_ai_support_table($pdo);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($section === 'coc' && $action === 'delete') {
                $stmt = $pdo->prepare('DELETE FROM coc WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa acc.';
            }

            if ($section === 'coc' && $action === 'delete_failed_coc_orders') {
                $stmt = $pdo->prepare("DELETE FROM coc_orders WHERE COALESCE(status, '') <> 'COMPLETED'");
                $stmt->execute();
                $message = 'Đã xóa ' . number_format($stmt->rowCount()) . ' đơn COC thanh toán không thành công.';
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
                admin_clear_carrothome_cache();
                $message = 'Đã xóa app.';
            }

            if ($section === 'apps' && $action === 'delete_app_order') {
                $stmt = $pdo->prepare('DELETE FROM app_orders WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa đơn đặt hàng app.';
            }

            if ($section === 'apps' && $action === 'delete_failed_app_orders') {
                $stmt = $pdo->prepare("DELETE FROM app_orders WHERE COALESCE(status, '') <> 'COMPLETED'");
                $stmt->execute();
                $message = 'Đã xóa ' . number_format($stmt->rowCount()) . ' đơn app thanh toán không thành công.';
            }

            if ($section === 'apps' && $action === 'delete_app_photo') {
                $stmt = $pdo->prepare('DELETE FROM app_photo WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                admin_clear_carrothome_cache();
                $message = 'Đã xóa ảnh mô tả.';
            }

            if ($section === 'apps' && $action === 'delete_app_store') {
                $stmt = $pdo->prepare('DELETE FROM app_store WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                admin_clear_carrothome_cache();
                $message = 'Đã xóa cổng phân phối.';
            }

            if ($section === 'apps' && $action === 'delete_app_content') {
                $stmt = $pdo->prepare('DELETE FROM app_content WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                admin_clear_carrothome_cache();
                $message = 'Đã xóa nội dung mô tả.';
            }

            if ($section === 'apps' && $action === 'delete_app_category') {
                $stmt = $pdo->prepare('DELETE FROM app_category WHERE category_id = ?');
                $stmt->execute([trim($_POST['category_id'] ?? '')]);
                admin_clear_carrothome_cache();
                $message = 'Đã xóa chuyên mục.';
            }

            if ($section === 'apps' && $action === 'delete_app_category_content') {
                $stmt = $pdo->prepare('DELETE FROM app_category_content WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                admin_clear_carrothome_cache();
                $message = 'Đã xóa nội dung chuyên mục.';
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

                $categoryIds = $_POST['category_ids'] ?? [];
                if (!is_array($categoryIds)) {
                    $categoryIds = [$categoryIds];
                }
                $values['category'] = implode(',', array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string) $value), $categoryIds)))));

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('UPDATE app SET id = ?, decription = ?, github = ?, microsoft_store = ?, icon = ?, itch = ?, exe_file = ?, ipa_file = ?, deb_file = ?, amazon_app_store = ?, huawei_store = ?, youtube_link = ?, google_play = ?, dmg_file = ?, uptodown = ?, simmer = ?, type = ?, apk_file = ?, status = ?, priority = ?, price = ?, category = ? WHERE id = ?');
                    $stmt->execute([$id, $decription, $values['github'], $values['microsoft_store'], $values['icon'], $values['itch'], $values['exe_file'], $values['ipa_file'], $values['deb_file'], $values['amazon_app_store'], $values['huawei_store'], $values['youtube_link'], $values['google_play'], $values['dmg_file'], $values['uptodown'], $values['simmer'], $type, $values['apk_file'], $status, $priority, $price, $values['category'], $originalId]);
                    admin_sync_app_categories($pdo, $id, $categoryIds);
                    admin_clear_carrothome_cache();
                    $message = 'Đã cập nhật app.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app (id, decription, github, microsoft_store, icon, itch, exe_file, ipa_file, deb_file, amazon_app_store, huawei_store, youtube_link, google_play, dmg_file, uptodown, simmer, type, apk_file, status, priority, price, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$id, $decription, $values['github'], $values['microsoft_store'], $values['icon'], $values['itch'], $values['exe_file'], $values['ipa_file'], $values['deb_file'], $values['amazon_app_store'], $values['huawei_store'], $values['youtube_link'], $values['google_play'], $values['dmg_file'], $values['uptodown'], $values['simmer'], $type, $values['apk_file'], $status, $priority, $price, $values['category']]);
                    admin_sync_app_categories($pdo, $id, $categoryIds);
                    admin_clear_carrothome_cache();
                    $message = 'Đã thêm app mới.';
                }
            }

            if ($section === 'ebook' && $action === 'delete_ebook') {
                $stmt = $pdo->prepare('DELETE FROM ebook WHERE id = ?');
                $stmt->execute([trim($_POST['id'] ?? '')]);
                $message = 'Đã xóa ebook.';
            }

            if ($section === 'ebook' && $action === 'delete_ebook_category') {
                $stmt = $pdo->prepare('DELETE FROM ebook_categories WHERE id = ?');
                $stmt->execute([trim($_POST['id'] ?? '')]);
                $message = 'Đã xóa chuyên mục ebook.';
            }

            if ($section === 'ebook' && $action === 'delete_ebook_store_link') {
                $stmt = $pdo->prepare('DELETE FROM ebook_store_links WHERE id = ?');
                $stmt->execute([trim($_POST['id'] ?? '')]);
                $message = 'Đã xóa liên kết cửa hàng.';
            }

            if ($section === 'ebook' && $action === 'delete_ebook_order') {
                $stmt = $pdo->prepare('DELETE FROM ebook_orders WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa đơn đặt hàng ebook.';
            }

            if ($section === 'ebook' && $action === 'delete_failed_ebook_orders') {
                $stmt = $pdo->prepare("DELETE FROM ebook_orders WHERE COALESCE(status, '') <> 'COMPLETED'");
                $stmt->execute();
                $message = 'Đã xóa ' . number_format($stmt->rowCount()) . ' đơn ebook thanh toán không thành công.';
            }

            if ($section === 'ebook' && $action === 'save_ebook') {
                $originalId = trim($_POST['original_id'] ?? '');
                $id = trim($_POST['id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $author = trim($_POST['author'] ?? '');
                $categoryId = trim($_POST['category_id'] ?? '');
                $lang = trim($_POST['lang'] ?? 'en') ?: 'en';
                $price = (float) ($_POST['price'] ?? 0);
                $currency = strtoupper(trim($_POST['currency'] ?? 'USD') ?: 'USD');
                $isFree = !empty($_POST['is_free']) ? 1 : 0;
                $status = trim($_POST['status'] ?? 'draft') ?: 'draft';
                $cover = trim($_POST['cover'] ?? '');
                $previewFile = trim($_POST['preview_file'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $publishedAt = trim($_POST['published_at'] ?? '');
                $now = gmdate('c');

                if ($id === '' || $name === '') {
                    throw new RuntimeException('Vui lòng nhập ID và tên ebook.');
                }

                if ($categoryId === '') {
                    $categoryId = null;
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('
                        UPDATE ebook
                        SET id = ?, name = ?, author = ?, category_id = ?, lang = ?, price = ?, currency = ?, is_free = ?, status = ?, cover = ?, preview_file = ?, description = ?, published_at = ?, updated_at = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$id, $name, $author, $categoryId, $lang, $price, $currency, $isFree, $status, $cover, $previewFile, $description, $publishedAt, $now, $originalId]);
                    $message = 'Đã cập nhật ebook.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO ebook (id, name, author, category_id, lang, price, currency, is_free, status, cover, preview_file, description, published_at, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$id, $name, $author, $categoryId, $lang, $price, $currency, $isFree, $status, $cover, $previewFile, $description, $publishedAt, $now, $now]);
                    $message = 'Đã thêm ebook mới.';
                }
            }

            if ($section === 'ebook' && $action === 'save_ebook_category') {
                $originalId = trim($_POST['original_id'] ?? '');
                $id = trim($_POST['id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $now = gmdate('c');

                if ($id === '' || $name === '') {
                    throw new RuntimeException('Vui lòng nhập ID và tên chuyên mục.');
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('UPDATE ebook_categories SET id = ?, name = ?, description = ?, updated_at = ? WHERE id = ?');
                    $stmt->execute([$id, $name, $description, $now, $originalId]);
                    $message = 'Đã cập nhật chuyên mục ebook.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO ebook_categories (id, name, description, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), updated_at = VALUES(updated_at)
                    ');
                    $stmt->execute([$id, $name, $description, $now, $now]);
                    $message = 'Đã lưu chuyên mục ebook.';
                }
            }

            if ($section === 'ebook' && $action === 'save_ebook_store_link') {
                $originalId = trim($_POST['original_id'] ?? '');
                $id = trim($_POST['id'] ?? '');
                $ebookId = trim($_POST['ebook_id'] ?? '');
                $storeId = trim($_POST['store_id'] ?? '');
                $storeName = trim($_POST['store_name'] ?? '');
                $storeIcon = trim($_POST['store_icon'] ?? '');
                $url = trim($_POST['url'] ?? '');
                $isPrimary = !empty($_POST['is_primary']) ? 1 : 0;
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);
                $now = gmdate('c');

                if ($id === '') {
                    $id = bin2hex(random_bytes(8));
                }
                if ($ebookId === '' || $storeId === '' || $storeName === '' || $url === '') {
                    throw new RuntimeException('Vui lòng nhập ebook, store_id, store_name và URL.');
                }

                if ($isPrimary) {
                    $stmt = $pdo->prepare('UPDATE ebook_store_links SET is_primary = 0 WHERE ebook_id = ?');
                    $stmt->execute([$ebookId]);
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('
                        UPDATE ebook_store_links
                        SET id = ?, ebook_id = ?, store_id = ?, store_name = ?, store_icon = ?, url = ?, is_primary = ?, sort_order = ?, updated_at = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$id, $ebookId, $storeId, $storeName, $storeIcon, $url, $isPrimary, $sortOrder, $now, $originalId]);
                    $message = 'Đã cập nhật liên kết cửa hàng.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO ebook_store_links (id, ebook_id, store_id, store_name, store_icon, url, is_primary, sort_order, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE store_name = VALUES(store_name), store_icon = VALUES(store_icon), url = VALUES(url), is_primary = VALUES(is_primary), sort_order = VALUES(sort_order), updated_at = VALUES(updated_at)
                    ');
                    $stmt->execute([$id, $ebookId, $storeId, $storeName, $storeIcon, $url, $isPrimary, $sortOrder, $now, $now]);
                    $message = 'Đã lưu liên kết cửa hàng.';
                }
            }

            if ($section === 'music' && $action === 'delete_song') {
                $stmt = $pdo->prepare('DELETE FROM song WHERE id = ?');
                $stmt->execute([trim($_POST['id'] ?? '')]);
                admin_clear_carrotmusic_cache();
                $message = 'Đã xóa bài hát.';
            }

            if ($section === 'music' && $action === 'delete_song_artist') {
                $stmt = $pdo->prepare('DELETE FROM song_artist WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                admin_clear_carrotmusic_cache();
                $message = 'Đã xóa nghệ sĩ.';
            }

            if ($section === 'music' && $action === 'delete_song_genre') {
                $stmt = $pdo->prepare('DELETE FROM song_genre WHERE genre_id = ?');
                $stmt->execute([trim($_POST['genre_id'] ?? '')]);
                admin_clear_carrotmusic_cache();
                $message = 'Đã xóa thể loại.';
            }

            if ($section === 'music' && $action === 'delete_song_order') {
                $stmt = $pdo->prepare('DELETE FROM song_orders WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa đơn đặt hàng nhạc.';
            }

            if ($section === 'music' && $action === 'delete_failed_song_orders') {
                $stmt = $pdo->prepare("DELETE FROM song_orders WHERE COALESCE(status, '') <> 'COMPLETED'");
                $stmt->execute();
                $message = 'Đã xóa ' . number_format($stmt->rowCount()) . ' đơn nhạc thanh toán không thành công.';
            }

            if ($section === 'music' && $action === 'delete_song_search_log') {
                $stmt = $pdo->prepare('DELETE FROM song_search_log WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa lịch sử tìm kiếm.';
            }

            if ($section === 'music' && $action === 'clear_song_search_log') {
                $stmt = $pdo->prepare('DELETE FROM song_search_log');
                $stmt->execute();
                $message = 'Đã xóa ' . number_format($stmt->rowCount()) . ' lịch sử tìm kiếm.';
            }

            if ($section === 'music' && $action === 'save_song') {
                $originalId = trim($_POST['original_id'] ?? '');
                $id = trim($_POST['id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $artist = trim($_POST['artist'] ?? '');
                $album = trim($_POST['album'] ?? '');
                $genreValues = $_POST['genre'] ?? [];
                if (!is_array($genreValues)) {
                    $genreValues = [$genreValues];
                }
                $genreIds = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string) $value), $genreValues))));
                $genre = implode(',', $genreIds);
                $lang = trim($_POST['lang'] ?? 'vi') ?: 'vi';
                $year = trim($_POST['year'] ?? '');
                $date = trim($_POST['date'] ?? '');
                $publishedAt = trim($_POST['publishedAt'] ?? '');
                $linkYtb = trim($_POST['link_ytb'] ?? '');
                $mp3 = trim($_POST['mp3'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $lyrics = trim($_POST['lyrics'] ?? '');
                $artistIds = $_POST['artist_ids'] ?? [];
                if (!is_array($artistIds)) {
                    $artistIds = [$artistIds];
                }

                if ($id === '' || $name === '') {
                    throw new RuntimeException('Vui lòng nhập ID và tên bài hát.');
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('
                        UPDATE song
                        SET id = ?, name = ?, artist = ?, album = ?, genre = ?, lang = ?, year = ?, date = ?, publishedAt = ?, link_ytb = ?, mp3 = ?, avatar = ?, lyrics = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$id, $name, $artist, $album, $genre, $lang, $year, $date, $publishedAt, $linkYtb, $mp3, $avatar, $lyrics, $originalId]);
                    admin_sync_song_artists($pdo, $id, $artistIds);
                    foreach ($genreIds as $genreId) {
                        $pdo->prepare('INSERT IGNORE INTO song_genre (genre_id, title) VALUES (?, ?)')->execute([$genreId, $genreId]);
                    }
                    admin_clear_carrotmusic_cache();
                    $message = 'Đã cập nhật bài hát.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO song (id, name, artist, album, genre, lang, year, date, publishedAt, link_ytb, mp3, avatar, lyrics)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$id, $name, $artist, $album, $genre, $lang, $year, $date, $publishedAt, $linkYtb, $mp3, $avatar, $lyrics]);
                    admin_sync_song_artists($pdo, $id, $artistIds);
                    foreach ($genreIds as $genreId) {
                        $pdo->prepare('INSERT IGNORE INTO song_genre (genre_id, title) VALUES (?, ?)')->execute([$genreId, $genreId]);
                    }
                    admin_clear_carrotmusic_cache();
                    $message = 'Đã thêm bài hát.';
                }
            }

            if ($section === 'music' && $action === 'save_song_artist') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? 'vi') ?: 'vi';

                if ($name === '') {
                    throw new RuntimeException('Vui lòng nhập tên nghệ sĩ.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE song_artist SET name = ?, avatar = ?, description = ?, lang_key = ? WHERE id = ?');
                    $stmt->execute([$name, $avatar, $description, $langKey, $id]);
                    admin_clear_carrotmusic_cache();
                    $message = 'Đã cập nhật nghệ sĩ.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO song_artist (name, avatar, description, lang_key)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE avatar = VALUES(avatar), description = VALUES(description)
                    ');
                    $stmt->execute([$name, $avatar, $description, $langKey]);
                    admin_clear_carrotmusic_cache();
                    $message = 'Đã lưu nghệ sĩ.';
                }
            }

            if ($section === 'music' && $action === 'ajax_quick_add_song_artist') {
                try {
                    $name = trim($_POST['name'] ?? '');
                    $avatar = trim($_POST['avatar'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    $langKey = trim($_POST['lang_key'] ?? 'vi') ?: 'vi';

                    if ($name === '') {
                        throw new RuntimeException('Vui lòng nhập tên nghệ sĩ.');
                    }

                    $stmt = $pdo->prepare('
                        INSERT INTO song_artist (name, avatar, description, lang_key)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            id = LAST_INSERT_ID(id),
                            avatar = IF(VALUES(avatar) <> "", VALUES(avatar), avatar),
                            description = IF(VALUES(description) <> "", VALUES(description), description)
                    ');
                    $stmt->execute([$name, $avatar, $description, $langKey]);

                    $artistId = (int) $pdo->lastInsertId();
                    $artist = admin_fetch_song_artist($pdo, $artistId);
                    if (!$artist) {
                        throw new RuntimeException('Không tìm thấy nghệ sĩ vừa lưu.');
                    }

                    admin_clear_carrotmusic_cache();
                    admin_json_success([
                        'artist' => [
                            'id' => (int) $artist['id'],
                            'name' => (string) $artist['name'],
                            'lang_key' => (string) ($artist['lang_key'] ?? $langKey),
                        ],
                    ]);
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'music' && $action === 'ajax_ai_request_song_artist') {
                try {
                    $idea = trim($_POST['idea'] ?? '');
                    $artistName = trim($_POST['name'] ?? '');
                    $langKey = trim($_POST['lang_key'] ?? 'vi');
                    $currentDescription = trim($_POST['description'] ?? '');

                    if ($idea === '') {
                        throw new RuntimeException('Vui lòng nhập yêu cầu cho AI.');
                    }
                    if ($artistName === '') {
                        throw new RuntimeException('Vui lòng nhập tên nghệ sĩ trước khi yêu cầu AI.');
                    }
                    if ($langKey === '') {
                        throw new RuntimeException('Vui lòng chọn lang trước khi yêu cầu AI.');
                    }

                    admin_json_success(admin_gemini_generate_song_artist_description($pdo, $idea, $artistName, $langKey, $currentDescription));
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'music' && $action === 'ajax_ai_request_song_genre') {
                try {
                    $idea = trim($_POST['idea'] ?? '');
                    $genreId = trim($_POST['genre_id'] ?? '');
                    $title = trim($_POST['title'] ?? '');
                    $langKey = trim($_POST['lang_key'] ?? ($_SESSION['key_lang'] ?? 'vi'));
                    $currentDescription = trim($_POST['description'] ?? '');

                    if ($idea === '') {
                        throw new RuntimeException('Vui lòng nhập yêu cầu cho AI.');
                    }
                    if ($genreId === '' && $title === '') {
                        throw new RuntimeException('Vui lòng nhập Genre ID hoặc Title trước khi yêu cầu AI.');
                    }
                    if ($genreId === '') {
                        $genreId = $title;
                    }
                    if ($title === '') {
                        $title = $genreId;
                    }
                    if ($langKey === '') {
                        $langKey = 'vi';
                    }

                    admin_json_success(admin_gemini_generate_song_genre_description($pdo, $idea, $genreId, $title, $langKey, $currentDescription));
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'music' && $action === 'save_song_genre') {
                $originalId = trim($_POST['original_genre_id'] ?? '');
                $genreId = trim($_POST['genre_id'] ?? '');
                $title = trim($_POST['title'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if ($genreId === '') {
                    throw new RuntimeException('Vui lòng nhập genre_id.');
                }
                if ($title === '') {
                    $title = $genreId;
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('UPDATE song_genre SET genre_id = ?, title = ?, avatar = ?, description = ? WHERE genre_id = ?');
                    $stmt->execute([$genreId, $title, $avatar, $description, $originalId]);
                    admin_clear_carrotmusic_cache();
                    $message = 'Đã cập nhật thể loại.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO song_genre (genre_id, title, avatar, description)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE title = VALUES(title), avatar = VALUES(avatar), description = VALUES(description)
                    ');
                    $stmt->execute([$genreId, $title, $avatar, $description]);
                    admin_clear_carrotmusic_cache();
                    $message = 'Đã lưu thể loại.';
                }
            }

            if ($section === 'music' && $action === 'ajax_youtube_song') {
                try {
                    $url = trim($_POST['url'] ?? '');
                    if ($url === '') {
                        throw new RuntimeException('Vui lòng nhập link YouTube.');
                    }
                    $videoId = admin_extract_youtube_video_id($url);
                    if ($videoId === '') {
                        throw new RuntimeException('Không nhận diện được video ID.');
                    }

                    $existingSong = admin_find_song_by_youtube_video_id($pdo, $videoId);
                    if ($existingSong) {
                        $existingTitle = trim((string) ($existingSong['name'] ?? ''));
                        $existingId = trim((string) ($existingSong['id'] ?? ''));
                        $existingArtist = trim((string) ($existingSong['artist'] ?? ''));
                        $existingLabel = $existingTitle !== '' ? $existingTitle : $existingId;
                        if ($existingArtist !== '') {
                            $existingLabel .= ' - ' . $existingArtist;
                        }
                        admin_json_error('Link YouTube này đã tồn tại trong bài "' . $existingLabel . '" (ID: ' . $existingId . ').', 409);
                    }

                    $apiPdo = $homePdo instanceof PDO ? $homePdo : admin_home_pdo();
                    $musicSiteId = $pdo instanceof PDO ? admin_site_id_by_key($pdo, 'CarrotMusic', ['Music', 'music.carrot28.com']) : null;
                    $apiKey = admin_fetch_youtube_api_key($apiPdo, $pdo instanceof PDO ? $pdo : null, $musicSiteId);
                    if (!function_exists('curl_init')) {
                        throw new RuntimeException('Server cần bật PHP cURL.');
                    }

                    $endpoint = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
                        'part' => 'snippet,contentDetails',
                        'id' => $videoId,
                        'key' => $apiKey,
                    ]);
                    $curl = curl_init($endpoint);
                    curl_setopt_array($curl, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 20,
                    ]);
                    $response = curl_exec($curl);
                    $curlError = curl_error($curl);
                    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                    curl_close($curl);
                    if ($response === false) {
                        throw new RuntimeException('Không gọi được YouTube API: ' . ($curlError ?: 'cURL error'));
                    }

                    $payload = json_decode((string) $response, true);
                    if (!is_array($payload)) {
                        throw new RuntimeException('YouTube API trả về dữ liệu không hợp lệ.');
                    }

                    if ($statusCode >= 300) {
                        $apiMessage = trim((string) ($payload['error']['message'] ?? ''));
                        $apiReason = trim((string) ($payload['error']['errors'][0]['reason'] ?? ''));
                        $detail = $apiMessage !== '' ? $apiMessage : 'HTTP ' . $statusCode;
                        if ($apiReason !== '') {
                            $detail .= ' (' . $apiReason . ')';
                        }
                        throw new RuntimeException('YouTube API lỗi: ' . $detail);
                    }

                    if (empty($payload['items'][0]['snippet'])) {
                        throw new RuntimeException('Không tìm thấy video YouTube hoặc video không public.');
                    }
                    $snippet = $payload['items'][0]['snippet'];
                    $publishedAt = (string) ($snippet['publishedAt'] ?? '');
                    $publishedDate = '';
                    $publishedYear = '';
                    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $publishedAt, $dateMatches)) {
                        $publishedYear = $dateMatches[1];
                        $publishedDate = $dateMatches[1] . '-' . $dateMatches[2] . '-' . $dateMatches[3];
                    }
                    $thumbs = $snippet['thumbnails'] ?? [];
                    $avatar = $thumbs['maxres']['url'] ?? $thumbs['high']['url'] ?? $thumbs['medium']['url'] ?? $thumbs['default']['url'] ?? '';
                    admin_json_success([
                        'name' => (string) ($snippet['title'] ?? ''),
                        'artist' => (string) ($snippet['channelTitle'] ?? ''),
                        'album' => (string) ($snippet['channelTitle'] ?? ''),
                        'year' => $publishedYear,
                        'date' => $publishedDate,
                        'publishedAt' => $publishedAt,
                        'avatar' => (string) $avatar,
                        'lyrics' => (string) ($snippet['description'] ?? ''),
                    ]);
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'apps' && $action === 'save_app_store') {
                $id = (int) ($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $link = trim($_POST['link'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                if ($slug === '') {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?: 'store');
                    $slug = trim((string) $slug, '-');
                    if ($slug === '') {
                        $slug = 'store-' . ($id > 0 ? $id : time());
                    }
                }
                $platform = '';
                $status = 'active';
                $sortOrder = 0;

                if ($title === '' || $link === '') {
                    throw new RuntimeException('Vui lòng nhập tên store và link cho cổng phân phối.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE app_store SET slug = ?, title = ?, description = ?, icon = ?, link = ?, platform = ?, sort_order = ?, status = ? WHERE id = ?');
                    $stmt->execute([$slug, $title, $description, $icon, $link, $platform, $sortOrder, $status, $id]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã cập nhật cổng phân phối.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app_store (slug, title, description, icon, link, platform, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$slug, $title, $description, $icon, $link, $platform, $sortOrder, $status]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã thêm cổng phân phối.';
                }
            }

            if ($section === 'apps' && $action === 'save_app_category') {
                $originalId = trim($_POST['original_category_id'] ?? '');
                $categoryId = trim($_POST['category_id'] ?? '');
                $icon = trim($_POST['icon'] ?? '');

                if ($categoryId === '') {
                    throw new RuntimeException('Vui lòng nhập category_id.');
                }

                if ($originalId !== '') {
                    $stmt = $pdo->prepare('UPDATE app_category SET category_id = ?, icon = ? WHERE category_id = ?');
                    $stmt->execute([$categoryId, $icon, $originalId]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã cập nhật chuyên mục.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app_category (category_id, icon) VALUES (?, ?) ON DUPLICATE KEY UPDATE icon = VALUES(icon)');
                    $stmt->execute([$categoryId, $icon]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã lưu chuyên mục.';
                }
            }

            if ($section === 'apps' && $action === 'save_app_category_content') {
                $id = (int) ($_POST['id'] ?? 0);
                $categoryId = trim($_POST['category_id'] ?? '');
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $keyLang = trim($_POST['key_lang'] ?? '');

                if ($categoryId === '' || $title === '' || $keyLang === '') {
                    throw new RuntimeException('Vui lòng chọn category, nhập title và key_lang.');
                }

                if (!preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z]{2})?$/', $keyLang)) {
                    throw new RuntimeException('Key lang nên có dạng vi, en, en-US hoặc en_US.');
                }

                if (!admin_fetch_app_category($pdo, $categoryId)) {
                    throw new RuntimeException('Category được chọn không tồn tại.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE app_category_content SET category_id = ?, title = ?, description = ?, key_lang = ? WHERE id = ?');
                    $stmt->execute([$categoryId, $title, $description, $keyLang, $id]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã cập nhật nội dung chuyên mục.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO app_category_content (category_id, title, description, key_lang)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)
                    ');
                    $stmt->execute([$categoryId, $title, $description, $keyLang]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã lưu nội dung chuyên mục.';
                }
            }

            if ($section === 'apps' && $action === 'ajax_find_app_category_content_source') {
                $categoryId = trim($_POST['category_id'] ?? '');
                $keyLang = trim($_POST['key_lang'] ?? '');
                if ($categoryId === '' || $keyLang === '' || $keyLang === 'en') {
                    admin_json_success(['source_en' => null]);
                }

                $stmt = $pdo->prepare('
                    SELECT id, category_id, key_lang, title
                    FROM app_category_content
                    WHERE category_id = ? AND key_lang = "en" AND TRIM(title) <> ""
                    LIMIT 1
                ');
                $stmt->execute([$categoryId]);
                admin_json_success(['source_en' => $stmt->fetch() ?: null]);
            }

            if ($section === 'apps' && $action === 'ajax_ai_translate_app_category_content') {
                try {
                    $categoryId = trim($_POST['category_id'] ?? '');
                    $keyLang = trim($_POST['key_lang'] ?? '');
                    if ($categoryId === '' || $keyLang === '' || $keyLang === 'en') {
                        throw new RuntimeException('Vui lòng chọn category và key_lang khác en.');
                    }

                    $stmt = $pdo->prepare('
                        SELECT title, description
                        FROM app_category_content
                        WHERE category_id = ? AND key_lang = "en" AND TRIM(title) <> ""
                        LIMIT 1
                    ');
                    $stmt->execute([$categoryId]);
                    $source = $stmt->fetch();
                    if (!$source) {
                        throw new RuntimeException('Chưa có nội dung chuyên mục tiếng Anh để dịch.');
                    }

                    $translated = [
                        'title' => admin_gemini_translate($pdo, (string) ($source['title'] ?? ''), $keyLang, 'app category title'),
                        'description' => '',
                    ];
                    $sourceDescription = trim((string) ($source['description'] ?? ''));
                    if ($sourceDescription !== '') {
                        $translated['description'] = admin_gemini_translate($pdo, $sourceDescription, $keyLang, 'app category description');
                    }

                    admin_json_success($translated);
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'apps' && $action === 'ajax_ai_request_app_category_content') {
                try {
                    $idea = trim($_POST['idea'] ?? '');
                    $categoryId = trim($_POST['category_id'] ?? '');
                    $keyLang = trim($_POST['key_lang'] ?? 'vi');
                    $currentTitle = trim($_POST['title'] ?? '');
                    $currentDescription = trim($_POST['description'] ?? '');
                    if ($idea === '') {
                        throw new RuntimeException('Vui lòng nhập yêu cầu cho AI.');
                    }
                    if ($categoryId === '' || $keyLang === '') {
                        throw new RuntimeException('Vui lòng chọn category và key_lang trước khi yêu cầu AI.');
                    }

                    admin_json_success(admin_gemini_generate_category_content($pdo, $idea, $categoryId, $keyLang, $currentTitle, $currentDescription));
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
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
                    admin_clear_carrothome_cache();
                    $message = 'Đã cập nhật ảnh mô tả.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO app_photo (app_id, image_url, display_mode, sort_order) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$appId, $imageUrl, $displayMode, $sortOrder]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã thêm ảnh mô tả.';
                }
            }

            if ($section === 'apps' && $action === 'save_app_content') {
                $id = (int) ($_POST['id'] ?? 0);
                $appId = trim($_POST['app_id'] ?? '');
                $langKey = trim($_POST['lang_key'] ?? '');
                $title = trim($_POST['title'] ?? '');
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
                    $stmt = $pdo->prepare('UPDATE app_content SET app_id = ?, lang_key = ?, title = ?, content_html = ? WHERE id = ?');
                    $stmt->execute([$appId, $langKey, $title !== '' ? $title : null, $contentHtml, $id]);
                    admin_clear_carrothome_cache();
                    $message = 'Đã cập nhật nội dung mô tả.';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO app_content (app_id, lang_key, title, content_html)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE title = VALUES(title), content_html = VALUES(content_html)
                    ');
                    $stmt->execute([$appId, $langKey, $title !== '' ? $title : null, $contentHtml]);
                    admin_clear_carrothome_cache();
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
                        SELECT title, content_html
                        FROM app_content
                        WHERE app_id = ? AND lang_key = "en" AND TRIM(content_html) <> ""
                        LIMIT 1
                    ');
                    $stmt->execute([$appId]);
                    $sourceContent = $stmt->fetch() ?: null;
                    $sourceTitle = trim((string) ($sourceContent['title'] ?? ''));
                    $sourceHtml = trim((string) ($sourceContent['content_html'] ?? ''));
                    if ($sourceHtml === '') {
                        throw new RuntimeException('Chưa có mô tả app tiếng Anh để dịch.');
                    }

                    $translatedTitle = '';
                    if ($sourceTitle !== '') {
                        $translatedTitle = admin_gemini_translate($pdo, $sourceTitle, $langKey, 'app title plain text');
                    }
                    $translated = admin_gemini_translate($pdo, $sourceHtml, $langKey, 'app description html');
                    admin_json_success(['title' => $translatedTitle, 'content_html' => $translated]);
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'paypal' && $action === 'save_paypal_config') {
                $site = in_array($_POST['site'] ?? '', ['home', 'ebook', 'coc', 'music', 'cloud'], true) ? (string) $_POST['site'] : 'home';
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

            if ($section === 'cloud' && $action === 'delete_cloud_plan') {
                $stmt = $pdo->prepare('DELETE FROM cloud WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa gói Cloud.';
            }

            if ($section === 'cloud' && $action === 'delete_cloud_lang') {
                $stmt = $pdo->prepare('DELETE FROM cloud_lang WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa bản dịch gói Cloud.';
            }

            if ($section === 'cloud' && $action === 'delete_cloud_subscription') {
                $stmt = $pdo->prepare('DELETE FROM cloud_subscription WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa quyền sử dụng Cloud.';
            }

            if ($section === 'cloud' && $action === 'save_cloud_plan') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $limitGb = max(0, (float) ($_POST['limit_gb'] ?? 0));
                $price = max(0, (float) ($_POST['price'] ?? 0));
                $currency = strtoupper(trim($_POST['currency'] ?? 'USD')) ?: 'USD';
                $user = trim($_POST['user'] ?? '');
                $status = trim($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);

                if ($name === '' || $limitGb <= 0) {
                    throw new RuntimeException('Vui lòng nhập tên gói và dung lượng giới hạn lớn hơn 0 GB.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE cloud SET name = ?, description = ?, limit_gb = ?, price = ?, currency = ?, user = ?, status = ?, sort_order = ? WHERE id = ?');
                    $stmt->execute([$name, $description, $limitGb, $price, $currency, $user, $status, $sortOrder, $id]);
                    $message = 'Đã cập nhật gói Cloud.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cloud (name, description, limit_gb, price, currency, user, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $description, $limitGb, $price, $currency, $user, $status, $sortOrder]);
                    $message = 'Đã thêm gói Cloud.';
                }
            }

            if ($section === 'cloud' && $action === 'save_cloud_lang') {
                $id = (int) ($_POST['id'] ?? 0);
                $cloudId = (int) ($_POST['cloud_id'] ?? 0);
                $langKey = trim($_POST['lang_key'] ?? 'vi');
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if ($cloudId <= 0 || $langKey === '' || $name === '') {
                    throw new RuntimeException('Vui lòng chọn gói, lang key và nhập tên bản dịch.');
                }

                if ($languageOptions && !in_array($langKey, array_column($languageOptions, 'lang_key'), true)) {
                    throw new RuntimeException('Lang key không có trong danh sách country.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE cloud_lang SET cloud_id = ?, lang_key = ?, name = ?, description = ? WHERE id = ?');
                    $stmt->execute([$cloudId, $langKey, $name, $description, $id]);
                    $message = 'Đã cập nhật bản dịch gói Cloud.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cloud_lang (cloud_id, lang_key, name, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)');
                    $stmt->execute([$cloudId, $langKey, $name, $description]);
                    $message = 'Đã lưu bản dịch gói Cloud.';
                }
            }

            if ($section === 'cloud' && $action === 'save_cloud_subscription') {
                $id = (int) ($_POST['id'] ?? 0);
                $cloudId = (int) ($_POST['cloud_id'] ?? 0);
                $user = trim($_POST['user'] ?? '');
                $userId = trim($_POST['user_id'] ?? '') === '' ? null : (int) $_POST['user_id'];
                $payerEmail = trim($_POST['payer_email'] ?? '');
                $provider = trim($_POST['provider'] ?? '');
                $providerOrderId = trim($_POST['provider_order_id'] ?? '');
                $providerOrderId = $providerOrderId === '' ? null : $providerOrderId;
                $status = in_array($_POST['status'] ?? 'active', ['active', 'pending', 'expired', 'cancelled'], true) ? $_POST['status'] : 'active';
                $expiresAt = trim($_POST['expires_at'] ?? '');
                $expiresAt = $expiresAt === '' ? null : str_replace('T', ' ', $expiresAt);

                if ($cloudId <= 0 || $user === '') {
                    throw new RuntimeException('Vui lòng chọn gói và nhập user email/id.');
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE cloud_subscription SET cloud_id = ?, user = ?, user_id = ?, payer_email = ?, provider = ?, provider_order_id = ?, status = ?, expires_at = ? WHERE id = ?');
                    $stmt->execute([$cloudId, $user, $userId, $payerEmail, $provider, $providerOrderId, $status, $expiresAt, $id]);
                    $message = 'Đã cập nhật quyền sử dụng Cloud.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cloud_subscription (cloud_id, user, user_id, payer_email, provider, provider_order_id, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$cloudId, $user, $userId, $payerEmail, $provider, $providerOrderId, $status, $expiresAt]);
                    $message = 'Đã cấp quyền sử dụng Cloud.';
                }
            }

            if ($section === 'pages' && $action === 'delete_page') {
                $stmt = $homePdo->prepare('DELETE FROM page WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa page.';
            }

            if ($section === 'pages' && $action === 'delete_page_group') {
                $slug = trim($_POST['slug'] ?? '');
                if ($slug === '') {
                    throw new RuntimeException('Không tìm thấy slug cần xóa.');
                }

                $stmt = $homePdo->prepare('DELETE FROM page WHERE slug = ?');
                $stmt->execute([$slug]);
                $message = 'Đã xóa toàn bộ page thuộc slug "' . $slug . '".';
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
                        'title' => admin_gemini_translate($pdo, (string) ($sourceDetail['title'] ?? $slug), $lang, 'page title plain text'),
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

            if ($section === 'pages' && $action === 'ajax_ai_request_page') {
                try {
                    $idea = trim($_POST['idea'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $lang = trim($_POST['lang'] ?? 'vi');
                    $currentTitle = trim($_POST['title'] ?? '');
                    $currentContentHtml = trim($_POST['content_html'] ?? '');
                    if ($idea === '') {
                        throw new RuntimeException('Vui lòng nhập yêu cầu nội dung cho AI.');
                    }
                    if ($slug === '' || $lang === '') {
                        throw new RuntimeException('Vui lòng chọn slug và lang trước khi yêu cầu AI.');
                    }

                    admin_json_success(admin_gemini_generate_page($pdo, $idea, $slug, $lang, $currentTitle, $currentContentHtml));
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

            if ($section === 'users' && $action === 'delete_user') {
                $stmt = $homePdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa user.';
            }

            if ($section === 'users' && $action === 'save_user') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $lang = trim($_POST['lang'] ?? 'vi');
                $role = trim($_POST['role'] ?? 'user');
                $sex = trim($_POST['sex'] ?? '');
                $statusShare = trim($_POST['status_share'] ?? 'private');
                $type = trim($_POST['type'] ?? 'normal');
                $birthday = trim($_POST['birthday'] ?? '');
                $createdAt = trim($_POST['created_at'] ?? '');

                if ($name === '' || $email === '') {
                    throw new RuntimeException('Vui lòng nhập name và email.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Email không hợp lệ.');
                }

                if ($createdAt === '') {
                    $createdAt = date('Y-m-d H:i:s');
                }

                if ($password !== '' && password_get_info($password)['algo'] === 0) {
                    $password = password_hash($password, PASSWORD_DEFAULT);
                }

                if ($id > 0) {
                    $currentUser = admin_fetch_user($homePdo, $id);
                    if (!$currentUser) {
                        throw new RuntimeException('Không tìm thấy user cần cập nhật.');
                    }
                    if ($password === '') {
                        $password = (string) ($currentUser['password'] ?? '');
                    }
                    $stmt = $homePdo->prepare('
                        UPDATE users
                        SET address = ?, avatar = ?, created_at = ?, email = ?, lang = ?, name = ?, password = ?, phone = ?, role = ?, sex = ?, status_share = ?, type = ?, birthday = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$address, $avatar, $createdAt, $email, $lang, $name, $password, $phone, $role, $sex, $statusShare, $type, $birthday, $id]);
                    $message = 'Đã cập nhật user.';
                } else {
                    $stmt = $homePdo->prepare('
                        INSERT INTO users (address, avatar, created_at, email, lang, name, password, phone, role, sex, status_share, type, birthday)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$address, $avatar, $createdAt, $email, $lang, $name, $password, $phone, $role, $sex, $statusShare, $type, $birthday]);
                    $message = 'Đã thêm user mới.';
                }
            }

            if ($section === 'api' && $action === 'delete_api_config') {
                $stmt = $homePdo->prepare('DELETE FROM api_config WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa API config.';
            }

            if ($section === 'api' && $action === 'duplicate_api_config') {
                $sourceId = (int) ($_POST['id'] ?? 0);
                $sourceApi = admin_fetch_api_config($homePdo, $sourceId);
                if (!$sourceApi) {
                    throw new RuntimeException('Không tìm thấy API config cần nhân đôi.');
                }

                $stmt = $homePdo->prepare('
                    INSERT INTO api_config (provider, site_id, name, enabled, client_id, client_secret, api_key, project_url, redirect_uri, scopes, config_json, note)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    (string) ($sourceApi['provider'] ?? 'custom'),
                    !empty($sourceApi['site_id']) ? (int) $sourceApi['site_id'] : null,
                    trim((string) ($sourceApi['name'] ?? 'API')) . ' (Copy)',
                    (int) ($sourceApi['enabled'] ?? 0),
                    (string) ($sourceApi['client_id'] ?? ''),
                    (string) ($sourceApi['client_secret'] ?? ''),
                    (string) ($sourceApi['api_key'] ?? ''),
                    (string) ($sourceApi['project_url'] ?? ''),
                    (string) ($sourceApi['redirect_uri'] ?? ''),
                    (string) ($sourceApi['scopes'] ?? ''),
                    (string) ($sourceApi['config_json'] ?? ''),
                    (string) ($sourceApi['note'] ?? ''),
                ]);
                $message = 'Đã nhân đôi API config.';
            }

            if ($section === 'api' && $action === 'save_api_config') {
                $id = (int) ($_POST['id'] ?? 0);
                $provider = trim($_POST['provider'] ?? 'custom');
                $siteId = (int) ($_POST['site_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $enabled = isset($_POST['enabled']) ? 1 : 0;
                $clientId = trim($_POST['client_id'] ?? '');
                $clientSecret = trim($_POST['client_secret'] ?? '');
                $apiKey = trim($_POST['api_key'] ?? '');
                $projectUrl = trim($_POST['project_url'] ?? '');
                $redirectUri = trim($_POST['redirect_uri'] ?? '');
                $scopes = trim($_POST['scopes'] ?? '');
                $configJson = trim($_POST['config_json'] ?? '');
                $note = trim($_POST['note'] ?? '');

                if ($provider === '' || $name === '') {
                    throw new RuntimeException('Vui lòng nhập provider và name.');
                }

                if (!preg_match('/^[a-z0-9_-]+$/i', $provider)) {
                    throw new RuntimeException('Provider chỉ dùng chữ, số, gạch ngang hoặc gạch dưới.');
                }

                if ($siteId > 0 && !admin_fetch_sites($pdo, $siteId)) {
                    throw new RuntimeException('Site áp dụng API không tồn tại.');
                }

                if ($configJson !== '' && json_decode($configJson, true) === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Config JSON không hợp lệ.');
                }

                if ($id > 0) {
                    $stmt = $homePdo->prepare('
                        UPDATE api_config
                        SET provider = ?, site_id = ?, name = ?, enabled = ?, client_id = ?, client_secret = ?, api_key = ?, project_url = ?, redirect_uri = ?, scopes = ?, config_json = ?, note = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$provider, $siteId > 0 ? $siteId : null, $name, $enabled, $clientId, $clientSecret, $apiKey, $projectUrl, $redirectUri, $scopes, $configJson, $note, $id]);
                    $message = 'Đã cập nhật API config.';
                } else {
                    $stmt = $homePdo->prepare('
                        INSERT INTO api_config (provider, site_id, name, enabled, client_id, client_secret, api_key, project_url, redirect_uri, scopes, config_json, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$provider, $siteId > 0 ? $siteId : null, $name, $enabled, $clientId, $clientSecret, $apiKey, $projectUrl, $redirectUri, $scopes, $configJson, $note]);
                    $message = 'Đã thêm API config.';
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

            if ($section === 'sites' && $action === 'delete_sites') {
                $stmt = $pdo->prepare('DELETE FROM sites WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                admin_clear_internal_cache('overview_count_main_sites');
                admin_clear_carrothome_cache();
                admin_clear_carrotmusic_cache();
                $message = 'Đã xóa site.';
            }

            if ($section === 'sites' && $action === 'ajax_ai_request_site_description') {
                try {
                    $idea = trim($_POST['idea'] ?? '');
                    $name = trim($_POST['name'] ?? '');
                    $url = trim($_POST['url'] ?? '');
                    $currentDescription = trim($_POST['description'] ?? '');

                    if ($idea === '') {
                        throw new RuntimeException('Vui lòng nhập yêu cầu cho AI.');
                    }
                    if ($name === '') {
                        throw new RuntimeException('Vui lòng nhập tên site trước khi yêu cầu AI.');
                    }

                    admin_json_success(admin_gemini_generate_site_description($pdo, $idea, $name, $url, $currentDescription));
                } catch (Throwable $e) {
                    admin_json_error($e->getMessage());
                }
            }

            if ($section === 'sites' && $action === 'save_sites') {
                $originalId = (int) ($_POST['original_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $url = trim($_POST['url'] ?? '');
                $logo = trim($_POST['logo'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = ($_POST['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active';
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);

                if ($name === '' || $url === '') {
                    throw new RuntimeException('Vui lòng nhập đủ thông tin site (Tên và URL).');
                }

                if ($originalId > 0) {
                    $stmt = $pdo->prepare('UPDATE sites SET name = ?, url = ?, logo = ?, description = ?, status = ?, sort_order = ? WHERE id = ?');
                    $stmt->execute([$name, $url, $logo, $description, $status, $sortOrder, $originalId]);
                    $message = 'Đã cập nhật site.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO sites (name, url, logo, description, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $url, $logo, $description, $status, $sortOrder]);
                    $message = 'Đã thêm site mới.';
                }

                admin_clear_internal_cache('overview_count_main_sites');
                admin_clear_carrothome_cache();
                admin_clear_carrotmusic_cache();
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

            if ($section === 'country' && $action === 'delete_text_label_group') {
                $labelKey = trim($_POST['key'] ?? '');
                if ($labelKey === '') {
                    throw new RuntimeException('Không tìm thấy key cần xóa.');
                }

                $stmt = $pdo->prepare('DELETE FROM text_label WHERE `key` = ?');
                $stmt->execute([$labelKey]);
                $message = 'Đã xóa toàn bộ value thuộc key "' . $labelKey . '".';
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
                    $existingStmt = $pdo->prepare('SELECT id FROM text_label WHERE `key` = ? AND lang_key = ? AND id <> ? LIMIT 1');
                    $existingStmt->execute([$labelKey, $langKey, $id]);
                    $existingId = (int) $existingStmt->fetchColumn();

                    if ($existingId > 0) {
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare('UPDATE text_label SET value = ? WHERE id = ?');
                            $stmt->execute([$value, $existingId]);
                            $stmt = $pdo->prepare('DELETE FROM text_label WHERE id = ?');
                            $stmt->execute([$id]);
                            $pdo->commit();
                        } catch (Throwable $e) {
                            $pdo->rollBack();
                            throw $e;
                        }
                        $message = 'Đã gộp và cập nhật nhãn đã tồn tại.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE text_label SET `key` = ?, lang_key = ?, value = ? WHERE id = ?');
                        $stmt->execute([$labelKey, $langKey, $value, $id]);
                        $message = 'Đã cập nhật nhãn.';
                    }
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO text_label (`key`, lang_key, value)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE value = VALUES(value)
                    ');
                    $stmt->execute([$labelKey, $langKey, $value]);
                    $message = 'Đã lưu nhãn.';
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

        if ($section === 'ebook' && $editKey !== '') {
            if ($ebookTab === 'categories') {
                $editing = admin_fetch_ebook_category($pdo, $editKey);
            } elseif ($ebookTab === 'stores') {
                $editing = admin_fetch_ebook_store_link($pdo, $editKey);
            } else {
                $editing = admin_fetch_ebook($pdo, $editKey);
            }
        }

        if ($section === 'music' && $musicTab === 'songs' && $editKey !== '') {
            $editing = admin_fetch_song($pdo, $editKey);
        }

        if ($section === 'music' && $musicTab === 'artists' && $editId > 0) {
            $editing = admin_fetch_song_artist($pdo, $editId);
        }

        if ($section === 'music' && $musicTab === 'genres' && $editKey !== '') {
            $stmt = $pdo->prepare('SELECT * FROM song_genre WHERE genre_id = ?');
            $stmt->execute([$editKey]);
            $editing = $stmt->fetch() ?: null;
        }

        if ($section === 'pages' && $editId > 0) {
            $editing = admin_fetch_page($homePdo, $editId);
        }

        if ($section === 'users' && $editId > 0) {
            $editing = admin_fetch_user($homePdo, $editId);
        }

        if ($section === 'api' && $editId > 0) {
            $editing = admin_fetch_api_config($homePdo, $editId);
        }

        if ($section === 'bank' && $editId > 0) {
            $editing = admin_fetch_bank($pdo, $editId);
        }

        if ($section === 'sites' && $editId > 0) {
            $editing = admin_fetch_sites($pdo, $editId);
        }

        if ($section === 'country' && $countryTab === 'countries' && $editId > 0) {
            $editing = admin_fetch_country($pdo, $editId);
        }

        if ($section === 'country' && $countryTab === 'labels' && $editId > 0) {
            $editingLabel = admin_fetch_text_label($pdo, $editId);
        }

        if ($section === 'cloud' && $editId > 0) {
            if ($cloudTab === 'langs') {
                $editing = admin_fetch_cloud_lang($pdo, $editId);
            } elseif ($cloudTab === 'subscriptions') {
                $stmt = $pdo->prepare('SELECT * FROM cloud_subscription WHERE id = ?');
                $stmt->execute([$editId]);
                $editing = $stmt->fetch() ?: null;
            } else {
                $editing = admin_fetch_cloud_plan($pdo, $editId);
            }
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

        $ebookSortColumns = [
            'id' => 'ebook.id',
            'name' => 'ebook.name',
            'author' => 'ebook.author',
            'category' => 'ebook.category_id',
            'lang' => 'ebook.lang',
            'price' => 'ebook.price',
            'status' => 'ebook.status',
            'updated_at' => 'ebook.updated_at',
        ];
        [$ebookSort, $ebookDir] = admin_sort_state($ebookSortColumns, 'updated_at', 'DESC');

        $pageSortColumns = [
            'id' => 'id',
            'title' => 'title',
            'slug' => 'slug',
            'lang' => 'lang',
            'updated_at' => 'updated_at',
        ];
        [$pageSort, $pageDir] = admin_sort_state($pageSortColumns, 'updated_at', 'DESC');

        $userSortColumns = [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'type' => 'type',
            'created_at' => 'created_at',
        ];
        [$userSort, $userDir] = admin_sort_state($userSortColumns, 'created_at', 'DESC');

        $apiSortColumns = [
            'id' => 'api_config.id',
            'site_id' => 'api_config.site_id',
            'provider' => 'api_config.provider',
            'name' => 'api_config.name',
            'enabled' => 'api_config.enabled',
            'updated_at' => 'api_config.updated_at',
        ];
        [$apiSort, $apiDir] = admin_sort_state($apiSortColumns, 'updated_at', 'DESC');

        $bankSortColumns = [
            'id' => 'id',
            'name' => 'name',
            'account_name' => 'account_name',
            'account_number' => 'account_number',
        ];
        [$bankSort, $bankDir] = admin_sort_state($bankSortColumns, 'id', 'DESC');

        $cloudSortColumns = [
            'id' => 'id',
            'name' => 'name',
            'limit_gb' => 'limit_gb',
            'price' => 'price',
            'status' => 'status',
            'sort_order' => 'sort_order',
            'updated_at' => 'updated_at',
        ];
        [$cloudSort, $cloudDir] = admin_sort_state($cloudSortColumns, 'sort_order', 'ASC');

        $siteSortColumns = [
            'id' => 'id',
            'name' => 'name',
            'status' => 'status',
            'sort_order' => 'sort_order',
        ];
        [$sitesSort, $sitesDir] = admin_sort_state($siteSortColumns, 'sort_order', 'ASC');

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
        $appOrders = ($section === 'apps' && $appTab === 'orders')
            ? $pdo->query('
                SELECT app_orders.*, app.icon AS app_icon, app.decription AS app_description, users.name AS user_name, users.email AS user_email
                FROM app_orders
                LEFT JOIN app ON app.id = app_orders.app_id
                LEFT JOIN users ON users.id = app_orders.user_id
                ORDER BY app_orders.created_at DESC, app_orders.id DESC
            ')->fetchAll()
            : [];
        if ($section === 'ebook') {
            $ebookCategoryOptions = $pdo->query('SELECT id, name FROM ebook_categories ORDER BY name ASC, id ASC')->fetchAll();
            $ebooks = $pdo->query('
                SELECT ebook.*, ebook_categories.name AS category_name
                FROM ebook
                LEFT JOIN ebook_categories ON ebook_categories.id = ebook.category_id
                ORDER BY ' . admin_order_by($ebookSortColumns, $ebookSort, $ebookDir) . ', ebook.id ASC
            ')->fetchAll();
            if ($ebookTab === 'categories') {
                $ebookCategories = $pdo->query('
                    SELECT ebook_categories.*, COUNT(ebook.id) AS ebook_count
                    FROM ebook_categories
                    LEFT JOIN ebook ON ebook.category_id = ebook_categories.id
                    GROUP BY ebook_categories.id, ebook_categories.name, ebook_categories.description, ebook_categories.created_at, ebook_categories.updated_at
                    ORDER BY ebook_categories.name ASC
                ')->fetchAll();
            }
            if ($ebookTab === 'stores') {
                $ebookStoreLinks = $pdo->query('
                    SELECT ebook_store_links.*, ebook.name AS ebook_name
                    FROM ebook_store_links
                    LEFT JOIN ebook ON ebook.id = ebook_store_links.ebook_id
                    ORDER BY ebook_store_links.sort_order ASC, ebook_store_links.store_name ASC, ebook_store_links.id DESC
                ')->fetchAll();
            }
            if ($ebookTab === 'orders') {
                $ebookOrders = $pdo->query('
                    SELECT ebook_orders.*, ebook.name AS ebook_name, ebook.cover AS ebook_cover, users.name AS user_name, users.email AS user_email
                    FROM ebook_orders
                    LEFT JOIN ebook ON ebook.id = ebook_orders.ebook_id
                    LEFT JOIN users ON users.id = ebook_orders.user_id
                    ORDER BY ebook_orders.created_at DESC, ebook_orders.id DESC
                ')->fetchAll();
            }
        }
        if ($section === 'music') {
            $songWhere = [];
            $songParams = [];
            if ($songSearch !== '') {
                $songWhere[] = '(s.id LIKE :song_q_id OR s.name LIKE :song_q_name OR s.artist LIKE :song_q_artist OR EXISTS (
                    SELECT 1
                    FROM song_artist_map search_map
                    INNER JOIN song_artist search_artist ON search_artist.id = search_map.artist_id
                    WHERE search_map.song_id = s.id AND search_artist.name LIKE :song_q_managed_artist
                ))';
                $songSearchValue = '%' . $songSearch . '%';
                $songParams[':song_q_id'] = $songSearchValue;
                $songParams[':song_q_name'] = $songSearchValue;
                $songParams[':song_q_artist'] = $songSearchValue;
                $songParams[':song_q_managed_artist'] = $songSearchValue;
            }
            if ($songLangFilter !== '') {
                $songWhere[] = 's.lang = :song_lang';
                $songParams[':song_lang'] = $songLangFilter;
            }
            $songWhereSql = $songWhere ? ' WHERE ' . implode(' AND ', $songWhere) : '';
            $songCountStmt = $pdo->prepare('SELECT COUNT(*) FROM song s' . $songWhereSql);
            $songCountStmt->execute($songParams);
            $songTotal = (int) $songCountStmt->fetchColumn();
            $songTotalPages = max(1, (int) ceil($songTotal / $songPerPage));
            $songPage = min($songPage, $songTotalPages);
            $songOffset = ($songPage - 1) * $songPerPage;
            $songStmt = $pdo->prepare('
                SELECT s.*,
                    GROUP_CONCAT(DISTINCT sa.name ORDER BY sa.name SEPARATOR ", ") AS artist_names,
                    GROUP_CONCAT(DISTINCT sa.id ORDER BY sa.name SEPARATOR ",") AS artist_ids
                FROM song s
                LEFT JOIN song_artist_map sam ON sam.song_id = s.id
                LEFT JOIN song_artist sa ON sa.id = sam.artist_id
                ' . $songWhereSql . '
                GROUP BY s.id
                ORDER BY s.created_at DESC, s.id ASC
                LIMIT :limit OFFSET :offset
            ');
            foreach ($songParams as $paramKey => $paramValue) {
                $songStmt->bindValue($paramKey, $paramValue);
            }
            $songStmt->bindValue(':limit', $songPerPage, PDO::PARAM_INT);
            $songStmt->bindValue(':offset', $songOffset, PDO::PARAM_INT);
            $songStmt->execute();
            $songs = $songStmt->fetchAll();
        } else {
            $songs = [];
        }
        $songArtistOptions = $section === 'music'
            ? $pdo->query('SELECT * FROM song_artist ORDER BY name ASC, id DESC')->fetchAll()
            : [];
        if ($section === 'music') {
            $artistWhere = [];
            $artistParams = [];
            if ($artistSearch !== '') {
                $artistWhere[] = '(name LIKE :artist_q OR description LIKE :artist_description_q)';
                $artistSearchValue = '%' . $artistSearch . '%';
                $artistParams[':artist_q'] = $artistSearchValue;
                $artistParams[':artist_description_q'] = $artistSearchValue;
            }
            if ($artistLangFilter !== '') {
                $artistWhere[] = 'lang_key = :artist_lang';
                $artistParams[':artist_lang'] = $artistLangFilter;
            }
            $artistStmt = $pdo->prepare('SELECT * FROM song_artist' . ($artistWhere ? ' WHERE ' . implode(' AND ', $artistWhere) : '') . ' ORDER BY name ASC, id DESC');
            $artistStmt->execute($artistParams);
            $songArtists = $artistStmt->fetchAll();
        } else {
            $songArtists = [];
        }
        $songGenres = $section === 'music'
            ? $pdo->query('
                SELECT g.*, COUNT(s.id) AS song_count
                FROM song_genre g
                LEFT JOIN song s ON FIND_IN_SET(g.genre_id, REPLACE(COALESCE(s.genre, \'\'), \' \', \'\')) > 0
                GROUP BY g.genre_id, g.title, g.avatar, g.description, g.created_at, g.updated_at
                ORDER BY g.genre_id ASC
            ')->fetchAll()
            : [];
        $songOrders = ($section === 'music' && $musicTab === 'orders')
            ? $pdo->query('
                SELECT song_orders.*, song.name AS song_name, song.avatar AS song_avatar, song.mp3 AS song_mp3, users.name AS user_name, users.email AS user_email
                FROM song_orders
                LEFT JOIN song ON song.id = song_orders.song_id
                LEFT JOIN users ON users.id = song_orders.user_id
                ORDER BY song_orders.created_at DESC, song_orders.id DESC
            ')->fetchAll()
            : [];
        if ($section === 'music' && $musicTab === 'search_log') {
            $songSearchLogWhere = [];
            $songSearchLogParams = [];
            if ($songSearchLogQuery !== '') {
                $songSearchLogWhere[] = '(query LIKE :search_log_query OR normalized_query LIKE :search_log_normalized OR lang LIKE :search_log_lang OR ip_text LIKE :search_log_ip OR request_path LIKE :search_log_path)';
                $songSearchLogValue = '%' . $songSearchLogQuery . '%';
                $songSearchLogParams[':search_log_query'] = $songSearchLogValue;
                $songSearchLogParams[':search_log_normalized'] = $songSearchLogValue;
                $songSearchLogParams[':search_log_lang'] = $songSearchLogValue;
                $songSearchLogParams[':search_log_ip'] = $songSearchLogValue;
                $songSearchLogParams[':search_log_path'] = $songSearchLogValue;
            }
            $songSearchLogWhereSql = $songSearchLogWhere ? ' WHERE ' . implode(' AND ', $songSearchLogWhere) : '';

            $songSearchLogStats = $pdo->query('
                SELECT COUNT(*) AS total_rows,
                       COUNT(DISTINCT normalized_query) AS unique_queries,
                       COUNT(DISTINCT ip_text) AS unique_ips
                FROM song_search_log
            ')->fetch() ?: $songSearchLogStats;

            $songSearchLogCountStmt = $pdo->prepare('SELECT COUNT(*) FROM song_search_log' . $songSearchLogWhereSql);
            $songSearchLogCountStmt->execute($songSearchLogParams);
            $songSearchLogTotal = (int) $songSearchLogCountStmt->fetchColumn();
            $songSearchLogTotalPages = max(1, (int) ceil($songSearchLogTotal / $songSearchLogPerPage));
            $songSearchLogPage = min($songSearchLogPage, $songSearchLogTotalPages);
            $songSearchLogOffset = ($songSearchLogPage - 1) * $songSearchLogPerPage;

            $songSearchLogStmt = $pdo->prepare('
                SELECT *
                FROM song_search_log
                ' . $songSearchLogWhereSql . '
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
            foreach ($songSearchLogParams as $paramKey => $paramValue) {
                $songSearchLogStmt->bindValue($paramKey, $paramValue);
            }
            $songSearchLogStmt->bindValue(':limit', $songSearchLogPerPage, PDO::PARAM_INT);
            $songSearchLogStmt->bindValue(':offset', $songSearchLogOffset, PDO::PARAM_INT);
            $songSearchLogStmt->execute();
            $songSearchLogs = $songSearchLogStmt->fetchAll();
        }
        $apps = $section === 'apps'
            ? $pdo->query('SELECT * FROM app ORDER BY ' . admin_order_by($appSortColumns, $appSort, $appDir) . ', id ASC')->fetchAll()
            : [];
        $pages = $section === 'pages'
            ? $homePdo->query('SELECT * FROM page ORDER BY ' . admin_order_by($pageSortColumns, $pageSort, $pageDir) . ', id DESC')->fetchAll()
            : [];
        $users = $section === 'users'
            ? $homePdo->query('SELECT * FROM users ORDER BY ' . admin_order_by($userSortColumns, $userSort, $userDir) . ', id DESC')->fetchAll()
            : [];
        $apiConfigs = $section === 'api'
            ? $homePdo->query('
                SELECT api_config.*, sites.name AS site_name, sites.url AS site_url
                FROM api_config
                LEFT JOIN sites ON sites.id = api_config.site_id
                ORDER BY ' . admin_order_by($apiSortColumns, $apiSort, $apiDir) . ', api_config.id DESC
            ')->fetchAll()
            : [];
        $apiSiteOptions = $section === 'api' ? admin_fetch_api_site_options($pdo) : [];
        if ($section === 'pages') {
            $pageSlugOptions = array_values(array_unique(array_filter(array_map(static fn(array $page): string => (string) ($page['slug'] ?? ''), $pages))));
            sort($pageSlugOptions, SORT_NATURAL | SORT_FLAG_CASE);
        }
        $banks = $section === 'bank'
            ? $pdo->query('SELECT * FROM bank ORDER BY ' . admin_order_by($bankSortColumns, $bankSort, $bankDir))->fetchAll()
            : [];
        $sites = $section === 'sites'
            ? $pdo->query('SELECT * FROM sites ORDER BY ' . admin_order_by($siteSortColumns, $sitesSort, $sitesDir))->fetchAll()
            : [];
        $cloudPlans = $section === 'cloud'
            ? $pdo->query('SELECT * FROM cloud ORDER BY ' . admin_order_by($cloudSortColumns, $cloudSort, $cloudDir) . ', id ASC')->fetchAll()
            : [];
        $cloudLangs = $section === 'cloud'
            ? $pdo->query('
                SELECT cl.*, c.name AS plan_name
                FROM cloud_lang cl
                JOIN cloud c ON c.id = cl.cloud_id
                ORDER BY c.sort_order ASC, c.id ASC, cl.lang_key ASC
            ')->fetchAll()
            : [];
        $cloudSubscriptions = $section === 'cloud'
            ? $pdo->query('
                SELECT cs.*, c.name AS plan_name, c.limit_gb
                FROM cloud_subscription cs
                JOIN cloud c ON c.id = cs.cloud_id
                ORDER BY cs.updated_at DESC, cs.id DESC
                LIMIT 200
            ')->fetchAll()
            : [];
        $countries = $section === 'country'
            ? $pdo->query('SELECT * FROM country ORDER BY ' . admin_order_by($countrySortColumns, $countrySort, $countryDir))->fetchAll()
            : [];
        $textLabels = ($section === 'country' && $countryTab === 'labels')
            ? []
            : [];
        $selectedLabelKey = trim($_GET['label_key'] ?? '');
        if ($selectedLabelKey === '' && $editingLabel && !empty($editingLabel['key'])) {
            $selectedLabelKey = (string) $editingLabel['key'];
        }
        $selectedLabelRows = [];
        $selectedLabelLangs = [];
        $selectedLabelStats = ['label_count' => 0, 'langs' => '', 'latest_update' => null];
        if ($section === 'country' && $countryTab === 'labels') {
            $labelSearchHaving = '';
            $labelSearchParams = [];
            if ($textLabelSearch !== '') {
                $labelSearchHaving = ' HAVING `key` LIKE :label_key_q OR langs LIKE :label_lang_q OR searchable_text LIKE :label_text_q';
                $labelSearchValue = '%' . $textLabelSearch . '%';
                $labelSearchParams = [
                    ':label_key_q' => $labelSearchValue,
                    ':label_lang_q' => $labelSearchValue,
                    ':label_text_q' => $labelSearchValue,
                ];
            }

            $labelGroupSql = '
                SELECT `key`, COUNT(*) AS label_count, GROUP_CONCAT(DISTINCT lang_key ORDER BY lang_key SEPARATOR ", ") AS langs,
                       MAX(updated_at) AS latest_update, GROUP_CONCAT(value SEPARATOR " ") AS searchable_text
                FROM text_label
                GROUP BY `key`
            ';
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $labelGroupSql . $labelSearchHaving . ') label_groups');
            $countStmt->execute($labelSearchParams);
            $textLabelTotal = (int) $countStmt->fetchColumn();
            $textLabelTotalPages = max(1, (int) ceil($textLabelTotal / $textLabelPerPage));
            $textLabelPage = min($textLabelPage, $textLabelTotalPages);
            $textLabelOffset = ($textLabelPage - 1) * $textLabelPerPage;

            $labelStmt = $pdo->prepare($labelGroupSql . $labelSearchHaving . ' ORDER BY latest_update DESC, `key` ASC LIMIT :limit OFFSET :offset');
            foreach ($labelSearchParams as $paramKey => $paramValue) {
                $labelStmt->bindValue($paramKey, $paramValue);
            }
            $labelStmt->bindValue(':limit', $textLabelPerPage, PDO::PARAM_INT);
            $labelStmt->bindValue(':offset', $textLabelOffset, PDO::PARAM_INT);
            $labelStmt->execute();
            $textLabels = $labelStmt->fetchAll();

            if ($selectedLabelKey !== '') {
                $selectedLabelStatsStmt = $pdo->prepare('
                    SELECT COUNT(*) AS label_count, GROUP_CONCAT(DISTINCT lang_key ORDER BY lang_key SEPARATOR ", ") AS langs, MAX(updated_at) AS latest_update
                    FROM text_label
                    WHERE `key` = ?
                ');
                $selectedLabelStatsStmt->execute([$selectedLabelKey]);
                $selectedLabelStats = $selectedLabelStatsStmt->fetch() ?: $selectedLabelStats;

                $selectedLabelStmt = $pdo->prepare('SELECT * FROM text_label WHERE `key` = ? ORDER BY lang_key ASC, id DESC');
                $selectedLabelStmt->execute([$selectedLabelKey]);
                $selectedLabelRows = $selectedLabelStmt->fetchAll();
                $selectedLabelLangs = array_values(array_unique(array_filter(array_map(static fn(array $label): string => (string) ($label['lang_key'] ?? ''), $selectedLabelRows))));
            }

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
        $songs = [];
        $songArtistOptions = [];
        $songArtists = [];
        $songGenres = [];
        $songOrders = [];
        $songSearchLogs = [];
        $songSearchLogStats = ['total_rows' => 0, 'unique_queries' => 0, 'unique_ips' => 0];
        $songSearchLogTotal = 0;
        $songSearchLogTotalPages = 1;
        $pages = [];
        $users = [];
        $apiConfigs = [];
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
        $appOrders = [];
        $trafficIpRows = [];
        $backupItems = [];
    }
}

$photoText = ($section === 'coc' && $editing) ? implode("\n", coc_decode_photos($editing['photos'])) : '';
$pageTitle = ['overview' => 'Tổng quan', 'apps' => 'App', 'ebook' => 'Ebook', 'music' => 'Âm nhạc', 'pages' => 'Page', 'users' => 'Users', 'api' => 'API', 'bank' => 'Bank', 'sites' => 'Sites', 'coc' => 'Coc', 'country' => 'Country', 'paypal' => 'Paypal', 'ai_support' => 'AI Support', 'backup' => 'Sao Lưu'][$section] ?? 'Tổng quan';
$sectionLabels = ['overview' => 'tổng quan', 'apps' => 'ứng dụng', 'ebook' => 'ebook', 'music' => 'âm nhạc', 'pages' => 'Page/SEO', 'users' => 'người dùng', 'api' => 'API key', 'bank' => 'ngân hàng', 'sites' => 'website', 'coc' => 'shop', 'country' => 'quốc gia hỗ trợ', 'paypal' => 'PayPal', 'ai_support' => 'AI Support', 'cloud' => 'Cloud', 'backup' => 'sao lưu dữ liệu'];
$sectionTitles = ['overview' => 'Tổng quan', 'apps' => 'App Carrot Home', 'ebook' => 'CarrotEbook', 'music' => 'CarrotMusic', 'pages' => 'Page Carrot Home', 'users' => 'User Carrot Home', 'api' => 'API Config', 'bank' => 'Bank', 'sites' => 'Sites', 'coc' => 'Acc Clash of Clans', 'country' => 'Country', 'paypal' => 'Paypal Config', 'ai_support' => 'AI - Support', 'cloud' => 'CarrotCloud', 'backup' => 'Sao Lưu Database'];
$dashboardCards = [
    ['label' => 'App', 'value' => $dashboardMetrics['apps'], 'icon' => 'boxes'],
    ['label' => 'Page', 'value' => $dashboardMetrics['pages'], 'icon' => 'file-text'],
    ['label' => 'Users', 'value' => $dashboardMetrics['users'], 'icon' => 'users'],
    ['label' => 'Coc', 'value' => $dashboardMetrics['coc'], 'icon' => 'shield'],
    ['label' => 'Bài hát', 'value' => $dashboardMetrics['songs'], 'icon' => 'music-2'],
    ['label' => 'Ebook', 'value' => $dashboardMetrics['ebook'], 'icon' => 'book-open'],
    ['label' => 'Bank', 'value' => $dashboardMetrics['bank'], 'icon' => 'landmark'],
    ['label' => 'Sites', 'value' => $dashboardMetrics['sites'], 'icon' => 'globe'],
    ['label' => 'Cloud', 'value' => $dashboardMetrics['cloud'], 'icon' => 'cloud'],
    ['label' => 'Country', 'value' => $dashboardMetrics['country'], 'icon' => 'globe-2'],
    ['label' => 'IP hôm nay', 'value' => $trafficMetrics['total']['today_unique'], 'icon' => 'wifi', 'class' => 'dashboard-card-live-ip'],
];
$trafficRows = [
    ['label' => 'COC Shop', 'url' => 'https://coc.carrot28.com/', 'metrics' => $trafficMetrics['coc']],
    ['label' => 'CarrotHome', 'url' => 'https://home.carrot28.com/', 'metrics' => $trafficMetrics['home']],
    ['label' => 'CarrotEbook', 'url' => 'https://ebook.carrot28.com/', 'metrics' => $trafficMetrics['ebook']],
    ['label' => 'CarrotMusic', 'url' => 'https://music.carrot28.com/', 'metrics' => $trafficMetrics['music']],
    ['label' => 'Tổng cộng', 'url' => '', 'metrics' => $trafficMetrics['total']],
];
$useSelect2 = $section === 'overview' || $section === 'apps' || $section === 'ebook' || $section === 'music' || $section === 'pages' || $section === 'cloud' || ($section === 'country' && $countryTab === 'labels');
$sectionCreateUrls = [
    'apps' => 'index.php?section=apps',
    'ebook' => 'index.php?section=ebook&tab=' . urlencode($ebookTab),
    'music' => 'index.php?section=music&tab=' . urlencode($musicTab),
    'pages' => 'index.php?section=pages',
    'users' => 'index.php?section=users',
    'api' => 'index.php?section=api',
    'bank' => 'index.php?section=bank',
    'cloud' => 'index.php?section=cloud&tab=' . urlencode($cloudTab),
    'country' => 'index.php?section=country',
    'coc' => 'index.php?section=coc',
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
    <?php if ($useSelect2): ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php if ($useSelect2): ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($section === 'overview'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body{background:#eef2f7;color:#172033}
        .brand-mark{width:100%;height:36px;object-fit:contain}
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
        @media (max-width:991.98px){
            .dashboard-sidebar{position:relative;top:auto;min-height:0;margin:.75rem!important;padding:.75rem!important;overflow:hidden}
            .dashboard-brand{padding:0 0 .75rem;margin-bottom:.75rem!important}
            .dashboard-nav{flex-direction:row;flex-wrap:nowrap;gap:.5rem;margin:0 -.75rem;padding:0 .75rem .05rem;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;-ms-overflow-style:none;-webkit-overflow-scrolling:touch;scroll-snap-type:x proximity;cursor:grab;touch-action:pan-x}
            .dashboard-nav.is-dragging{cursor:grabbing;scroll-snap-type:none;user-select:none}
            .dashboard-nav::-webkit-scrollbar{display:none}
            .dashboard-nav .list-group-item{flex:0 0 auto;margin-bottom:0;white-space:nowrap;scroll-snap-align:start}
            .dashboard-nav.is-dragging .list-group-item{pointer-events:none}
        }
        .dashboard-main{min-width:0}
        .dashboard-topbar{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:rgba(255,255,255,.94);box-shadow:0 18px 48px rgba(15,23,42,.07)}
        .dashboard-eyebrow{color:#64748b;font-size:.76rem;letter-spacing:0;text-transform:uppercase}
        .dashboard-actions .btn{border-radius:8px}
        .dashboard-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.75rem}
        .dashboard-card{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.05);padding:.62rem .75rem}
        .dashboard-card-line{display:flex;align-items:center;gap:.55rem;min-width:0}
        .dashboard-card-icon{display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:28px;height:28px;border-radius:8px;background:#f1f5f9;color:#172033}
        .dashboard-card-icon i,.dashboard-card-icon svg{width:15px;height:15px}
        .dashboard-card-live-ip .dashboard-card-icon{position:relative;background:#dcfce7;color:#16a34a;animation:dashboardLivePulse 1.05s ease-in-out infinite;box-shadow:0 0 0 0 rgba(34,197,94,.52)}
        .dashboard-card-live-ip .dashboard-card-icon::after{content:"";position:absolute;inset:-4px;border:1px solid rgba(34,197,94,.48);border-radius:10px;animation:dashboardLiveRing 1.05s ease-out infinite}
        .dashboard-card-live-ip .dashboard-card-icon svg{animation:dashboardLiveBlink .7s steps(2,end) infinite}
        @keyframes dashboardLivePulse{0%,100%{background:#dcfce7;color:#16a34a;box-shadow:0 0 0 0 rgba(34,197,94,.38)}50%{background:#22c55e;color:#fff;box-shadow:0 0 18px 4px rgba(34,197,94,.48)}}
        @keyframes dashboardLiveRing{0%{opacity:.75;transform:scale(.86)}100%{opacity:0;transform:scale(1.42)}}
        @keyframes dashboardLiveBlink{0%,100%{opacity:1}50%{opacity:.28}}
        .dashboard-card-label{min-width:0;font-size:.72rem;color:#64748b;font-weight:800;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .dashboard-card-value{font-size:1.08rem;font-weight:850;line-height:1.05;white-space:nowrap}
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
        .traffic-tools{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:.65rem}
        .traffic-date-form{min-width:220px}
        .traffic-range-select{min-width:220px}
        .traffic-toggle{border:1px solid rgba(15,23,42,.12);border-radius:8px;padding:.25rem;background:#f8fafc}
        .traffic-toggle .btn{border-radius:6px;font-weight:800}
        .traffic-chart-wrap{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:#f8fafc;padding:1rem}
        .traffic-chart-title{font-size:1rem;font-weight:850;color:#172033}
        .traffic-chart-legend{display:flex;align-items:center;gap:.5rem;color:#475569;font-size:.82rem;font-weight:800}
        .traffic-legend-btn{display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap;border:1px solid rgba(15,23,42,.12);border-radius:999px;background:#fff;color:#475569;padding:.28rem .62rem;font:inherit;cursor:pointer;transition:opacity .15s ease,background .15s ease,border-color .15s ease}
        .traffic-legend-btn:hover{border-color:rgba(15,23,42,.24);background:#f8fafc}
        .traffic-legend-btn:not(.is-active){opacity:.42;background:#f1f5f9;text-decoration:line-through}
        .traffic-dot{display:inline-block;width:10px;height:10px;border-radius:999px}
        .traffic-dot-today{background:#0f766e}
        .traffic-dot-yesterday{background:#f59e0b}
        #traffic_compare_chart{display:block;width:100%;height:350px!important;max-height:350px}
        .backup-action-card{border:1px solid rgba(15,23,42,.08);border-radius:8px;background:#fff;padding:1rem;height:100%}
        .backup-progress{height:10px;border-radius:999px;background:#e2e8f0;overflow:hidden}
        .backup-progress-bar{height:100%;width:0;background:#198754;transition:width .25s ease}
        .backup-file-name{font-weight:800;overflow-wrap:anywhere}
        .glass-panel,.admin-shell{border:1px solid rgba(15,23,42,.08)!important;border-radius:8px!important;background:rgba(255,255,255,.96)!important;box-shadow:0 14px 36px rgba(15,23,42,.06)!important}
        .table{--bs-table-bg:transparent}
        .table thead th{color:#64748b;font-size:.78rem;text-transform:uppercase}
        .music-song-table{table-layout:fixed}
        .music-song-table th:nth-child(1){width:42%}
        .music-song-table th:nth-child(2){width:26%}
        .music-song-table th:nth-child(3){width:22%}
        .music-song-table th:nth-child(4){width:72px}
        .music-song-cell .d-flex{min-width:0}
        .music-song-cell a{flex:0 0 auto}
        .music-song-text{min-width:0;max-width:100%}
        .music-song-name,.music-song-id{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .api-config-name{display:flex;flex-wrap:wrap;align-items:baseline;gap:.45rem}
        .api-config-meta{display:grid;grid-template-columns:86px minmax(0,1fr);gap:.45rem;margin-top:.25rem;color:#64748b}
        .api-config-meta span{font-weight:800;text-transform:uppercase;font-size:.68rem}
        .api-config-meta code{white-space:normal;overflow-wrap:anywhere;word-break:break-word;color:#334155;background:transparent;padding:0}
        @media (max-width:1199px){.dashboard-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media (max-width:991px){.dashboard-sidebar{position:static;min-height:auto}.dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:575px){.dashboard-grid{grid-template-columns:1fr}.dashboard-card-value{font-size:1.05rem}.traffic-tools{width:100%;justify-content:stretch}.traffic-date-form{width:100%}.traffic-range-select{width:100%;min-width:0}.traffic-chart-legend{width:100%;justify-content:space-between}#traffic_compare_chart{height:260px!important;max-height:260px}}
    </style>
    <style>
        .simple-editor-toolbar{display:flex;flex-wrap:wrap;gap:.35rem;padding:.5rem;border:1px solid rgba(0,0,0,.15);border-bottom:0;border-radius:.375rem .375rem 0 0;background:rgba(255,255,255,.7)}
        .simple-editor-toolbar button{min-width:36px;border-radius:6px}
        .simple-editor-canvas{min-height:360px;padding:1rem;border:1px solid rgba(0,0,0,.15);border-radius:0 0 .375rem .375rem;background:#fff;color:#111;line-height:1.65;outline:0}
        .simple-editor-canvas:focus{box-shadow:0 0 0 .25rem rgba(25,135,84,.25)}
    </style>
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
                <a class="list-group-item list-group-item-action <?= $section === 'music' ? 'active' : '' ?>" href="index.php?section=music"><i data-lucide="music-2"></i><span>Âm nhạc</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'pages' ? 'active' : '' ?>" href="index.php?section=pages"><i data-lucide="file-text"></i><span>Page</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'users' ? 'active' : '' ?>" href="index.php?section=users"><i data-lucide="users"></i><span>User</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'api' ? 'active' : '' ?>" href="index.php?section=api"><i data-lucide="key-round"></i><span>API</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'coc' ? 'active' : '' ?>" href="index.php?section=coc"><i data-lucide="shield"></i><span>Coc</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'ebook' ? 'active' : '' ?>" href="index.php?section=ebook"><i data-lucide="book-open"></i><span>Ebook</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'paypal' ? 'active' : '' ?>" href="index.php?section=paypal"><i data-lucide="credit-card"></i><span>Paypal</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'ai_support' ? 'active' : '' ?>" href="index.php?section=ai_support"><i data-lucide="sparkles"></i><span>AI - Support</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'cloud' ? 'active' : '' ?>" href="index.php?section=cloud"><i data-lucide="cloud"></i><span>Cloud</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'bank' ? 'active' : '' ?>" href="index.php?section=bank"><i data-lucide="landmark"></i><span>Bank</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'sites' ? 'active' : '' ?>" href="index.php?section=sites"><i data-lucide="globe"></i><span>Sites</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'country' ? 'active' : '' ?>" href="index.php?section=country"><i data-lucide="globe-2"></i><span>Country</span></a>
                <a class="list-group-item list-group-item-action <?= $section === 'backup' ? 'active' : '' ?>" href="index.php?section=backup"><i data-lucide="database-backup"></i><span>Sao Lưu</span></a>
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
                        <?php if ($section === 'apps'): ?>
                            <a class="btn btn-success fw-bold" href="https://home.carrot28.com/" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link" style="width:16px;height:16px"></i> Carrot Store</a>
                        <?php endif; ?>
                        <?php if ($section === 'ebook'): ?>
                            <a class="btn btn-success fw-bold" href="https://ebook.carrot28.com/" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link" style="width:16px;height:16px"></i> CarrotEbook</a>
                        <?php endif; ?>
                        <?php if ($section === 'music'): ?>
                            <a class="btn btn-success fw-bold" href="https://music.carrot28.com/" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link" style="width:16px;height:16px"></i> CarrotMusic</a>
                        <?php endif; ?>
                        <?php if ($section === 'cloud'): ?>
                            <a class="btn btn-success fw-bold" href="https://cloud.carrot28.com/" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link" style="width:16px;height:16px"></i> CarrotCloud</a>
                        <?php endif; ?>
                        <?php if ($section === 'coc'): ?>
                            <a class="btn btn-secondary fw-bold" href="https://coc.carrot28.com/" target="_blank" rel="noopener noreferrer">Xem shop</a>
                        <?php endif; ?>
                        <?php if ($editing): ?>
                            <a class="btn btn-success fw-bold" href="<?= htmlspecialchars($sectionCreateUrls[$section] ?? 'index.php') ?>">Thêm mới</a>
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
            icon: form.dataset.confirmIcon || 'warning',
            title: form.dataset.confirmTitle || 'Xác nhận xóa',
            text: form.dataset.confirm || 'Bạn chắc chắn muốn xóa mục này?',
            showCancelButton: true,
            confirmButtonText: form.dataset.confirmButton || 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: form.dataset.confirmColor || '#dc3545',
        });

        if (result.isConfirmed) {
            form.submit();
        }
    });
});

function adminCreateFileDeleteButton(uploadButton) {
    const deleteButton = document.createElement('button');
    deleteButton.className = 'btn btn-outline-danger js-delete-file';
    deleteButton.type = 'button';
    deleteButton.dataset.target = uploadButton.dataset.target || '';
    deleteButton.dataset.typeMedia = uploadButton.dataset.typeMedia || '';
    deleteButton.dataset.mode = uploadButton.dataset.mode || 'replace';
    deleteButton.title = 'Delete file';
    deleteButton.setAttribute('aria-label', 'Delete file');
    deleteButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1 0-2H5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1h2.5a1 1 0 0 1 1 1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/></svg>';
    uploadButton.insertAdjacentElement('afterend', deleteButton);
    return deleteButton;
}

document.querySelectorAll('.js-upload').forEach((button) => {
    if (!button.parentElement || !button.parentElement.querySelector(`.js-delete-file[data-target="${button.dataset.target}"]`)) {
        adminCreateFileDeleteButton(button);
    }
});

document.querySelectorAll('.js-delete-file').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.target);
        if (!target) {
            return;
        }

        const values = target.value.split(/\r?\n/).map((item) => item.trim()).filter(Boolean);
        if (!values.length) {
            await Swal.fire({icon: 'info', title: 'Chưa có file', text: 'Field này chưa có URL file để xóa.'});
            return;
        }

        let fileUrl = values[0];
        if (values.length > 1) {
            const options = {};
            values.forEach((value) => {
                options[value] = value;
            });
            const pick = await Swal.fire({
                icon: 'question',
                title: 'Chọn file cần xóa',
                input: 'select',
                inputOptions: options,
                inputValue: fileUrl,
                showCancelButton: true,
                confirmButtonText: 'Tiếp tục',
                cancelButtonText: 'Hủy',
            });
            if (!pick.isConfirmed || !pick.value) {
                return;
            }
            fileUrl = pick.value;
        }

        const confirm = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa file',
            text: fileUrl,
            showCancelButton: true,
            confirmButtonText: 'Xóa file',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                const formData = new FormData();
                formData.append('action', 'ajax_delete_file');
                formData.append('type_media', button.dataset.typeMedia || '');
                formData.append('image_url', fileUrl);

                try {
                    const response = await fetch('index.php', {
                        method: 'POST',
                        body: formData,
                    });
                    const payload = await response.json();
                    if (!response.ok || payload.status !== 'success') {
                        throw new Error(payload.message || 'Xóa file thất bại.');
                    }
                    return payload;
                } catch (error) {
                    Swal.showValidationMessage(error.message);
                    return false;
                }
            },
            allowOutsideClick: () => !Swal.isLoading(),
        });

        if (!confirm.isConfirmed || !confirm.value) {
            return;
        }

        if (values.length > 1 || button.dataset.mode === 'append') {
            target.value = values.filter((value) => value !== fileUrl).join('\n');
        } else {
            target.value = '';
        }

        Swal.fire({
            icon: 'success',
            title: 'Đã xóa file',
            timer: 1400,
            showConfirmButton: false,
        });
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

const siteNameInput = document.getElementById('sites_name');
const siteUrlInput = document.getElementById('sites_url');
const siteDescriptionInput = document.getElementById('sites_description');
const aiSiteDescriptionButton = document.querySelector('.js-ai-site-description-request');
if (siteNameInput && siteDescriptionInput && aiSiteDescriptionButton) {
    aiSiteDescriptionButton.addEventListener('click', async () => {
        const siteName = siteNameInput.value.trim();
        const siteUrl = siteUrlInput ? siteUrlInput.value.trim() : '';
        if (!siteName) {
            await Swal.fire({icon: 'warning', title: 'Thiếu tên site', text: 'Vui lòng nhập tên site trước khi yêu cầu AI.'});
            return;
        }

        const requestResult = await Swal.fire({
            title: 'Yêu cầu AI',
            input: 'textarea',
            inputLabel: 'Bạn muốn AI viết hoặc chỉnh mô tả site này như thế nào?',
            inputPlaceholder: 'Ví dụ: Viết mô tả ngắn gọn, thân thiện, nêu rõ nội dung chính của site...',
            inputAttributes: {
                'aria-label': 'Yêu cầu mô tả Site cho AI',
            },
            inputAutoTrim: true,
            showCancelButton: true,
            confirmButtonText: 'Tạo nội dung',
            cancelButtonText: 'Hủy',
            preConfirm: (value) => {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('Vui lòng nhập yêu cầu cho AI.');
                    return false;
                }
                return value.trim();
            },
        });

        if (!requestResult.isConfirmed) {
            return;
        }

        aiSiteDescriptionButton.disabled = true;
        const originalHtml = aiSiteDescriptionButton.innerHTML;
        aiSiteDescriptionButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang viết</span>';

        const formData = new FormData();
        formData.append('action', 'ajax_ai_request_site_description');
        formData.append('idea', requestResult.value);
        formData.append('name', siteName);
        formData.append('url', siteUrl);
        formData.append('description', siteDescriptionInput.value.trim());

        try {
            const response = await fetch('index.php?section=sites', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Không tạo được mô tả Site theo yêu cầu AI.');
            }
            if (typeof payload.description === 'string' && payload.description !== '') {
                siteDescriptionInput.value = payload.description;
            }
            await Swal.fire({icon: 'success', title: 'Đã tạo nội dung', text: 'AI đã chèn mô tả vào form.'});
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
        } finally {
            aiSiteDescriptionButton.disabled = false;
            aiSiteDescriptionButton.innerHTML = originalHtml;
            if (window.lucide) {
                lucide.createIcons();
            }
        }
    });
}

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
if (trafficChartCanvas && trafficChartDataEl && window.Chart) {
    const trafficChartData = JSON.parse(trafficChartDataEl.textContent || '{}');
    const trafficChart = new Chart(trafficChartCanvas, {
        type: 'line',
        data: {
            labels: Array.isArray(trafficChartData.labels) ? trafficChartData.labels : [],
            datasets: [
                {
                    label: 'Hits',
                    data: Array.isArray(trafficChartData.hits) ? trafficChartData.hits.map(Number) : [],
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15, 118, 110, .12)',
                    borderWidth: 3,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    tension: .32,
                    fill: true,
                },
                {
                    label: 'IP',
                    data: Array.isArray(trafficChartData.unique) ? trafficChartData.unique.map(Number) : [],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, .10)',
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    tension: .32,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${Number(context.parsed.y || 0).toLocaleString('vi-VN')}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        maxTicksLimit: (trafficChartData.mode || 'daily') === 'hourly' ? 12 : 10,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        callback: (value) => Number(value).toLocaleString('vi-VN'),
                    },
                },
            },
        },
    });
    document.querySelectorAll('.traffic-legend-btn[data-traffic-dataset]').forEach((button) => {
        button.addEventListener('click', () => {
            const datasetIndex = Number(button.dataset.trafficDataset || 0);
            const isVisible = trafficChart.isDatasetVisible(datasetIndex);
            trafficChart.setDatasetVisibility(datasetIndex, !isVisible);
            trafficChart.update();
            button.classList.toggle('is-active', !isVisible);
            button.setAttribute('aria-pressed', String(!isVisible));
        });
    });
}

const backupPost = async (data) => {
    const formData = new FormData();
    formData.append('action', 'backup_ajax');
    Object.entries(data).forEach(([key, value]) => formData.append(key, value));
    const response = await fetch('index.php?section=backup', {
        method: 'POST',
        body: formData,
    });
    const rawText = await response.text();
    let payload = null;
    try {
        payload = JSON.parse(rawText);
    } catch (error) {
        const message = rawText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(message || 'Server không trả JSON. Có thể phiên đăng nhập đã hết hạn hoặc PHP đang báo lỗi.');
    }
    if (!response.ok || payload.status !== 'success') {
        throw new Error(payload.message || 'Tác vụ backup thất bại.');
    }
    return payload;
};

const renderBackupRows = (items) => {
    const list = document.querySelector('.js-backup-list');
    if (!list) {
        return;
    }
    if (!Array.isArray(items) || !items.length) {
        list.innerHTML = '<tr><td colspan="6" class="text-center muted-text py-4">Chưa có bản sao lưu.</td></tr>';
        return;
    }
    list.innerHTML = items.map((item) => `
        <tr>
            <td><div class="backup-file-name">${escapeHtml(item.file || '')}</div><div class="small muted-text">${escapeHtml(item.status || '')}</div></td>
            <td>${escapeHtml(item.size_label || '')}</td>
            <td>${escapeHtml(item.created_at || '')}</td>
            <td class="text-end">${Number(item.tables || 0).toLocaleString('vi-VN')}</td>
            <td class="text-end">${Number(item.rows || 0).toLocaleString('vi-VN')}</td>
            <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-secondary fw-bold ${item.complete ? '' : 'disabled'}" href="${item.complete ? `index.php?action=backup_download&file=${encodeURIComponent(item.file || '')}` : '#'}">Tải</a>
                    <button class="btn btn-outline-danger fw-bold js-backup-restore" type="button" data-file="${escapeHtml(item.file || '')}" ${item.complete ? '' : 'disabled'}>Khôi phục</button>
                    <button class="btn btn-outline-danger fw-bold js-backup-delete" type="button" data-file="${escapeHtml(item.file || '')}" title="Xóa backup" aria-label="Xóa backup">
                        <i data-lucide="trash-2" style="width:15px;height:15px"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    if (window.lucide) {
        lucide.createIcons();
    }
};

const refreshBackupList = async () => {
    const payload = await backupPost({backup_task: 'list'});
    renderBackupRows(payload.items || []);
};

const setBackupProgress = (progress) => {
    const wrap = document.querySelector('.js-backup-progress-wrap');
    const status = document.querySelector('.js-backup-status');
    const percent = document.querySelector('.js-backup-percent');
    const bar = document.querySelector('.js-backup-progress-bar');
    if (!wrap || !status || !percent || !bar) {
        return;
    }
    wrap.classList.remove('d-none');
    const value = Number(progress.percent || 0);
    status.textContent = progress.table ? `Đang xử lý: ${progress.table}` : (progress.file || 'Đang xử lý...');
    percent.textContent = `${value}%`;
    bar.style.width = `${value}%`;
};

document.querySelectorAll('.js-backup-refresh').forEach((button) => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            await refreshBackupList();
        } catch (error) {
            Swal.fire({icon: 'error', title: 'Không làm mới được', text: error.message});
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelectorAll('.js-backup-cleanup').forEach((button) => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const payload = await backupPost({backup_task: 'cleanup'});
            renderBackupRows(payload.items || []);
            Swal.fire({
                icon: 'success',
                title: 'Đã dọn file lỗi',
                text: `Đã xóa ${Number(payload.deleted || 0).toLocaleString('vi-VN')} file incomplete.`,
                timer: 1500,
                showConfirmButton: false,
            });
        } catch (error) {
            Swal.fire({icon: 'error', title: 'Không dọn được', text: error.message});
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelectorAll('.js-backup-start').forEach((button) => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            let progress = await backupPost({backup_task: 'start_backup'});
            setBackupProgress(progress);
            while (progress.job_status !== 'complete') {
                progress = await backupPost({backup_task: 'process_backup', job_id: progress.job_id});
                setBackupProgress(progress);
                await new Promise((resolve) => setTimeout(resolve, 120));
            }
            await refreshBackupList();
            Swal.fire({icon: 'success', title: 'Đã sao lưu', text: progress.file || '', timer: 1600, showConfirmButton: false});
        } catch (error) {
            Swal.fire({icon: 'error', title: 'Sao lưu thất bại', text: error.message});
        } finally {
            button.disabled = false;
        }
    });
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.js-backup-restore');
    if (!button) {
        return;
    }
    const file = button.dataset.file || '';
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Khôi phục backup?',
        text: `Database hiện tại sẽ bị ghi đè bằng file ${file}.`,
        showCancelButton: true,
        confirmButtonText: 'Khôi phục',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc3545',
    });
    if (!result.isConfirmed) {
        return;
    }

    button.disabled = true;
    try {
        let progress = await backupPost({backup_task: 'start_restore', file});
        Swal.fire({
            title: 'Đang khôi phục',
            html: `<div class="backup-progress"><div class="backup-progress-bar" id="restore-progress-bar" style="width:${Number(progress.percent || 0)}%"></div></div><div class="small fw-bold mt-2" id="restore-progress-text">${Number(progress.percent || 0)}%</div>`,
            allowOutsideClick: false,
            showConfirmButton: false,
        });
        while (progress.job_status !== 'complete') {
            progress = await backupPost({backup_task: 'process_restore', job_id: progress.job_id});
            const percentValue = Number(progress.percent || 0);
            const bar = document.getElementById('restore-progress-bar');
            const text = document.getElementById('restore-progress-text');
            if (bar) bar.style.width = `${percentValue}%`;
            if (text) text.textContent = `${percentValue}% - ${Number(progress.done_statements || 0).toLocaleString('vi-VN')} statements`;
            await new Promise((resolve) => setTimeout(resolve, 120));
        }
        await refreshBackupList();
        Swal.fire({icon: 'success', title: 'Đã khôi phục', text: progress.file || ''});
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Khôi phục thất bại', text: error.message});
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.js-backup-delete');
    if (!button) {
        return;
    }
    const file = button.dataset.file || '';
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Xóa backup?',
        text: `File ${file} sẽ bị xóa khỏi storage/backups.`,
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc3545',
    });
    if (!result.isConfirmed) {
        return;
    }

    button.disabled = true;
    try {
        const payload = await backupPost({backup_task: 'delete', file});
        renderBackupRows(payload.items || []);
        Swal.fire({
            icon: 'success',
            title: 'Đã xóa backup',
            timer: 1000,
            showConfirmButton: false,
        });
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Không xóa được', text: error.message});
    } finally {
        button.disabled = false;
    }
});

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
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-sm btn-secondary js-ai-label-batch-generate" type="button" ${englishValue ? '' : 'disabled'}>
                        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="sparkles" style="width:16px;height:16px"></i>Tạo bằng AI</span>
                    </button>
                </div>
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
                const aiBatchButton = document.querySelector('.swal2-container .js-ai-label-batch-generate');
                if (!aiBatchButton) {
                    return;
                }

                aiBatchButton.addEventListener('click', async () => {
                    const inputs = Array.from(document.querySelectorAll('.swal2-container .js-translation-input'))
                        .filter((input) => input.dataset.langKey && input.dataset.langKey !== 'en');
                    if (!inputs.length || !englishValue) {
                        return;
                    }

                    aiBatchButton.disabled = true;
                    const originalHtml = aiBatchButton.innerHTML;
                    aiBatchButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang tạo</span>';

                    try {
                        for (const input of inputs) {
                            const formData = new FormData();
                            formData.append('action', 'ajax_ai_translate_label');
                            formData.append('key', labelKey);
                            formData.append('lang_key', input.dataset.langKey);

                            const response = await fetch('index.php?section=country&tab=labels', {
                                method: 'POST',
                                body: formData,
                            });
                            const payload = await response.json();
                            if (!response.ok || payload.status !== 'success') {
                                throw new Error(payload.message || `Không dịch được ${input.dataset.langKey}.`);
                            }
                            input.value = payload.value || '';
                        }
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                    } finally {
                        aiBatchButton.disabled = false;
                        aiBatchButton.innerHTML = originalHtml;
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    }
                });
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

    jQuery('.js-traffic-range-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        minimumResultsForSearch: Infinity,
    }).on('select2:select', async function () {
        const select = this;
        const form = select.closest('form');
        if (!form) {
            return;
        }

        if (select.value !== 'custom') {
            form.submit();
            return;
        }

        const fromInput = form.querySelector('input[name="traffic_from"]');
        const toInput = form.querySelector('input[name="traffic_to"]');
        const result = await Swal.fire({
            title: 'Chọn khoảng thời gian',
            html: `
                <div class="text-start">
                    <label class="form-label fw-bold" for="traffic-custom-from">Từ ngày</label>
                    <input id="traffic-custom-from" class="form-control mb-3" type="date" value="${escapeHtml(select.dataset.currentFrom || '')}">
                    <label class="form-label fw-bold" for="traffic-custom-to">Đến ngày</label>
                    <input id="traffic-custom-to" class="form-control" type="date" value="${escapeHtml(select.dataset.currentTo || '')}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Xem',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            preConfirm: () => {
                const from = document.getElementById('traffic-custom-from')?.value || '';
                const to = document.getElementById('traffic-custom-to')?.value || '';
                if (!from || !to) {
                    Swal.showValidationMessage('Vui lòng chọn đủ ngày bắt đầu và kết thúc.');
                    return false;
                }
                return {from, to};
            },
        });

        if (result.isConfirmed && result.value) {
            fromInput.value = result.value.from;
            toInput.value = result.value.to;
            form.submit();
        }
    });

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

    jQuery('.js-api-site-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn site áp dụng',
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

    jQuery('.js-app-category-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn category',
    });

    jQuery('.js-country-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn lang',
    });

    jQuery('.js-country-filter-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Tất cả',
    });

    jQuery('.js-music-artist-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chọn nghệ sĩ',
    });

    jQuery('.js-music-genre-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        tags: true,
        placeholder: 'Chọn hoặc nhập thể loại',
    });
}

const quickAddSongArtistButton = document.querySelector('.js-quick-add-song-artist');
const songArtistSelect = document.getElementById('song_artist_ids');
const songLangSelect = document.getElementById('song_lang');
if (quickAddSongArtistButton && songArtistSelect) {
    quickAddSongArtistButton.addEventListener('click', async () => {
        const defaultLang = (songLangSelect && songLangSelect.value ? songLangSelect.value : 'vi');
        const langOptions = songLangSelect ? songLangSelect.innerHTML : `<option value="${defaultLang}">${defaultLang}</option>`;
        const result = await Swal.fire({
            title: 'Thêm nhanh nghệ sĩ',
            width: 760,
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label" for="quick_song_artist_name">Name</label>
                        <input id="quick_song_artist_name" class="form-control" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="quick_song_artist_avatar">Avatar</label>
                        <div class="input-group">
                            <input id="quick_song_artist_avatar" class="form-control" autocomplete="off">
                            <button class="btn btn-secondary" id="quick_song_artist_upload" type="button">Upload</button>
                        </div>
                        <input id="quick_song_artist_file" class="d-none" type="file" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-label mb-0" for="quick_song_artist_description_editor">Mô tả</label>
                            <button class="btn btn-sm btn-primary" id="quick_song_artist_ai" type="button">Yêu cầu AI</button>
                        </div>
                        <textarea id="quick_song_artist_ai_idea" class="form-control mb-2" rows="2" placeholder="Yêu cầu AI viết hoặc chỉnh mô tả nghệ sĩ..."></textarea>
                        <div class="simple-editor-toolbar" role="toolbar" aria-label="Editor toolbar">
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="bold"><strong>B</strong></button>
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="italic"><em>I</em></button>
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="formatBlock" data-quick-editor-value="h2">H2</button>
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="formatBlock" data-quick-editor-value="p">P</button>
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="insertUnorderedList">List</button>
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="createLink">Link</button>
                            <button class="btn btn-sm btn-light" type="button" data-quick-editor-command="removeFormat">Clear</button>
                        </div>
                        <div class="simple-editor-canvas" id="quick_song_artist_description_editor" contenteditable="true" spellcheck="true" style="min-height:220px;margin-top:8px;padding:5px;border:1px solid rgba(0,0,0,.15)"></div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="quick_song_artist_lang">Lang key</label>
                        <select id="quick_song_artist_lang" class="form-control">${langOptions}</select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Thêm',
            cancelButtonText: 'Hủy',
            focusConfirm: false,
            didOpen: () => {
                const nameInput = document.getElementById('quick_song_artist_name');
                const langInput = document.getElementById('quick_song_artist_lang');
                const avatarInput = document.getElementById('quick_song_artist_avatar');
                const fileInput = document.getElementById('quick_song_artist_file');
                const uploadButton = document.getElementById('quick_song_artist_upload');
                const aiButton = document.getElementById('quick_song_artist_ai');
                const aiIdeaInput = document.getElementById('quick_song_artist_ai_idea');
                const editor = document.getElementById('quick_song_artist_description_editor');

                if (langInput) {
                    langInput.value = defaultLang;
                }
                if (nameInput) {
                    nameInput.focus();
                }
                document.querySelectorAll('[data-quick-editor-command]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const command = button.dataset.quickEditorCommand;
                        let value = button.dataset.quickEditorValue || null;
                        if (command === 'createLink') {
                            value = window.prompt('URL');
                            if (!value) return;
                        }
                        if (editor) {
                            editor.focus();
                        }
                        document.execCommand(command, false, value);
                    });
                });

                if (uploadButton && fileInput && avatarInput) {
                    uploadButton.addEventListener('click', () => fileInput.click());
                    fileInput.addEventListener('change', async () => {
                        const file = fileInput.files ? fileInput.files[0] : null;
                        if (!file) {
                            return;
                        }

                        const originalText = uploadButton.textContent;
                        uploadButton.disabled = true;
                        uploadButton.textContent = 'Uploading...';
                        try {
                            const uploadData = new FormData();
                            uploadData.append('action', 'ajax_upload');
                            uploadData.append('type_media', 'artist_avatar');
                            uploadData.append('file', file);
                            const response = await fetch('index.php', {
                                method: 'POST',
                                body: uploadData,
                            });
                            const payload = await response.json();
                            if (!response.ok || payload.status !== 'success') {
                                throw new Error(payload.message || 'Upload thất bại.');
                            }
                            avatarInput.value = payload.url;
                            Swal.resetValidationMessage();
                        } catch (error) {
                            Swal.showValidationMessage(error.message);
                        } finally {
                            uploadButton.disabled = false;
                            uploadButton.textContent = originalText;
                            fileInput.value = '';
                        }
                    });
                }

                if (aiButton && aiIdeaInput && nameInput && langInput && editor) {
                    aiButton.addEventListener('click', async () => {
                        const artistName = nameInput.value.trim();
                        const langKey = langInput.value.trim();
                        const aiIdea = aiIdeaInput.value.trim();
                        if (!artistName || !langKey) {
                            Swal.showValidationMessage('Vui lòng nhập tên nghệ sĩ và chọn lang trước khi yêu cầu AI.');
                            return;
                        }
                        if (!aiIdea) {
                            Swal.showValidationMessage('Vui lòng nhập yêu cầu cho AI.');
                            return;
                        }

                        aiButton.disabled = true;
                        const originalHtml = aiButton.innerHTML;
                        aiButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Đang viết';

                        const aiData = new FormData();
                        aiData.append('action', 'ajax_ai_request_song_artist');
                        aiData.append('idea', aiIdea);
                        aiData.append('name', artistName);
                        aiData.append('lang_key', langKey);
                        aiData.append('description', editor.innerHTML.trim());

                        try {
                            const response = await fetch('index.php?section=music&tab=artists', {
                                method: 'POST',
                                body: aiData,
                            });
                            const payload = await response.json();
                            if (!response.ok || payload.status !== 'success') {
                                throw new Error(payload.message || 'Không tạo được mô tả nghệ sĩ theo yêu cầu AI.');
                            }
                            if (typeof payload.description === 'string' && payload.description !== '') {
                                editor.innerHTML = payload.description;
                                Swal.resetValidationMessage();
                            }
                        } catch (error) {
                            Swal.showValidationMessage(error.message);
                        } finally {
                            aiButton.disabled = false;
                            aiButton.innerHTML = originalHtml;
                        }
                    });
                }
            },
            preConfirm: () => {
                const name = (document.getElementById('quick_song_artist_name')?.value || '').trim();
                const langKey = (document.getElementById('quick_song_artist_lang')?.value || '').trim();
                const avatar = (document.getElementById('quick_song_artist_avatar')?.value || '').trim();
                const description = (document.getElementById('quick_song_artist_description_editor')?.innerHTML || '').trim();
                if (!name) {
                    Swal.showValidationMessage('Vui lòng nhập tên nghệ sĩ.');
                    return false;
                }
                if (!langKey) {
                    Swal.showValidationMessage('Vui lòng nhập lang.');
                    return false;
                }
                return {name, langKey, avatar, description};
            },
        });

        if (!result.isConfirmed) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'ajax_quick_add_song_artist');
        formData.append('name', result.value.name);
        formData.append('lang_key', result.value.langKey);
        formData.append('avatar', result.value.avatar);
        formData.append('description', result.value.description);

        try {
            quickAddSongArtistButton.disabled = true;
            const response = await fetch('index.php?section=music&tab=songs', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success' || !payload.artist) {
                throw new Error(payload.message || 'Không thêm được nghệ sĩ.');
            }

            const artistId = String(payload.artist.id);
            const artistName = String(payload.artist.name || result.value.name);
            let option = Array.from(songArtistSelect.options).find((item) => item.value === artistId);
            if (!option) {
                option = new Option(artistName, artistId, true, true);
                songArtistSelect.add(option);
            }
            option.selected = true;

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(songArtistSelect).trigger('change');
            } else {
                songArtistSelect.dispatchEvent(new Event('change', {bubbles: true}));
            }

            await Swal.fire({icon: 'success', title: 'Đã thêm nghệ sĩ', text: artistName, timer: 1200, showConfirmButton: false});
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'Không thêm được', text: error.message});
        } finally {
            quickAddSongArtistButton.disabled = false;
        }
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
bindSimpleEditor('artist_description_editor', 'artist_description', 'music_artist_description');
bindSimpleEditor('genre_description_editor', 'genre_description', 'music_genre_description');

const songArtistNameInput = document.getElementById('artist_name');
const songArtistLangInput = document.getElementById('artist_lang_key');
const songArtistDescriptionEditor = document.getElementById('artist_description_editor');
const songArtistDescriptionSource = document.getElementById('artist_description');
const aiSongArtistRequestButton = document.querySelector('.js-ai-song-artist-request');
if (songArtistNameInput && songArtistLangInput && songArtistDescriptionEditor && songArtistDescriptionSource && aiSongArtistRequestButton) {
    aiSongArtistRequestButton.addEventListener('click', async () => {
        const artistName = songArtistNameInput.value.trim();
        const langKey = songArtistLangInput.value.trim();
        if (!artistName || !langKey) {
            await Swal.fire({icon: 'warning', title: 'Thiếu thông tin', text: 'Vui lòng nhập tên nghệ sĩ và chọn lang trước khi yêu cầu AI.'});
            return;
        }

        const requestResult = await Swal.fire({
            title: 'Yêu cầu AI',
            input: 'textarea',
            inputLabel: 'Bạn muốn AI viết hoặc chỉnh mô tả nghệ sĩ này như thế nào?',
            inputPlaceholder: 'Ví dụ: Viết mô tả ngắn gọn, hấp dẫn, nhấn mạnh phong cách âm nhạc và thành tựu nổi bật...',
            inputAttributes: {
                'aria-label': 'Yêu cầu mô tả nghệ sĩ cho AI',
            },
            inputAutoTrim: true,
            showCancelButton: true,
            confirmButtonText: 'Tạo nội dung',
            cancelButtonText: 'Hủy',
            preConfirm: (value) => {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('Vui lòng nhập yêu cầu cho AI.');
                    return false;
                }
                return value.trim();
            },
        });

        if (!requestResult.isConfirmed) {
            return;
        }

        songArtistDescriptionSource.value = songArtistDescriptionEditor.innerHTML.trim();
        aiSongArtistRequestButton.disabled = true;
        const originalHtml = aiSongArtistRequestButton.innerHTML;
        aiSongArtistRequestButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang viết</span>';

        const formData = new FormData();
        formData.append('action', 'ajax_ai_request_song_artist');
        formData.append('idea', requestResult.value);
        formData.append('name', artistName);
        formData.append('lang_key', langKey);
        formData.append('description', songArtistDescriptionSource.value || '');

        try {
            const response = await fetch('index.php?section=music&tab=artists', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Không tạo được mô tả nghệ sĩ theo yêu cầu AI.');
            }

            if (songArtistDescriptionEditor && songArtistDescriptionSource && typeof payload.description === 'string' && payload.description !== '') {
                songArtistDescriptionEditor.innerHTML = payload.description;
                songArtistDescriptionSource.value = songArtistDescriptionEditor.innerHTML.trim();
            }

            await Swal.fire({icon: 'success', title: 'Đã tạo nội dung', text: 'AI đã chèn mô tả nghệ sĩ vào form.'});
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
        } finally {
            aiSongArtistRequestButton.disabled = false;
            aiSongArtistRequestButton.innerHTML = originalHtml;
            if (window.lucide) {
                lucide.createIcons();
            }
        }
    });
}

const songGenreIdInput = document.getElementById('genre_id');
const songGenreTitleInput = document.getElementById('genre_title');
const songGenreDescriptionEditor = document.getElementById('genre_description_editor');
const songGenreDescriptionSource = document.getElementById('genre_description');
const aiSongGenreRequestButton = document.querySelector('.js-ai-song-genre-request');
if (songGenreIdInput && songGenreTitleInput && songGenreDescriptionEditor && songGenreDescriptionSource && aiSongGenreRequestButton) {
    aiSongGenreRequestButton.addEventListener('click', async () => {
        const genreId = songGenreIdInput.value.trim();
        const genreTitle = songGenreTitleInput.value.trim();
        if (!genreId && !genreTitle) {
            await Swal.fire({icon: 'warning', title: 'Thiếu thông tin', text: 'Vui lòng nhập Genre ID hoặc Title trước khi yêu cầu AI.'});
            return;
        }

        const requestResult = await Swal.fire({
            title: 'Yêu cầu AI',
            input: 'textarea',
            inputLabel: 'Bạn muốn AI viết hoặc chỉnh mô tả thể loại này như thế nào?',
            inputPlaceholder: 'Ví dụ: Viết mô tả ngắn gọn, hấp dẫn, giải thích phong cách và cảm giác nghe của thể loại này...',
            inputAttributes: {
                'aria-label': 'Yêu cầu mô tả thể loại cho AI',
            },
            inputAutoTrim: true,
            showCancelButton: true,
            confirmButtonText: 'Tạo nội dung',
            cancelButtonText: 'Hủy',
            preConfirm: (value) => {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('Vui lòng nhập yêu cầu cho AI.');
                    return false;
                }
                return value.trim();
            },
        });

        if (!requestResult.isConfirmed) {
            return;
        }

        songGenreDescriptionSource.value = songGenreDescriptionEditor.innerHTML.trim();
        aiSongGenreRequestButton.disabled = true;
        const originalHtml = aiSongGenreRequestButton.innerHTML;
        aiSongGenreRequestButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang viết</span>';

        const formData = new FormData();
        formData.append('action', 'ajax_ai_request_song_genre');
        formData.append('idea', requestResult.value);
        formData.append('genre_id', genreId);
        formData.append('title', genreTitle);
        formData.append('lang_key', 'vi');
        formData.append('description', songGenreDescriptionSource.value || '');

        try {
            const response = await fetch('index.php?section=music&tab=genres', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Không tạo được mô tả thể loại theo yêu cầu AI.');
            }

            if (typeof payload.description === 'string' && payload.description !== '') {
                songGenreDescriptionEditor.innerHTML = payload.description;
                songGenreDescriptionSource.value = songGenreDescriptionEditor.innerHTML.trim();
            }

            await Swal.fire({icon: 'success', title: 'Đã tạo nội dung', text: 'AI đã chèn mô tả thể loại vào form.'});
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
        } finally {
            aiSongGenreRequestButton.disabled = false;
            aiSongGenreRequestButton.innerHTML = originalHtml;
            if (window.lucide) {
                lucide.createIcons();
            }
        }
    });
}

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
            const titleInput = document.getElementById('app_content_title');
            if (titleInput && typeof payload.title === 'string' && payload.title.trim() !== '') {
                titleInput.value = payload.title.trim();
            }
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

const categoryContentCategoryInput = document.getElementById('category_content_category_id');
const categoryContentLangInput = document.getElementById('category_content_key_lang');
const categoryContentTitleInput = document.getElementById('category_content_title');
const categoryContentDescriptionInput = document.getElementById('category_content_description');
const aiCategoryRequestButton = document.querySelector('.js-ai-category-request');
const aiCategoryGenerateButton = document.querySelector('.js-ai-category-generate');
if (categoryContentCategoryInput && categoryContentLangInput) {
    categoryContentCategoryInput.addEventListener('change', () => {
        const categoryId = categoryContentCategoryInput.value.trim();
        if (!categoryId) {
            return;
        }
        window.location.href = `index.php?section=apps&tab=categories&category_id=${encodeURIComponent(categoryId)}`;
    });
}
if (categoryContentCategoryInput && categoryContentLangInput && aiCategoryGenerateButton) {
    let categoryContentLookupTimer = null;
    let hasEnglishCategorySource = false;

    const fillCategoryAiFields = (payload) => {
        if (categoryContentTitleInput && typeof payload.title === 'string' && payload.title !== '') {
            categoryContentTitleInput.value = payload.title;
        }
        if (categoryContentDescriptionInput && typeof payload.description === 'string') {
            categoryContentDescriptionInput.value = payload.description;
        }
    };

    const checkEnglishCategorySource = () => {
        window.clearTimeout(categoryContentLookupTimer);
        categoryContentLookupTimer = window.setTimeout(async () => {
            const categoryId = categoryContentCategoryInput.value.trim();
            const keyLang = categoryContentLangInput.value.trim();
            hasEnglishCategorySource = false;
            aiCategoryGenerateButton.classList.add('d-none');
            if (!categoryId || !keyLang || keyLang === 'en') {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'ajax_find_app_category_content_source');
            formData.append('category_id', categoryId);
            formData.append('key_lang', keyLang);

            try {
                const response = await fetch('index.php?section=apps&tab=categories', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                hasEnglishCategorySource = Boolean(payload.source_en);
                if (response.ok && payload.status === 'success' && hasEnglishCategorySource) {
                    aiCategoryGenerateButton.classList.remove('d-none');
                }
            } catch (error) {
            }
        }, 250);
    };

    categoryContentCategoryInput.addEventListener('change', checkEnglishCategorySource);
    categoryContentLangInput.addEventListener('change', checkEnglishCategorySource);

    if (aiCategoryRequestButton) {
        aiCategoryRequestButton.addEventListener('click', async () => {
            const categoryId = categoryContentCategoryInput.value.trim();
            const keyLang = categoryContentLangInput.value.trim();
            if (!categoryId || !keyLang) {
                await Swal.fire({icon: 'warning', title: 'Thiếu thông tin', text: 'Vui lòng chọn category và key_lang trước khi yêu cầu AI.'});
                return;
            }

            const requestResult = await Swal.fire({
                title: 'Yêu cầu AI',
                input: 'textarea',
                inputLabel: 'Bạn muốn AI viết hoặc chỉnh chuyên mục này như thế nào?',
                inputPlaceholder: 'Ví dụ: Viết title ngắn gọn và description thân thiện cho chuyên mục app học tập...',
                inputAttributes: {
                    'aria-label': 'Yêu cầu nội dung category cho AI',
                },
                inputAutoTrim: true,
                showCancelButton: true,
                confirmButtonText: 'Tạo nội dung',
                cancelButtonText: 'Hủy',
                preConfirm: (value) => {
                    if (!value || !value.trim()) {
                        Swal.showValidationMessage('Vui lòng nhập yêu cầu cho AI.');
                        return false;
                    }
                    return value.trim();
                },
            });

            if (!requestResult.isConfirmed) {
                return;
            }

            aiCategoryRequestButton.disabled = true;
            const originalHtml = aiCategoryRequestButton.innerHTML;
            aiCategoryRequestButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang viết</span>';

            const formData = new FormData();
            formData.append('action', 'ajax_ai_request_app_category_content');
            formData.append('idea', requestResult.value);
            formData.append('category_id', categoryId);
            formData.append('key_lang', keyLang);
            formData.append('title', categoryContentTitleInput?.value.trim() || '');
            formData.append('description', categoryContentDescriptionInput?.value.trim() || '');

            try {
                const response = await fetch('index.php?section=apps&tab=categories', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 'success') {
                    throw new Error(payload.message || 'Không tạo được nội dung category theo yêu cầu AI.');
                }

                fillCategoryAiFields(payload);
                await Swal.fire({icon: 'success', title: 'Đã tạo nội dung', text: 'AI đã chèn Title và Description vào form.'});
            } catch (error) {
                await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
            } finally {
                aiCategoryRequestButton.disabled = false;
                aiCategoryRequestButton.innerHTML = originalHtml;
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        });
    }

    aiCategoryGenerateButton.addEventListener('click', async () => {
        const categoryId = categoryContentCategoryInput.value.trim();
        const keyLang = categoryContentLangInput.value.trim();
        if (!categoryId || !keyLang || keyLang === 'en' || !hasEnglishCategorySource) {
            return;
        }

        aiCategoryGenerateButton.disabled = true;
        const originalHtml = aiCategoryGenerateButton.innerHTML;
        aiCategoryGenerateButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang tạo</span>';

        const formData = new FormData();
        formData.append('action', 'ajax_ai_translate_app_category_content');
        formData.append('category_id', categoryId);
        formData.append('key_lang', keyLang);

        try {
            const response = await fetch('index.php?section=apps&tab=categories', {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Không tạo được nội dung category bằng AI.');
            }

            fillCategoryAiFields(payload);
        } catch (error) {
            await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
        } finally {
            aiCategoryGenerateButton.disabled = false;
            aiCategoryGenerateButton.innerHTML = originalHtml;
            if (window.lucide) {
                lucide.createIcons();
            }
        }
    });

    checkEnglishCategorySource();
}

const pageSlugInput = document.getElementById('page_slug');
const pageLangInput = document.getElementById('page_lang');
const pageIdInput = pageSlugInput ? pageSlugInput.closest('form')?.querySelector('input[name="id"]') : null;
const aiPageButton = document.querySelector('.js-ai-page-generate');
const aiPageRequestButton = document.querySelector('.js-ai-page-request');
if (pageSlugInput && pageLangInput && pageIdInput) {
    let pageLookupTimer = null;
    let lastPageLookup = '';
    let hasEnglishPageSource = false;

    const fillPageAiFields = (payload) => {
        const titleField = document.getElementById('page_title');
        if (titleField && typeof payload.title === 'string' && payload.title !== '') {
            titleField.value = payload.title;
        }

        const editor = document.getElementById('page_content_editor');
        const source = document.getElementById('page_content_html');
        if (editor && source && typeof payload.content_html === 'string' && payload.content_html !== '') {
            editor.innerHTML = payload.content_html;
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
    };

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

    if (aiPageRequestButton) {
        aiPageRequestButton.addEventListener('click', async () => {
            const slug = pageSlugInput.value.trim();
            const lang = pageLangInput.value.trim();
            if (!slug || !lang) {
                await Swal.fire({icon: 'warning', title: 'Thiếu thông tin', text: 'Vui lòng chọn slug và lang trước khi yêu cầu AI.'});
                return;
            }

            const requestResult = await Swal.fire({
                title: 'Yêu cầu AI',
                input: 'textarea',
                inputLabel: 'Bạn muốn AI viết nội dung gì?',
                inputPlaceholder: 'Ví dụ: Viết trang giới thiệu Carrot Store, giọng thân thiện, nhấn mạnh tải app miễn phí và tối ưu SEO...',
                inputAttributes: {
                    'aria-label': 'Yêu cầu nội dung cho AI',
                },
                inputAutoTrim: true,
                showCancelButton: true,
                confirmButtonText: 'Tạo nội dung',
                cancelButtonText: 'Hủy',
                preConfirm: (value) => {
                    if (!value || !value.trim()) {
                        Swal.showValidationMessage('Vui lòng nhập yêu cầu cho AI.');
                        return false;
                    }
                    return value.trim();
                },
            });

            if (!requestResult.isConfirmed) {
                return;
            }

            aiPageRequestButton.disabled = true;
            const originalHtml = aiPageRequestButton.innerHTML;
            aiPageRequestButton.innerHTML = '<span class="d-inline-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Đang viết</span>';

            const editor = document.getElementById('page_content_editor');
            const source = document.getElementById('page_content_html');
            if (editor && source) {
                source.value = editor.innerHTML.trim();
            }

            const formData = new FormData();
            formData.append('action', 'ajax_ai_request_page');
            formData.append('idea', requestResult.value);
            formData.append('slug', slug);
            formData.append('lang', lang);
            formData.append('title', document.getElementById('page_title')?.value.trim() || '');
            formData.append('content_html', source?.value || '');

            try {
                const response = await fetch('index.php?section=pages', {
                    method: 'POST',
                    body: formData,
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 'success') {
                    throw new Error(payload.message || 'Không tạo được nội dung theo yêu cầu AI.');
                }

                fillPageAiFields(payload);
                await Swal.fire({icon: 'success', title: 'Đã tạo nội dung', text: 'AI đã chèn Title, HTML content và SEO vào form.'});
            } catch (error) {
                await Swal.fire({icon: 'warning', title: 'AI lỗi', text: error.message});
            } finally {
                aiPageRequestButton.disabled = false;
                aiPageRequestButton.innerHTML = originalHtml;
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        });
    }

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

                fillPageAiFields(payload);
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

document.querySelectorAll('.dashboard-nav').forEach((nav) => {
    let isPointerDown = false;
    let startX = 0;
    let startScrollLeft = 0;
    let moved = false;

    const canScroll = () => nav.scrollWidth > nav.clientWidth + 1;

    nav.addEventListener('wheel', (event) => {
        if (!canScroll()) {
            return;
        }
        const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
        if (delta === 0) {
            return;
        }
        event.preventDefault();
        nav.scrollLeft += delta;
    }, {passive: false});

    nav.addEventListener('pointerdown', (event) => {
        if (!canScroll() || event.button > 0) {
            return;
        }
        isPointerDown = true;
        moved = false;
        startX = event.clientX;
        startScrollLeft = nav.scrollLeft;
        nav.classList.add('is-dragging');
        nav.setPointerCapture(event.pointerId);
    });

    nav.addEventListener('pointermove', (event) => {
        if (!isPointerDown) {
            return;
        }
        const deltaX = event.clientX - startX;
        if (Math.abs(deltaX) > 4) {
            moved = true;
        }
        nav.scrollLeft = startScrollLeft - deltaX;
    });

    const stopDrag = (event) => {
        if (!isPointerDown) {
            return;
        }
        isPointerDown = false;
        nav.classList.remove('is-dragging');
        if (nav.hasPointerCapture(event.pointerId)) {
            nav.releasePointerCapture(event.pointerId);
        }
    };

    nav.addEventListener('pointerup', stopDrag);
    nav.addEventListener('pointercancel', stopDrag);
    nav.addEventListener('click', (event) => {
        if (!moved) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        moved = false;
    }, true);
});

if (window.lucide) {
    lucide.createIcons();
}
</script>
</body>
</html>
