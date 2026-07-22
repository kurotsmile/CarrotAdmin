<?php
$orderSourceLabels = [
    'app' => ['label' => 'App', 'icon' => 'boxes', 'url' => 'index.php?section=apps&tab=orders'],
    'music' => ['label' => 'Âm nhạc', 'icon' => 'music-2', 'url' => 'index.php?section=music&tab=orders'],
    'ebook' => ['label' => 'Sách', 'icon' => 'book-open', 'url' => 'index.php?section=ebook&tab=orders'],
    'cloud' => ['label' => 'Cloud', 'icon' => 'cloud', 'url' => 'index.php?section=cloud&tab=subscriptions'],
    'coc' => ['label' => 'COC', 'icon' => 'shield', 'url' => 'index.php?section=coc&tab=orders'],
];

$paidPercent = $systemOrderStats['total'] > 0
    ? round(($systemOrderStats['paid'] / max(1, $systemOrderStats['total'])) * 100, 1)
    : 0;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Quản lý tổng đơn hàng</h2>
        <div class="muted-text">Gộp đơn App, Âm nhạc, Sách, Cloud và COC trong một nơi.</div>
    </div>
</div>

<div class="dashboard-grid mb-4">
    <div class="dashboard-card">
        <div class="dashboard-card-line">
            <span class="dashboard-card-icon"><i data-lucide="receipt-text"></i></span>
            <span class="dashboard-card-value"><?= number_format((int) $systemOrderStats['total']) ?></span>
            <span class="dashboard-card-label">Tổng đơn</span>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="dashboard-card-line">
            <span class="dashboard-card-icon"><i data-lucide="badge-check"></i></span>
            <span class="dashboard-card-value"><?= number_format((int) $systemOrderStats['paid']) ?></span>
            <span class="dashboard-card-label">Đã thanh toán</span>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="dashboard-card-line">
            <span class="dashboard-card-icon"><i data-lucide="clock-3"></i></span>
            <span class="dashboard-card-value"><?= number_format((int) $systemOrderStats['pending']) ?></span>
            <span class="dashboard-card-label">Chưa hoàn tất</span>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="dashboard-card-line">
            <span class="dashboard-card-icon"><i data-lucide="percent"></i></span>
            <span class="dashboard-card-value"><?= htmlspecialchars(number_format($paidPercent, 1)) ?>%</span>
            <span class="dashboard-card-label">Tỷ lệ paid</span>
        </div>
    </div>
    <?php if ($systemOrderIncome): ?>
        <?php foreach ($systemOrderIncome as $currency => $amount): ?>
            <div class="dashboard-card">
                <div class="dashboard-card-line">
                    <span class="dashboard-card-icon"><i data-lucide="circle-dollar-sign"></i></span>
                    <span class="dashboard-card-value"><?= htmlspecialchars(number_format((float) $amount, 2)) ?></span>
                    <span class="dashboard-card-label">Thu nhập <?= htmlspecialchars($currency) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dashboard-card">
            <div class="dashboard-card-line">
                <span class="dashboard-card-icon"><i data-lucide="circle-dollar-sign"></i></span>
                <span class="dashboard-card-value">0.00</span>
                <span class="dashboard-card-label">Thu nhập</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="glass-panel p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">Danh sách đơn hàng</h2>
        <div class="d-flex flex-nowrap align-items-center gap-2 overflow-auto">
            <span class="badge text-bg-secondary flex-shrink-0"><?= number_format(count($systemOrderRows)) ?> dòng</span>
            <form class="js-delete d-inline-flex flex-shrink-0" method="post" data-confirm="Xóa tất cả đơn có trạng thái CREATED ở App, Âm nhạc, Sách, Cloud và COC?" data-confirm-button="Xóa CREATED">
                <input type="hidden" name="action" value="delete_created_orders">
                <button class="btn btn-sm btn-outline-danger fw-bold d-inline-flex align-items-center gap-1 flex-shrink-0" type="submit">
                    <i data-lucide="trash-2" style="width:15px;height:15px"></i>
                    Xóa CREATED
                </button>
            </form>
            <form class="d-flex flex-nowrap align-items-center gap-2 flex-shrink-0" method="get">
                <input type="hidden" name="section" value="order">
                <select class="form-select form-select-sm fw-bold w-auto" name="source" aria-label="Nguồn đơn hàng">
                    <option value="all" <?= $orderSourceFilter === 'all' ? 'selected' : '' ?>>Tất cả nguồn</option>
                    <?php foreach ($orderSourceLabels as $sourceKey => $sourceMeta): ?>
                        <option value="<?= htmlspecialchars($sourceKey) ?>" <?= $orderSourceFilter === $sourceKey ? 'selected' : '' ?>><?= htmlspecialchars($sourceMeta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select form-select-sm fw-bold w-auto" name="order_status" aria-label="Trạng thái đơn hàng">
                    <option value="all" <?= $orderStatusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                    <option value="paid" <?= $orderStatusFilter === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                    <option value="pending" <?= $orderStatusFilter === 'pending' ? 'selected' : '' ?>>Chưa hoàn tất</option>
                </select>
                <button class="btn btn-sm btn-dark fw-bold flex-shrink-0" type="submit">Lọc</button>
            </form>
        </div>
    </div>
    <div class="table-responsive-sm">
        <table class="table table-striped table-hover table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Nguồn</th>
                <th>Đơn</th>
                <th>Sản phẩm/Gói</th>
                <th>User</th>
                <th>Trạng thái</th>
                <th class="text-end">Tiền</th>
                <th>Ngày tạo</th>
                <th>Paid</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($systemOrderRows as $orderRow): ?>
                <?php
                $source = (string) ($orderRow['source'] ?? '');
                $sourceMeta = $orderSourceLabels[$source] ?? ['label' => strtoupper($source), 'icon' => 'receipt-text', 'url' => '#'];
                $status = (string) ($orderRow['status'] ?? '');
                $orderCode = trim((string) ($orderRow['order_code'] ?? ''));
                $userText = trim((string) (($orderRow['user_name'] ?? '') ?: ($orderRow['user_email'] ?? '') ?: ($orderRow['payer_email'] ?? '')));
                ?>
                <tr>
                    <td>
                        <a class="d-inline-flex align-items-center gap-2 fw-bold text-decoration-none" href="<?= htmlspecialchars($sourceMeta['url']) ?>">
                            <i data-lucide="<?= htmlspecialchars($sourceMeta['icon']) ?>" style="width:16px;height:16px"></i>
                            <?= htmlspecialchars($sourceMeta['label']) ?>
                        </a>
                    </td>
                    <td class="font-monospace small"><?= htmlspecialchars($orderCode !== '' ? $orderCode : ('#' . (int) ($orderRow['id'] ?? 0))) ?></td>
                    <td>
                        <strong><?= htmlspecialchars(admin_excerpt((string) ($orderRow['item_name'] ?? ''), 56)) ?></strong>
                        <div class="muted-text small"><?= htmlspecialchars((string) ($orderRow['item_id'] ?? '')) ?></div>
                    </td>
                    <td>
                        <?= htmlspecialchars($userText !== '' ? admin_excerpt($userText, 46) : '-') ?>
                        <?php if (!empty($orderRow['payer_email']) && (string) $orderRow['payer_email'] !== $userText): ?>
                            <div class="muted-text small"><?= htmlspecialchars((string) $orderRow['payer_email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= !empty($orderRow['is_paid']) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars($status) ?></span></td>
                    <td class="text-end"><?= htmlspecialchars(admin_order_money((float) ($orderRow['amount'] ?? 0), (string) ($orderRow['currency'] ?? 'USD'))) ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($orderRow['created_at'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($orderRow['paid_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$systemOrderRows): ?>
                <tr><td colspan="8" class="text-center muted-text py-4">Chưa có đơn hàng phù hợp bộ lọc.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
