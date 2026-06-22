            <?php if ($section === 'overview'): ?>
            <div class="dashboard-grid mb-4">
                <?php foreach ($dashboardCards as $card): ?>
                    <div class="dashboard-card">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div class="dashboard-card-label"><?= htmlspecialchars($card['label']) ?></div>
                            <span class="dashboard-card-icon"><i data-lucide="<?= htmlspecialchars($card['icon']) ?>"></i></span>
                        </div>
                        <div class="dashboard-card-value"><?= number_format((int) $card['value']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (is_array($serverRuntime)): ?>
                <div class="dashboard-card dashboard-uptime">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div class="dashboard-card-label">XAMPP uptime</div>
                        <span class="dashboard-card-icon"><i data-lucide="timer"></i></span>
                    </div>
                    <div class="dashboard-card-value font-monospace" id="server_uptime" data-started-at="<?= (int) $serverRuntime['started_at'] ?>"><?= htmlspecialchars(admin_format_uptime($serverRuntime['uptime_seconds'])) ?></div>
                    <div class="dashboard-uptime-start mt-2">Start: <?= htmlspecialchars(date('Y-m-d H:i:s', $serverRuntime['started_at'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="glass-panel p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0 overview-panel-title"><i data-lucide="chart-no-axes-combined"></i><span>Lưu lượng truy cập</span></h2>
                    <div class="traffic-toggle btn-group" role="group" aria-label="Chuyển chế độ thống kê">
                        <button class="btn btn-sm btn-dark active js-traffic-toggle" type="button" data-traffic-view="site">Theo site</button>
                        <button class="btn btn-sm btn-light js-traffic-toggle" type="button" data-traffic-view="ip">Theo IP</button>
                    </div>
                </div>
                <div class="traffic-view active" data-traffic-panel="site">
                    <div class="dashboard-eyebrow fw-bold mb-2">IP không lặp trong ngày</div>
                    <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Site</th>
                            <th class="text-end">IP hôm nay</th>
                            <th class="text-end">Hits hôm nay</th>
                            <th class="text-end">IP 7 ngày</th>
                            <th class="text-end">Hits 7 ngày</th>
                            <th class="text-end">Tổng IP/ngày</th>
                            <th class="text-end">Tổng hits</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($trafficRows as $row): ?>
                            <?php $metrics = $row['metrics']; ?>
                            <tr>
                                <td>
                                    <?php if ($row['url']): ?>
                                        <a class="traffic-site-link" href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($row['label']) ?></a>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($row['label']) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format((int) $metrics['today_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['today_hits']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['week_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['week_hits']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['total_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['total_hits']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="traffic-view" data-traffic-panel="ip">
                    <div class="dashboard-eyebrow fw-bold mb-2">Tối đa 100 IP mỗi site, sắp xếp theo tổng hits</div>
                    <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>IP</th>
                            <th>Site</th>
                            <th class="text-end">Hits hôm nay</th>
                            <th class="text-end">Hits 7 ngày</th>
                            <th class="text-end">Tổng hits</th>
                            <th class="text-end">Số ngày</th>
                            <th>Gần nhất</th>
                            <th>Request gần nhất</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($trafficIpRows as $ipRow): ?>
                            <tr>
                                <td class="font-monospace small"><?= htmlspecialchars($ipRow['ip_text'] ?? '') ?></td>
                                <td><?= htmlspecialchars($ipRow['site_label'] ?? '') ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['today_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['week_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['total_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['visit_days'] ?? 0)) ?></td>
                                <td class="small"><?= htmlspecialchars($ipRow['last_seen_at'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars(admin_excerpt($ipRow['request_path'] ?? '', 80)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$trafficIpRows): ?>
                            <tr><td colspan="8" class="text-center muted-text py-4">Chưa có dữ liệu IP.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
