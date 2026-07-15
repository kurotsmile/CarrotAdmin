            <?php if ($section === 'overview'): ?>
            <div class="dashboard-grid mb-4">
                <?php foreach ($dashboardCards as $card): ?>
                    <div class="dashboard-card <?= htmlspecialchars($card['class'] ?? '') ?>">
                        <div class="dashboard-card-line">
                            <span class="dashboard-card-icon"><i data-lucide="<?= htmlspecialchars($card['icon']) ?>"></i></span>
                            <span class="dashboard-card-value"><?= number_format((int) $card['value']) ?></span>
                            <span class="dashboard-card-label"><?= htmlspecialchars($card['label']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="dashboard-card dashboard-uptime h-100">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <div class="dashboard-card-label mb-2">XAMPP uptime</div>
                                <?php if (is_array($serverRuntime)): ?>
                                    <div class="dashboard-card-value font-monospace" id="server_uptime" data-started-at="<?= (int) $serverRuntime['started_at'] ?>"><?= htmlspecialchars(admin_format_uptime($serverRuntime['uptime_seconds'])) ?></div>
                                    <div class="dashboard-uptime-start mt-2">Start: <?= htmlspecialchars(date('Y-m-d H:i:s', $serverRuntime['started_at'])) ?></div>
                                <?php else: ?>
                                    <div class="dashboard-card-value font-monospace">--:--:--</div>
                                    <div class="dashboard-uptime-start mt-2">Chưa có dữ liệu runtime.</div>
                                <?php endif; ?>
                            </div>
                            <span class="dashboard-card-icon"><i data-lucide="timer"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel p-4 h-100">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h2 class="h5 mb-1 overview-panel-title"><i data-lucide="database-zap"></i><span>Cache hệ thống</span></h2>
                                <div class="muted-text">Hiện đang quản lý cache thống kê CarrotAdmin, list trang chủ CarrotHome và CarrotMusic.</div>
                            </div>
                            <form class="js-delete" method="post" data-confirm="Clear toàn bộ cache CarrotAdmin, CarrotHome và CarrotMusic hiện tại?">
                                <input type="hidden" name="action" value="clear_system_cache">
                                <button class="btn btn-warning fw-bold" type="submit">
                                    <i data-lucide="trash-2" style="width:18px;height:18px"></i>
                                    Clear Cache
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0 overview-panel-title"><i data-lucide="chart-no-axes-combined"></i><span>Lưu lượng truy cập</span></h2>
                    <div class="traffic-tools">
                        <form class="traffic-date-form" method="get">
                            <input type="hidden" name="section" value="overview">
                            <input type="hidden" name="traffic_from" value="<?= htmlspecialchars($trafficDateRange['from']) ?>">
                            <input type="hidden" name="traffic_to" value="<?= htmlspecialchars($trafficDateRange['to']) ?>">
                            <select class="form-select form-select-sm traffic-range-select js-traffic-range-select" name="traffic_range" data-current-from="<?= htmlspecialchars($trafficDateRange['from']) ?>" data-current-to="<?= htmlspecialchars($trafficDateRange['to']) ?>">
                                <option value="today" <?= ($trafficDateRange['preset'] ?? '') === 'today' ? 'selected' : '' ?>>Ngày hôm nay</option>
                                <option value="yesterday" <?= ($trafficDateRange['preset'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Hôm qua</option>
                                <option value="7days" <?= ($trafficDateRange['preset'] ?? '') === '7days' ? 'selected' : '' ?>>7 ngày</option>
                                <option value="1month" <?= ($trafficDateRange['preset'] ?? '') === '1month' ? 'selected' : '' ?>>1 tháng</option>
                                <option value="1year" <?= ($trafficDateRange['preset'] ?? '') === '1year' ? 'selected' : '' ?>>1 năm</option>
                                <option value="custom" <?= ($trafficDateRange['preset'] ?? '') === 'custom' ? 'selected' : '' ?>>Tùy chọn từ - đến</option>
                            </select>
                        </form>
                        <div class="traffic-toggle btn-group" role="group" aria-label="Chuyển chế độ thống kê">
                            <button class="btn btn-sm btn-dark active js-traffic-toggle" type="button" data-traffic-view="site">Theo site</button>
                            <button class="btn btn-sm btn-light js-traffic-toggle" type="button" data-traffic-view="ip">Theo IP</button>
                        </div>
                    </div>
                </div>
                <div class="traffic-chart-wrap mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
                        <div>
                            <div class="dashboard-eyebrow fw-bold">Theo <?= ($trafficChartData['mode'] ?? '') === 'hourly' ? 'giờ' : 'ngày' ?></div>
                            <div class="traffic-chart-title">Hits / IP - <?= htmlspecialchars($trafficDateRange['label']) ?></div>
                        </div>
                        <div class="traffic-chart-legend">
                            <button class="traffic-legend-btn is-active" type="button" data-traffic-dataset="0" aria-pressed="true"><i class="traffic-dot traffic-dot-today"></i>Hits</button>
                            <button class="traffic-legend-btn is-active" type="button" data-traffic-dataset="1" aria-pressed="true"><i class="traffic-dot traffic-dot-yesterday"></i>IP</button>
                        </div>
                    </div>
                    <canvas id="traffic_compare_chart" height="350" aria-label="Biểu đồ lưu lượng truy cập"></canvas>
                    <script type="application/json" id="traffic_compare_data"><?= json_encode($trafficChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
                </div>
                <div class="traffic-view active" data-traffic-panel="site">
                    <div class="dashboard-eyebrow fw-bold mb-2">IP không lặp trong ngày - khoảng <?= htmlspecialchars($trafficDateRange['label']) ?></div>
                    <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Site</th>
                            <th class="text-end">IP hôm nay</th>
                            <th class="text-end">Hits hôm nay</th>
                            <th class="text-end">IP 7 ngày</th>
                            <th class="text-end">Hits 7 ngày</th>
                            <th class="text-end">IP khoảng</th>
                            <th class="text-end">Hits khoảng</th>
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
                                <td class="text-end"><?= number_format((int) $metrics['range_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['range_hits']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['total_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['total_hits']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="traffic-view" data-traffic-panel="ip">
                    <div class="dashboard-eyebrow fw-bold mb-2">Tối đa 100 IP mỗi site, sắp xếp theo hits khoảng - <?= htmlspecialchars($trafficDateRange['label']) ?></div>
                    <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>IP</th>
                            <th>Site</th>
                            <th class="text-end">Hits khoảng</th>
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
                                <td class="text-end"><?= number_format((int) ($ipRow['range_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['today_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['week_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['total_hits'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['visit_days'] ?? 0)) ?></td>
                                <td class="small"><?= htmlspecialchars($ipRow['last_seen_at'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars(admin_excerpt($ipRow['request_path'] ?? '', 80)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$trafficIpRows): ?>
                            <tr><td colspan="9" class="text-center muted-text py-4">Chưa có dữ liệu IP.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
