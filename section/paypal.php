            <?php if ($section === 'paypal'): ?>
            <?php
            $paypalSites = [
                'home' => 'CarrotHome',
                'coc' => 'Coc',
            ];
            $paypalRows = [];
            $paypalEditing = [
                'site' => $paypalTab,
                'enabled' => 0,
                'active_mode' => 'sandbox',
                'sandbox_client_id' => '',
                'sandbox_client_secret' => '',
                'live_client_id' => '',
                'live_client_secret' => '',
                'currency' => 'USD',
                'amount' => '0.00',
            ];

            if ($pdo instanceof PDO) {
                admin_ensure_paypal_config_table($pdo);
                $paypalRows = $pdo->query('SELECT * FROM paypal_config ORDER BY FIELD(site, "home", "coc"), site ASC')->fetchAll();
                $paypalDbRow = admin_fetch_paypal_config($pdo, $paypalTab);
                if ($paypalDbRow) {
                    $paypalEditing = array_merge($paypalEditing, $paypalDbRow);
                }
            }
            ?>

            <ul class="nav nav-tabs mb-4">
                <?php foreach ($paypalSites as $siteKey => $siteLabel): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $paypalTab === $siteKey ? 'active' : '' ?>" href="index.php?section=paypal&tab=<?= urlencode($siteKey) ?>"><?= htmlspecialchars($siteLabel) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="row g-4">
                <div class="col-xl-6">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_paypal_config">
                        <input type="hidden" name="site" value="<?= htmlspecialchars($paypalTab) ?>">
                        <h2 class="h5 mb-3"><?= htmlspecialchars($paypalSites[$paypalTab]) ?> PayPal</h2>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="paypal_enabled">Enabled</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" id="paypal_enabled" name="enabled" type="checkbox" value="1" <?= !empty($paypalEditing['enabled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paypal_enabled">Bật thanh toán</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="paypal_active_mode">Mode</label>
                                <select class="form-select" id="paypal_active_mode" name="active_mode">
                                    <option value="sandbox" <?= ($paypalEditing['active_mode'] ?? 'sandbox') !== 'live' ? 'selected' : '' ?>>SandBox</option>
                                    <option value="live" <?= ($paypalEditing['active_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="paypal_currency">Currency</label>
                                <input class="form-control" id="paypal_currency" name="currency" maxlength="8" value="<?= htmlspecialchars($paypalEditing['currency'] ?? 'USD') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="paypal_amount">Amount</label>
                                <input class="form-control" id="paypal_amount" name="amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($paypalEditing['amount'] ?? '0.00')) ?>">
                            </div>
                        </div>

                        <hr>

                        <h3 class="h6 mb-3">SandBox keys</h3>
                        <div class="mb-3">
                            <label class="form-label" for="sandbox_client_id">Client ID</label>
                            <textarea class="form-control font-monospace small" id="sandbox_client_id" name="sandbox_client_id" rows="2"><?= htmlspecialchars($paypalEditing['sandbox_client_id'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="sandbox_client_secret">Client Secret</label>
                            <textarea class="form-control font-monospace small" id="sandbox_client_secret" name="sandbox_client_secret" rows="2"><?= htmlspecialchars($paypalEditing['sandbox_client_secret'] ?? '') ?></textarea>
                        </div>

                        <h3 class="h6 mb-3 mt-4">Live keys</h3>
                        <div class="mb-3">
                            <label class="form-label" for="live_client_id">Client ID</label>
                            <textarea class="form-control font-monospace small" id="live_client_id" name="live_client_id" rows="2"><?= htmlspecialchars($paypalEditing['live_client_id'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="live_client_secret">Client Secret</label>
                            <textarea class="form-control font-monospace small" id="live_client_secret" name="live_client_secret" rows="2"><?= htmlspecialchars($paypalEditing['live_client_secret'] ?? '') ?></textarea>
                        </div>

                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu PayPal config</button>
                    </form>
                </div>

                <div class="col-xl-6">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Trạng thái cấu hình</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Site</th>
                                    <th>Enabled</th>
                                    <th>Mode</th>
                                    <th>Currency</th>
                                    <th>SandBox</th>
                                    <th>Live</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($paypalSites as $siteKey => $siteLabel): ?>
                                    <?php
                                    $row = null;
                                    foreach ($paypalRows as $paypalRow) {
                                        if (($paypalRow['site'] ?? '') === $siteKey) {
                                            $row = $paypalRow;
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><a class="fw-bold" href="index.php?section=paypal&tab=<?= urlencode($siteKey) ?>"><?= htmlspecialchars($siteLabel) ?></a></td>
                                        <td><?= !empty($row['enabled']) ? '<span class="badge text-bg-success">On</span>' : '<span class="badge text-bg-secondary">Off</span>' ?></td>
                                        <td><?= htmlspecialchars($row['active_mode'] ?? 'sandbox') ?></td>
                                        <td><?= htmlspecialchars(($row['currency'] ?? 'USD') . ' ' . number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars(admin_mask_secret($row['sandbox_client_id'] ?? '')) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars(admin_mask_secret($row['live_client_id'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
