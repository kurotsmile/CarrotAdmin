            <?php if ($section === 'overview'): ?>
            <div class="dashboard-grid mb-4">
                <?php foreach ($dashboardCards as $card): ?>
                    <div class="dashboard-card <?= htmlspecialchars($card['class'] ?? '') ?>" <?= !empty($card['key']) ? 'data-dashboard-card="' . htmlspecialchars($card['key']) . '"' : '' ?>>
                        <div class="dashboard-card-line">
                            <span class="dashboard-card-icon"><i data-lucide="<?= htmlspecialchars($card['icon']) ?>"></i></span>
                            <span class="dashboard-card-value"><?= number_format((int) $card['value']) ?></span>
                            <span class="dashboard-card-label"><?= htmlspecialchars($card['label']) ?></span>
                            <?php if (($card['key'] ?? '') === 'traffic-ip'): ?>
                                <span class="dashboard-card-refresh" aria-live="polite" data-refresh-countdown>05:00</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="dashboard-card dashboard-uptime h-100">
                        <span class="dashboard-uptime-icon"><i data-lucide="timer"></i></span>
                        <div class="dashboard-uptime-content">
                            <div class="dashboard-card-label mb-2">XAMPP uptime</div>
                            <?php if (is_array($serverRuntime)): ?>
                                <div class="dashboard-uptime-value font-monospace" id="server_uptime" data-started-at="<?= (int) $serverRuntime['started_at'] ?>"><?= htmlspecialchars(admin_format_uptime($serverRuntime['uptime_seconds'])) ?></div>
                                <div class="dashboard-uptime-start mt-2">Start: <?= htmlspecialchars(date('Y-m-d H:i:s', $serverRuntime['started_at'])) ?></div>
                                <div class="dashboard-uptime-timezone mt-1">Timezone: <?= htmlspecialchars(date_default_timezone_get()) ?> <?= htmlspecialchars(date('P')) ?></div>
                            <?php else: ?>
                                <div class="dashboard-uptime-value font-monospace">--:--:--</div>
                                <div class="dashboard-uptime-start mt-2">Chưa có dữ liệu runtime.</div>
                                <div class="dashboard-uptime-timezone mt-1">Timezone: <?= htmlspecialchars(date_default_timezone_get()) ?> <?= htmlspecialchars(date('P')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dashboard-card dashboard-resource h-100">
                        <?php
                        $diskStats = $systemResources['disk'] ?? [];
                        $diskPercent = $diskStats['percent'] ?? null;
                        $diskUsed = (int) ($diskStats['used'] ?? 0);
                        $diskFree = (int) ($diskStats['free'] ?? 0);
                        $diskTotal = (int) ($diskStats['total'] ?? 0);
                        ?>
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                            <div>
                                <div class="dashboard-card-label mb-1">Tài nguyên PC</div>
                                <div class="dashboard-resource-title">XAMPP disk</div>
                            </div>
                            <span class="dashboard-card-icon"><i data-lucide="hard-drive"></i></span>
                        </div>
                        <div class="dashboard-disk">
                            <div class="dashboard-disk-chart">
                                <canvas id="xampp_disk_chart" width="104" height="104" aria-label="Biểu đồ dung lượng ổ XAMPP"></canvas>
                                <div class="dashboard-disk-center">
                                    <strong><?= $diskPercent !== null ? htmlspecialchars(number_format((float) $diskPercent, 1)) . '%' : '--' ?></strong>
                                    <span>đã dùng</span>
                                </div>
                            </div>
                            <div class="dashboard-disk-meta">
                                <div><span>Trống</span><strong><?= $diskTotal > 0 ? htmlspecialchars(admin_format_bytes($diskFree)) : 'Không rõ' ?></strong></div>
                                <div><span>Đã dùng</span><strong><?= $diskTotal > 0 ? htmlspecialchars(admin_format_bytes($diskUsed)) : 'Không rõ' ?></strong></div>
                                <div><span>Tổng</span><strong><?= $diskTotal > 0 ? htmlspecialchars(admin_format_bytes($diskTotal)) : 'Không rõ' ?></strong></div>
                            </div>
                        </div>
                        <div class="dashboard-resource-path mt-2"><?= htmlspecialchars((string) ($diskStats['path'] ?? '')) ?></div>
                        <script type="application/json" id="xampp_disk_data"><?= json_encode(['used' => $diskUsed, 'free' => $diskFree], JSON_UNESCAPED_SLASHES) ?></script>
                    </div>
                </div>
                <div class="col-lg-4">
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
                    <div class="dashboard-eyebrow fw-bold mb-2">Số liệu theo bộ lọc - <?= htmlspecialchars($trafficDateRange['label']) ?></div>
                    <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Site</th>
                            <th class="text-end">IP</th>
                            <th class="text-end">Hits</th>
                            <th class="text-end">Hits/IP</th>
                            <th class="text-end">Hits/ngày</th>
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
                                <td class="text-end"><?= number_format((int) $metrics['range_unique']) ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['range_hits']) ?></td>
                                <td class="text-end"><?= $metrics['range_unique'] > 0 ? number_format((int) $metrics['range_hits'] / max(1, (int) $metrics['range_unique']), 2) : '0.00' ?></td>
                                <td class="text-end"><?= number_format((int) $metrics['range_hits'] / max(1, (int) $trafficRangeDays), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="traffic-view" data-traffic-panel="ip">
                    <div class="dashboard-eyebrow fw-bold mb-2">Tối đa 100 IP mỗi site, sắp xếp theo hits trong bộ lọc - <?= htmlspecialchars($trafficDateRange['label']) ?></div>
                    <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>IP</th>
                            <th>Site</th>
                            <th class="text-end">Hits</th>
                            <th class="text-end">Số ngày có truy cập</th>
                            <th class="text-end">Hits/ngày</th>
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
                                <td class="text-end"><?= number_format((int) ($ipRow['visit_days'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($ipRow['range_hits'] ?? 0) / max(1, (int) ($ipRow['visit_days'] ?? 0)), 2) ?></td>
                                <td class="small"><?= htmlspecialchars($ipRow['last_seen_at'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars(admin_excerpt($ipRow['request_path'] ?? '', 80)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$trafficIpRows): ?>
                            <tr><td colspan="7" class="text-center muted-text py-4">Chưa có dữ liệu IP.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
