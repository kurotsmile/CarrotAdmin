<?php
require __DIR__ . '/../CarrotCoc/config/database.php';
require __DIR__ . '/../CarrotCoc/includes/coc_helpers.php';

$message = '';
$error = '';
$section = ($_GET['section'] ?? 'coc') === 'apps' ? 'apps' : 'coc';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;
$accounts = [];
$apps = [];

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

function admin_uploaded_files(string $field): array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return [];
    }

    $files = [];
    foreach ($_FILES[$field]['name'] as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $_FILES[$field]['type'][$index] ?? '',
            'tmp_name' => $_FILES[$field]['tmp_name'][$index] ?? '',
            'error' => $_FILES[$field]['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES[$field]['size'][$index] ?? 0,
        ];
    }

    return $files;
}

function admin_empty_json_array(): string
{
    return '[]';
}

function admin_json_text($value, string $fallback = '[]'): string
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
    } else {
        $decoded = $value;
    }

    if (json_last_error() !== JSON_ERROR_NONE && is_string($value)) {
        return (string) $value;
    }

    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function admin_require_json(string $json, string $label): string
{
    $json = trim($json);
    if ($json === '') {
        $json = '[]';
    }

    json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException($label . ' phải là JSON hợp lệ.');
    }

    return $json;
}

function admin_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'app-' . time();
}

function admin_fetch_app(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM apps WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function admin_ensure_apps_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS apps (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          app_id VARCHAR(160) NOT NULL,
          slug VARCHAR(180) NOT NULL,
          name_en VARCHAR(255) NOT NULL,
          type VARCHAR(80) DEFAULT 'app',
          status ENUM('publish','draft','trash') DEFAULT 'publish',
          priority INT DEFAULT 0,
          date_create DATETIME DEFAULT CURRENT_TIMESTAMP,
          icon TEXT NULL,
          images JSON NULL,
          store_links JSON NULL,
          download_links JSON NULL,
          video_links JSON NULL,
          category JSON NULL,
          icons JSON NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_apps_app_id (app_id),
          UNIQUE KEY uq_apps_slug (slug),
          KEY idx_apps_status (status),
          KEY idx_apps_type (type),
          KEY idx_apps_priority (priority),
          KEY idx_apps_date_create (date_create),
          FULLTEXT KEY ft_apps_name_appid (name_en, app_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

if (!$pdo instanceof PDO) {
    $error = 'Không thể kết nối database: ' . ($db_error ?? 'unknown error');
} else {
    try {
        $pdo->exec(file_get_contents(__DIR__ . '/../CarrotCoc/sql/schema.sql'));
        admin_ensure_apps_table($pdo);

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

                $uploadedAvatar = admin_upload_image_to_nas($_FILES['avatar_file'] ?? [], 'coc_images');
                if ($uploadedAvatar !== '') {
                    $avatar = $uploadedAvatar;
                }

                foreach (admin_uploaded_files('photos_files') as $photoFile) {
                    $uploadedPhoto = admin_upload_image_to_nas($photoFile, 'coc_images');
                    if ($uploadedPhoto !== '') {
                        $photoUrls[] = $uploadedPhoto;
                    }
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
                $stmt = $pdo->prepare('DELETE FROM apps WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa app.';
            }

            if ($section === 'apps' && $action === 'save_app') {
                $id = (int) ($_POST['id'] ?? 0);
                $appId = trim($_POST['app_id'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $nameEn = trim($_POST['name_en'] ?? '');
                $type = trim($_POST['type'] ?? 'app');
                $status = $_POST['status'] ?? 'publish';
                $priority = (int) ($_POST['priority'] ?? 0);
                $dateCreate = trim($_POST['date_create'] ?? '');
                $icon = trim($_POST['icon'] ?? '');

                if ($appId === '' || $nameEn === '') {
                    throw new RuntimeException('Vui lòng nhập đủ App ID và Name.');
                }

                if (!in_array($status, ['publish', 'draft', 'trash'], true)) {
                    throw new RuntimeException('Status không hợp lệ.');
                }

                if ($slug === '') {
                    $slug = admin_slugify($nameEn);
                }

                if ($dateCreate === '') {
                    $dateCreate = date('Y-m-d H:i:s');
                }

                $images = admin_require_json($_POST['images'] ?? '', 'Images');
                $storeLinks = admin_require_json($_POST['store_links'] ?? '', 'Store links');
                $downloadLinks = admin_require_json($_POST['download_links'] ?? '', 'Download links');
                $videoLinks = admin_require_json($_POST['video_links'] ?? '', 'Video links');
                $category = admin_require_json($_POST['category'] ?? '', 'Category');
                $icons = admin_require_json($_POST['icons'] ?? '', 'Icons');

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE apps SET app_id = ?, slug = ?, name_en = ?, type = ?, status = ?, priority = ?, date_create = ?, icon = ?, images = ?, store_links = ?, download_links = ?, video_links = ?, category = ?, icons = ? WHERE id = ?');
                    $stmt->execute([$appId, $slug, $nameEn, $type, $status, $priority, $dateCreate, $icon, $images, $storeLinks, $downloadLinks, $videoLinks, $category, $icons, $id]);
                    $message = 'Đã cập nhật app.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO apps (app_id, slug, name_en, type, status, priority, date_create, icon, images, store_links, download_links, video_links, category, icons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$appId, $slug, $nameEn, $type, $status, $priority, $dateCreate, $icon, $images, $storeLinks, $downloadLinks, $videoLinks, $category, $icons]);
                    $message = 'Đã thêm app mới.';
                }
            }
        }

        if ($section === 'coc' && $editId > 0) {
            $editing = coc_fetch_account($pdo, $editId);
        }

        if ($section === 'apps' && $editId > 0) {
            $editing = admin_fetch_app($pdo, $editId);
        }

        $accounts = $section === 'coc' ? $pdo->query('SELECT * FROM coc ORDER BY id DESC')->fetchAll() : [];
        $apps = $section === 'apps' ? $pdo->query('SELECT * FROM apps ORDER BY priority DESC, date_create DESC, id DESC')->fetchAll() : [];
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $accounts = [];
        $apps = [];
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
                <a class="list-group-item list-group-item-action <?= $section === 'coc' ? 'active' : '' ?>" href="index.php">Coc</a>
                <a class="list-group-item list-group-item-action <?= $section === 'apps' ? 'active' : '' ?>" href="index.php?section=apps">App</a>
                <a class="list-group-item list-group-item-action" href="/CarrotCoc/index.php">Xem shop</a>
            </div>
        </aside>

        <main class="col p-3 p-lg-4">
            <div class="admin-shell p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <p class="muted-text text-uppercase fw-bold mb-1">Quản lý <?= $section === 'apps' ? 'ứng dụng' : 'shop' ?></p>
                        <h1 class="h3 mb-0"><?= $section === 'apps' ? 'App Carrot Home' : 'Acc Clash of Clans' ?></h1>
                    </div>
                    <?php if ($editing): ?>
                        <a class="btn btn-outline-light" href="index.php<?= $section === 'apps' ? '?section=apps' : '' ?>">Thêm mới</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($section === 'coc'): ?>
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
                                <input class="form-control" id="avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                <input class="form-control mt-2" id="avatar_file" name="avatar_file" type="file" accept="image/*">
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
                            <textarea class="form-control" id="photos" name="photos" rows="4"><?= htmlspecialchars($photoText) ?></textarea>
                            <input class="form-control mt-2" id="photos_files" name="photos_files[]" type="file" accept="image/*" multiple>
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
                                    <th>ID</th>
                                    <th>Acc</th>
                                    <th>Town Hall</th>
                                    <th>Giá</th>
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
                                            <a class="btn btn-sm btn-warning fw-bold" href="index.php?edit=<?= (int) $account['id'] ?>">Cập nhật</a>
                                            <form class="d-inline" method="post" onsubmit="return confirm('Xóa acc này?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                            </form>
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

            <?php if ($section === 'apps'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_app">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật app' : 'Thêm app mới' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="app_id">App ID</label>
                                <input class="form-control" id="app_id" name="app_id" value="<?= htmlspecialchars($editing['app_id'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="slug">Slug</label>
                                <input class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($editing['slug'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="name_en">Name</label>
                            <input class="form-control" id="name_en" name="name_en" value="<?= htmlspecialchars($editing['name_en'] ?? '') ?>" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="type">Type</label>
                                <input class="form-control" id="type" name="type" value="<?= htmlspecialchars($editing['type'] ?? 'app') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach (['publish', 'draft', 'trash'] as $status): ?>
                                        <option value="<?= $status ?>" <?= ($editing['status'] ?? 'publish') === $status ? 'selected' : '' ?>><?= $status ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="priority">Priority</label>
                                <input class="form-control" id="priority" name="priority" type="number" value="<?= htmlspecialchars((string) ($editing['priority'] ?? 0)) ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="date_create">Date create</label>
                            <input class="form-control" id="date_create" name="date_create" value="<?= htmlspecialchars($editing['date_create'] ?? date('Y-m-d H:i:s')) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="icon">Icon URL</label>
                            <input class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($editing['icon'] ?? '') ?>">
                        </div>

                        <?php
                        $jsonFields = [
                            'images' => 'Images',
                            'store_links' => 'Store links',
                            'download_links' => 'Download links',
                            'video_links' => 'Video links',
                            'category' => 'Category',
                            'icons' => 'Icons',
                        ];
                        foreach ($jsonFields as $field => $label):
                        ?>
                            <div class="mb-3">
                                <label class="form-label" for="<?= $field ?>"><?= $label ?> JSON</label>
                                <textarea class="form-control font-monospace small" id="<?= $field ?>" name="<?= $field ?>" rows="5"><?= htmlspecialchars(admin_json_text($editing[$field] ?? admin_empty_json_array())) ?></textarea>
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
                                    <th>ID</th>
                                    <th>App</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($apps as $app): ?>
                                    <tr>
                                        <td><?= (int) $app['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($app['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($app['icon']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($app['name_en']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars($app['app_id']) ?> / <?= htmlspecialchars($app['slug']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($app['type']) ?></td>
                                        <td><?= htmlspecialchars($app['status']) ?></td>
                                        <td><?= (int) $app['priority'] ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-warning fw-bold" href="index.php?section=apps&edit=<?= (int) $app['id'] ?>">Cập nhật</a>
                                            <form class="d-inline" method="post" onsubmit="return confirm('Xóa app này?')">
                                                <input type="hidden" name="action" value="delete_app">
                                                <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                            </form>
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
</body>
</html>
