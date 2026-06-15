<?php
require __DIR__ . '/../CarrotCoc/config/database.php';
require __DIR__ . '/../CarrotCoc/includes/coc_helpers.php';

$message = '';
$error = '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;

function admin_nas_upload_endpoint(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/CarrotNas/index.php?api=upload_image';
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

if (!$pdo instanceof PDO) {
    $error = 'Không thể kết nối database: ' . ($db_error ?? 'unknown error');
} else {
    try {
        $pdo->exec(file_get_contents(__DIR__ . '/../CarrotCoc/sql/schema.sql'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'delete') {
                $stmt = $pdo->prepare('DELETE FROM coc WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
                $message = 'Đã xóa acc.';
            }

            if ($action === 'save') {
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

                $uploadedAvatar = admin_upload_image_to_nas($_FILES['avatar_file'] ?? [], 'avatar');
                if ($uploadedAvatar !== '') {
                    $avatar = $uploadedAvatar;
                }

                foreach (admin_uploaded_files('photos_files') as $photoFile) {
                    $uploadedPhoto = admin_upload_image_to_nas($photoFile, 'photos');
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
        }

        if ($editId > 0) {
            $editing = coc_fetch_account($pdo, $editId);
        }

        $accounts = $pdo->query('SELECT * FROM coc ORDER BY id DESC')->fetchAll();
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $accounts = [];
    }
}

$photoText = $editing ? implode("\n", coc_decode_photos($editing['photos'])) : '';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrot Admin - Coc</title>
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
                <a class="list-group-item list-group-item-action active" href="index.php">Coc</a>
                <a class="list-group-item list-group-item-action" href="/CarrotCoc/index.php">Xem shop</a>
            </div>
        </aside>

        <main class="col p-3 p-lg-4">
            <div class="admin-shell p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <p class="muted-text text-uppercase fw-bold mb-1">Quản lý shop</p>
                        <h1 class="h3 mb-0">Acc Clash of Clans</h1>
                    </div>
                    <?php if ($editing): ?>
                        <a class="btn btn-outline-light" href="index.php">Thêm mới</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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

                        <button class="btn btn-coc w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm acc' ?></button>
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
                                            <a class="btn btn-sm btn-outline-light" href="index.php?edit=<?= (int) $account['id'] ?>">Cập nhật</a>
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
        </main>
    </div>
</div>
</body>
</html>
