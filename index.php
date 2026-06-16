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
        <div class="d-flex align-items-center gap-3 mb-4">
            <span class="brand-mark">CA</span>
            <div>
                <h1 class="h4 mb-0">Carrot Admin</h1>
                <div class="muted-text small">Đăng nhập</div>
            </div>
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
$section = ($_GET['section'] ?? 'coc') === 'apps' ? 'apps' : 'coc';
$editKey = trim($_GET['edit'] ?? '');
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$cocTab = ($_GET['tab'] ?? 'accounts') === 'orders' ? 'orders' : 'accounts';
$editing = null;
$accounts = [];
$apps = [];
$orders = [];
$accountSort = 'id';
$accountDir = 'DESC';
$orderSort = 'created_at';
$orderDir = 'DESC';
$appSort = 'priority';
$appDir = 'DESC';

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
        if (!in_array($typeMedia, ['carrot_app', 'coc_images'], true)) {
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

function admin_ensure_app_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app (
          id varchar(255) NOT NULL,
          decription longtext DEFAULT NULL,
          github longtext DEFAULT NULL,
          microsoft_store longtext DEFAULT NULL,
          icon longtext DEFAULT NULL,
          itch longtext DEFAULT NULL,
          exe_file longtext DEFAULT NULL,
          ipa_file longtext DEFAULT NULL,
          deb_file longtext DEFAULT NULL,
          amazon_app_store longtext DEFAULT NULL,
          huawei_store longtext DEFAULT NULL,
          youtube_link longtext DEFAULT NULL,
          google_play longtext DEFAULT NULL,
          dmg_file longtext DEFAULT NULL,
          uptodown longtext DEFAULT NULL,
          simmer longtext DEFAULT NULL,
          type longtext DEFAULT NULL,
          apk_file longtext DEFAULT NULL,
          status longtext DEFAULT NULL,
          sync_status int(11) DEFAULT 0,
          priority int(11) DEFAULT 0,
          category longtext DEFAULT NULL,
          created_at datetime DEFAULT current_timestamp(),
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajax_upload') {
    admin_ajax_upload();
}

if (!$pdo instanceof PDO) {
    $error = 'Không thể kết nối database: ' . ($db_error ?? 'unknown error');
} else {
    try {
        $pdo->exec(file_get_contents(__DIR__ . '/../CarrotCoc/sql/schema.sql'));
        admin_ensure_app_table($pdo);

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
                $data = trim($_POST['data'] ?? '');
                $avatar = trim($_POST['avatar'] ?? '');
                $photoUrls = coc_decode_photos(coc_photos_to_json($_POST['photos'] ?? ''));
                $username = trim($_POST['username'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $price = (float) ($_POST['price'] ?? 0);

                if ($name === '' || $data === '' || $username === '' || $password === '') {
                    throw new RuntimeException('Vui lòng nhập đủ name, data, username và password.');
                }

                if (json_decode($data, true) === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Trường data phải là JSON hợp lệ.');
                }

                $photos = coc_photos_to_json(implode("\n", array_unique($photoUrls)));

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE coc SET name = ?, data = ?, avatar = ?, photos = ?, username = ?, password = ?, price = ? WHERE id = ?');
                    $stmt->execute([$name, $data, $avatar, $photos, $username, $password, $price, $id]);
                    $message = 'Đã cập nhật acc.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO coc (name, data, avatar, photos, username, password, price) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $data, $avatar, $photos, $username, $password, $price]);
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
        }

        if ($section === 'coc' && $editId > 0) {
            $editing = coc_fetch_account($pdo, $editId);
        }

        if ($section === 'apps' && $editKey !== '') {
            $editing = admin_fetch_app($pdo, $editKey);
        }

        $accountSortColumns = [
            'id' => 'id',
            'name' => 'name',
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
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $accounts = [];
        $apps = [];
        $orders = [];
    }
}

$photoText = ($section === 'coc' && $editing) ? implode("\n", coc_decode_photos($editing['photos'])) : '';
$pageTitle = $section === 'apps' ? 'App' : 'Coc';
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
</head>
<body>
<div class="container-fluid">
    <div class="row min-vh-100">
        <aside class="col-lg-2 glass-panel m-3 p-3 align-self-start">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="brand-mark">CA</span>
                <strong>Carrot Admin</strong>
            </div>
            <div class="list-group">
                <a class="list-group-item list-group-item-action <?= $section === 'apps' ? 'active' : '' ?>" href="index.php?section=apps">App</a>
                <a class="list-group-item list-group-item-action <?= $section === 'coc' ? 'active' : '' ?>" href="index.php">Coc</a>
            </div>
        </aside>

        <main class="col p-3 p-lg-4">
            <div class="admin-shell p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <p class="muted-text text-uppercase fw-bold mb-1">Quản lý <?= $section === 'apps' ? 'ứng dụng' : 'shop' ?></p>
                        <h1 class="h3 mb-0"><?= $section === 'apps' ? 'App Carrot Home' : 'Acc Clash of Clans' ?></h1>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($section === 'coc'): ?>
                            <a class="btn btn-secondary fw-bold" href="/CarrotCoc/index.php">Xem shop</a>
                        <?php endif; ?>
                        <?php if ($editing): ?>
                            <a class="btn btn-success fw-bold" href="index.php<?= $section === 'apps' ? '?section=apps' : '' ?>">Thêm mới</a>
                        <?php endif; ?>
                        <a class="btn btn-danger fw-bold" href="index.php?logout=1" title="Đăng xuất">
                            <span class="d-inline-flex align-items-center gap-2"><i data-lucide="log-out" style="width:16px;height:16px"></i><?= htmlspecialchars($_SESSION['admin_user']) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($section === 'coc'): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $cocTab === 'accounts' ? 'active' : '' ?>" href="index.php?tab=accounts">Các Tài khoản</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $cocTab === 'orders' ? 'active' : '' ?>" href="index.php?tab=orders">Đơn Đặt hàng</a>
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
                                <label class="form-label" for="avatar">Avatar URL</label>
                                <div class="input-group">
                                    <input class="form-control" id="avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                    <button class="btn btn-secondary js-upload" type="button" data-target="avatar" data-type-media="coc_images" data-mode="replace" data-accept="image/*">Upload</button>
                                </div>
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
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Acc', $accountSort, $accountDir) ?></th>
                                    <th>Town Hall</th>
                                    <th><?= admin_sort_link('price', 'Giá', $accountSort, $accountDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($accounts as $account):
                                    $th = coc_townhall_level(coc_decode_json($account['data']));
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
                                            <a class="btn btn-sm btn-warning" href="index.php?edit=<?= (int) $account['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                            </a>
                                            <form method="post" onsubmit="return confirm('Xóa acc này?')">
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
                <div class="table-responsive">
                    <table class="table align-middle">
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
                        <div class="table-responsive">
                            <table class="table align-middle">
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
                                            <form method="post" onsubmit="return confirm('Xóa app này?')">
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
        </main>
    </div>
</div>
<script>
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

if (window.lucide) {
    lucide.createIcons();
}
</script>
</body>
</html>
