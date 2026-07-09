<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="backup-action-card">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Tạo bản sao lưu</h2>
                    <div class="muted-text small">Đóng gói schema và dữ liệu database carrot_home theo từng chunk AJAX để giảm timeout.</div>
                </div>
                <span class="dashboard-card-icon"><i data-lucide="database-backup"></i></span>
            </div>
            <button class="btn btn-success fw-bold js-backup-start" type="button">
                <span class="d-inline-flex align-items-center gap-2"><i data-lucide="play" style="width:16px;height:16px"></i>Bắt đầu sao lưu</span>
            </button>
            <div class="mt-3 d-none js-backup-progress-wrap">
                <div class="d-flex justify-content-between small fw-bold mb-2">
                    <span class="js-backup-status">Đang chuẩn bị...</span>
                    <span class="js-backup-percent">0%</span>
                </div>
                <div class="backup-progress"><div class="backup-progress-bar js-backup-progress-bar"></div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="backup-action-card">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Khôi phục dữ liệu</h2>
                    <div class="muted-text small">Chọn file trong danh sách bên dưới. Hệ thống sẽ chạy từng nhóm statement để tránh request quá lâu.</div>
                </div>
                <span class="dashboard-card-icon"><i data-lucide="rotate-ccw"></i></span>
            </div>
            <div class="alert alert-warning mb-0 small">Khôi phục sẽ drop và tạo lại các bảng có trong file backup. Nên tạo một bản backup mới trước khi restore.</div>
        </div>
    </div>
</div>

<div class="glass-panel p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">Các bản đã sao lưu</h2>
            <div class="muted-text small">File được lưu tại <code>CarrotAdmin/storage/backups</code>.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-danger fw-bold js-backup-cleanup" type="button">Dọn file lỗi</button>
            <button class="btn btn-sm btn-secondary fw-bold js-backup-refresh" type="button">Làm mới</button>
        </div>
    </div>
    <div class="table-responsive-sm">
        <table class="table table-striped table-hover table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>File</th>
                <th>Dung lượng</th>
                <th>Thời gian</th>
                <th class="text-end">Bảng</th>
                <th class="text-end">Dòng</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody class="js-backup-list">
            <?php foreach ($backupItems as $item): ?>
                <tr>
                    <td><div class="backup-file-name"><?= htmlspecialchars($item['file']) ?></div><div class="small muted-text"><?= htmlspecialchars($item['status']) ?></div></td>
                    <td><?= htmlspecialchars($item['size_label']) ?></td>
                    <td><?= htmlspecialchars($item['created_at']) ?></td>
                    <td class="text-end"><?= number_format((int) $item['tables']) ?></td>
                    <td class="text-end"><?= number_format((int) $item['rows']) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-secondary fw-bold <?= !empty($item['complete']) ? '' : 'disabled' ?>" href="<?= !empty($item['complete']) ? 'index.php?action=backup_download&amp;file=' . urlencode($item['file']) : '#' ?>">Tải</a>
                            <button class="btn btn-outline-danger fw-bold js-backup-restore" type="button" data-file="<?= htmlspecialchars($item['file']) ?>" <?= !empty($item['complete']) ? '' : 'disabled' ?>>Khôi phục</button>
                            <button class="btn btn-outline-danger fw-bold js-backup-delete" type="button" data-file="<?= htmlspecialchars($item['file']) ?>" title="Xóa backup" aria-label="Xóa backup">
                                <i data-lucide="trash-2" style="width:15px;height:15px"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$backupItems): ?>
                <tr><td colspan="6" class="text-center muted-text py-4">Chưa có bản sao lưu.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
