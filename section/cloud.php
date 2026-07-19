<?php
$cloudTabUrl = static function (string $tab): string {
    return 'index.php?section=cloud&tab=' . urlencode($tab);
};

$cloudPlanOptions = [];
foreach ($cloudPlans as $cloudPlanOption) {
    $cloudPlanOptions[(int) $cloudPlanOption['id']] = (string) $cloudPlanOption['name'];
}

$cloudShortText = static function (string $value, int $width): string {
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $width, '...', 'UTF-8');
    }

    return strlen($value) > $width ? substr($value, 0, max(0, $width - 3)) . '...' : $value;
};
?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn <?= $cloudTab === 'plans' ? 'btn-dark' : 'btn-light' ?> fw-bold" href="<?= htmlspecialchars($cloudTabUrl('plans')) ?>">
        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="package" style="width:16px;height:16px"></i>Gói dịch vụ</span>
    </a>
    <a class="btn <?= $cloudTab === 'langs' ? 'btn-dark' : 'btn-light' ?> fw-bold" href="<?= htmlspecialchars($cloudTabUrl('langs')) ?>">
        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="languages" style="width:16px;height:16px"></i>Cloud Lang</span>
    </a>
    <a class="btn <?= $cloudTab === 'subscriptions' ? 'btn-dark' : 'btn-light' ?> fw-bold" href="<?= htmlspecialchars($cloudTabUrl('subscriptions')) ?>">
        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="badge-check" style="width:16px;height:16px"></i>User đã mua</span>
    </a>
</div>

<?php if ($cloudTab === 'plans'): ?>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="glass-panel p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h5 mb-0">Danh sách gói Cloud</h2>
                <span class="badge text-bg-secondary"><?= number_format(count($cloudPlans)) ?> gói</span>
            </div>
            <div class="table-responsive-sm">
                <table class="table table-striped table-hover table-sm align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Gói</th>
                        <th>Dung lượng</th>
                        <th>Giá</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cloudPlans as $plan): ?>
                        <tr class="<?= (int) ($editing['id'] ?? 0) === (int) $plan['id'] ? 'table-warning' : '' ?>">
                            <td><?= (int) $plan['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($plan['name']) ?></strong>
                                <?php if (!empty($plan['description'])): ?>
                                    <div class="muted-text small"><?= htmlspecialchars($cloudShortText((string) $plan['description'], 90)) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($plan['user'])): ?>
                                    <div class="small font-monospace">User: <?= htmlspecialchars((string) $plan['user']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((float) $plan['limit_gb'], 2) ?> GB</td>
                            <td><?= htmlspecialchars((string) $plan['currency']) ?> <?= number_format((float) $plan['price'], 2) ?></td>
                            <td><span class="badge <?= $plan['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars((string) $plan['status']) ?></span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-warning" href="index.php?section=cloud&tab=plans&edit=<?= (int) $plan['id'] ?>" title="Sửa" aria-label="Sửa">
                                        <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                    </a>
                                    <form class="js-delete" method="post" data-confirm="Xóa gói Cloud <?= htmlspecialchars($plan['name']) ?>?">
                                        <input type="hidden" name="action" value="delete_cloud_plan">
                                        <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$cloudPlans): ?>
                        <tr><td colspan="6" class="text-center muted-text py-4">Chưa có gói Cloud.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <form class="glass-panel p-4" method="post">
            <input type="hidden" name="action" value="save_cloud_plan">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật gói Cloud' : 'Thêm gói Cloud' ?></h2>
            <div class="mb-3">
                <label class="form-label" for="cloud_name">Name</label>
                <input class="form-control" id="cloud_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="cloud_description">Description</label>
                <textarea class="form-control" id="cloud_description" name="description" rows="4"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="cloud_limit_gb">Limit GB</label>
                    <input class="form-control" id="cloud_limit_gb" name="limit_gb" type="number" step="0.01" min="0" value="<?= htmlspecialchars($editing['limit_gb'] ?? '50') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="cloud_price">Price</label>
                    <input class="form-control" id="cloud_price" name="price" type="number" step="0.01" min="0" value="<?= htmlspecialchars($editing['price'] ?? '0') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="cloud_currency">Currency</label>
                    <input class="form-control" id="cloud_currency" name="currency" maxlength="12" value="<?= htmlspecialchars($editing['currency'] ?? 'USD') ?>">
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label" for="cloud_user">User email/id mặc định</label>
                <input class="form-control" id="cloud_user" name="user" value="<?= htmlspecialchars($editing['user'] ?? '') ?>" placeholder="email hoặc user id">
            </div>
            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label" for="cloud_status">Status</label>
                    <select class="form-select" id="cloud_status" name="status">
                        <?php $cloudStatus = (string) ($editing['status'] ?? 'active'); ?>
                        <option value="active" <?= $cloudStatus === 'active' ? 'selected' : '' ?>>active</option>
                        <option value="inactive" <?= $cloudStatus === 'inactive' ? 'selected' : '' ?>>inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="cloud_sort_order">Sort order</label>
                    <input class="form-control" id="cloud_sort_order" name="sort_order" type="number" value="<?= htmlspecialchars($editing['sort_order'] ?? '0') ?>">
                </div>
            </div>
            <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-4" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm gói' ?></button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($cloudTab === 'langs'): ?>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="glass-panel p-4">
            <h2 class="h5 mb-3">Bản dịch gói Cloud</h2>
            <div class="table-responsive-sm">
                <table class="table table-striped table-hover table-sm align-middle">
                    <thead><tr><th>Gói</th><th>Lang</th><th>Name</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cloudLangs as $langRow): ?>
                        <tr class="<?= (int) ($editing['id'] ?? 0) === (int) $langRow['id'] ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars((string) $langRow['plan_name']) ?></td>
                            <td><span class="badge text-bg-secondary"><?= htmlspecialchars((string) $langRow['lang_key']) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars((string) $langRow['name']) ?></strong>
                                <div class="muted-text small"><?= htmlspecialchars($cloudShortText((string) ($langRow['description'] ?? ''), 100)) ?></div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-warning" href="index.php?section=cloud&tab=langs&edit=<?= (int) $langRow['id'] ?>" title="Sửa" aria-label="Sửa"><i data-lucide="pencil" style="width:16px;height:16px"></i></a>
                                    <form class="js-delete" method="post" data-confirm="Xóa bản dịch này?">
                                        <input type="hidden" name="action" value="delete_cloud_lang">
                                        <input type="hidden" name="id" value="<?= (int) $langRow['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$cloudLangs): ?>
                        <tr><td colspan="4" class="text-center muted-text py-4">Chưa có bản dịch.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <form class="glass-panel p-4" method="post">
            <input type="hidden" name="action" value="save_cloud_lang">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật Cloud Lang' : 'Thêm Cloud Lang' ?></h2>
            <div class="mb-3">
                <label class="form-label" for="cloud_lang_cloud_id">Gói</label>
                <select class="form-select" id="cloud_lang_cloud_id" name="cloud_id" required>
                    <option value="">Chọn gói</option>
                    <?php foreach ($cloudPlanOptions as $planId => $planName): ?>
                        <option value="<?= (int) $planId ?>" <?= (int) ($editing['cloud_id'] ?? 0) === (int) $planId ? 'selected' : '' ?>><?= htmlspecialchars($planName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="cloud_lang_key">Lang key</label>
                <select class="form-select" id="cloud_lang_key" name="lang_key" required>
                    <?= admin_language_select_options($languageOptions, (string) ($editing['lang_key'] ?? 'vi')) ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="cloud_lang_name">Name</label>
                <input class="form-control" id="cloud_lang_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="cloud_lang_description">Description</label>
                <textarea class="form-control" id="cloud_lang_description" name="description" rows="5"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
            </div>
            <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu bản dịch' : 'Thêm bản dịch' ?></button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($cloudTab === 'subscriptions'): ?>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="glass-panel p-4">
            <h2 class="h5 mb-3">User đã mua Cloud</h2>
            <div class="table-responsive-sm">
                <table class="table table-striped table-hover table-sm align-middle">
                    <thead><tr><th>User</th><th>Gói</th><th>Status</th><th>Hết hạn</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cloudSubscriptions as $subscription): ?>
                        <tr class="<?= (int) ($editing['id'] ?? 0) === (int) $subscription['id'] ? 'table-warning' : '' ?>">
                            <td>
                                <strong class="font-monospace"><?= htmlspecialchars((string) $subscription['user']) ?></strong>
                                <?php if (!empty($subscription['payer_email'])): ?><div class="muted-text small"><?= htmlspecialchars((string) $subscription['payer_email']) ?></div><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) $subscription['plan_name']) ?> <span class="muted-text small">(<?= number_format((float) $subscription['limit_gb'], 2) ?> GB)</span></td>
                            <td><span class="badge text-bg-<?= $subscription['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars((string) $subscription['status']) ?></span></td>
                            <td><?= htmlspecialchars((string) ($subscription['expires_at'] ?? '')) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-warning" href="index.php?section=cloud&tab=subscriptions&edit=<?= (int) $subscription['id'] ?>" title="Sửa" aria-label="Sửa"><i data-lucide="pencil" style="width:16px;height:16px"></i></a>
                                    <form class="js-delete" method="post" data-confirm="Xóa quyền sử dụng Cloud này?">
                                        <input type="hidden" name="action" value="delete_cloud_subscription">
                                        <input type="hidden" name="id" value="<?= (int) $subscription['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$cloudSubscriptions): ?>
                        <tr><td colspan="5" class="text-center muted-text py-4">Chưa có user mua Cloud.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <form class="glass-panel p-4" method="post">
            <input type="hidden" name="action" value="save_cloud_subscription">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật quyền Cloud' : 'Cấp quyền Cloud' ?></h2>
            <div class="mb-3">
                <label class="form-label" for="subscription_cloud_id">Gói</label>
                <select class="form-select" id="subscription_cloud_id" name="cloud_id" required>
                    <option value="">Chọn gói</option>
                    <?php foreach ($cloudPlanOptions as $planId => $planName): ?>
                        <option value="<?= (int) $planId ?>" <?= (int) ($editing['cloud_id'] ?? 0) === (int) $planId ? 'selected' : '' ?>><?= htmlspecialchars($planName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="subscription_user">User email/id</label>
                    <input class="form-control" id="subscription_user" name="user" value="<?= htmlspecialchars($editing['user'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="subscription_user_id">User ID</label>
                    <input class="form-control" id="subscription_user_id" name="user_id" type="number" min="0" value="<?= htmlspecialchars($editing['user_id'] ?? '') ?>">
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label" for="subscription_payer_email">Payer email</label>
                <input class="form-control" id="subscription_payer_email" name="payer_email" type="email" value="<?= htmlspecialchars($editing['payer_email'] ?? '') ?>">
            </div>
            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label" for="subscription_provider">Provider</label>
                    <input class="form-control" id="subscription_provider" name="provider" value="<?= htmlspecialchars($editing['provider'] ?? 'paypal') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="subscription_status">Status</label>
                    <?php $subscriptionStatus = (string) ($editing['status'] ?? 'active'); ?>
                    <select class="form-select" id="subscription_status" name="status">
                        <?php foreach (['active', 'pending', 'expired', 'cancelled'] as $statusOption): ?>
                            <option value="<?= htmlspecialchars($statusOption) ?>" <?= $subscriptionStatus === $statusOption ? 'selected' : '' ?>><?= htmlspecialchars($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label" for="subscription_provider_order_id">Provider order id</label>
                <input class="form-control" id="subscription_provider_order_id" name="provider_order_id" value="<?= htmlspecialchars($editing['provider_order_id'] ?? '') ?>">
            </div>
            <div class="mt-3">
                <label class="form-label" for="subscription_expires_at">Expires at</label>
                <input class="form-control" id="subscription_expires_at" name="expires_at" type="datetime-local" value="<?= !empty($editing['expires_at']) ? htmlspecialchars(str_replace(' ', 'T', substr((string) $editing['expires_at'], 0, 16))) : '' ?>">
            </div>
            <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-4" type="submit"><?= $editing ? 'Lưu quyền' : 'Cấp quyền' ?></button>
        </form>
    </div>
</div>
<?php endif; ?>
