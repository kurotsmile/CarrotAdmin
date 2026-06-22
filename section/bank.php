<?php

function admin_section_prepare(): void
{
    global $pdo, $message, $editId, $editing, $bankSort, $bankDir, $banks;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete_bank') {
            $stmt = $pdo->prepare('DELETE FROM bank WHERE id = ?');
            $stmt->execute([(int) ($_POST['id'] ?? 0)]);
            $message = 'Đã xóa bank.';
        }
        if ($action === 'save_bank') {
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
    }

    if ($editId > 0) {
        $editing = admin_fetch_bank($pdo, $editId);
    }

    $bankSortColumns = ['id' => 'id', 'name' => 'name', 'account_name' => 'account_name', 'account_number' => 'account_number'];
    [$bankSort, $bankDir] = admin_sort_state($bankSortColumns, 'id', 'DESC');
    $banks = $pdo->query('SELECT * FROM bank ORDER BY ' . admin_order_by($bankSortColumns, $bankSort, $bankDir))->fetchAll();
}

function admin_section_render(): void
{
    global $editing, $banks, $bankSort, $bankDir;
    ?>
    <div class="row g-4">
        <div class="col-xl-5">
            <form class="glass-panel p-4" method="post">
                <input type="hidden" name="action" value="save_bank">
                <input type="hidden" name="original_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật bank' : 'Thêm bank mới' ?></h2>
                <div class="mb-3"><label class="form-label" for="bank_name">Name</label><input class="form-control" id="bank_name" name="name" maxlength="50" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required></div>
                <?php foreach (['bank_avatar' => ['avatar', 'Avatar URL'], 'bank_banner' => ['banner', 'Banner URL'], 'bank_qr' => ['qr', 'QR URL']] as $id => $meta): [$field, $label] = $meta; ?>
                    <div class="mb-3"><label class="form-label" for="<?= $id ?>"><?= $label ?></label><div class="input-group"><input class="form-control" id="<?= $id ?>" name="<?= $field ?>" value="<?= htmlspecialchars($editing[$field] ?? '') ?>" required><button class="btn btn-secondary js-upload" type="button" data-target="<?= $id ?>" data-type-media="bank" data-mode="replace" data-accept="image/*">Upload</button></div></div>
                <?php endforeach; ?>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="account_name">Account name</label><input class="form-control" id="account_name" name="account_name" maxlength="20" value="<?= htmlspecialchars($editing['account_name'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="account_number">Account number</label><input class="form-control" id="account_number" name="account_number" maxlength="50" value="<?= htmlspecialchars($editing['account_number'] ?? '') ?>" required></div>
                </div>
                <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm bank' ?></button>
            </form>
        </div>
        <div class="col-xl-7"><div class="glass-panel p-4"><h2 class="h5 mb-3">Danh sách bank</h2><div class="table-responsive-sm"><table class="table table-striped table-hover table-sm align-middle">
            <thead><tr><th><?= admin_sort_link('id', 'ID', $bankSort, $bankDir) ?></th><th><?= admin_sort_link('name', 'Bank', $bankSort, $bankDir) ?></th><th><?= admin_sort_link('account_name', 'Account name', $bankSort, $bankDir) ?></th><th><?= admin_sort_link('account_number', 'Account number', $bankSort, $bankDir) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($banks as $bank): ?>
                <tr><td><?= (int) $bank['id'] ?></td><td><div class="d-flex align-items-center gap-3"><img src="<?= htmlspecialchars($bank['avatar']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover"><div><strong><?= htmlspecialchars($bank['name']) ?></strong><div class="muted-text small"><?= htmlspecialchars(admin_excerpt($bank['banner'] ?? '', 45)) ?></div></div></div></td><td><?= htmlspecialchars($bank['account_name']) ?></td><td class="font-monospace small"><?= htmlspecialchars($bank['account_number']) ?></td><td class="text-end"><div class="d-inline-flex gap-2 flex-nowrap"><a class="btn btn-sm btn-warning" href="index.php?section=bank&edit=<?= (int) $bank['id'] ?>"><i data-lucide="pencil" style="width:16px;height:16px"></i></a><form class="js-delete" method="post" data-confirm="Xóa bank này?"><input type="hidden" name="action" value="delete_bank"><input type="hidden" name="id" value="<?= (int) $bank['id'] ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button></form></div></td></tr>
            <?php endforeach; ?>
            <?php if (!$banks): ?><tr><td colspan="5" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr><?php endif; ?>
            </tbody>
        </table></div></div></div>
    </div>
    <?php
}
