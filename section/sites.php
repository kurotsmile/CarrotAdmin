            <?php if ($section === 'sites'): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $sitesTab === 'main' ? 'active' : '' ?>" href="index.php?section=sites&tab=main">Danh sách site</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $sitesTab === 'google_search' ? 'active' : '' ?>" href="index.php?section=sites&tab=google_search">Xác thực Google Search</a>
                </li>
            </ul>

            <?php if ($sitesTab === 'main'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_sites">
                        <input type="hidden" name="original_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật site' : 'Thêm site mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="sites_name">Tên site</label>
                            <input class="form-control" id="sites_name" name="name" maxlength="120" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="sites_url">URL</label>
                            <input class="form-control" id="sites_url" name="url" type="url" value="<?= htmlspecialchars($editing['url'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="sites_logo">Logo URL</label>
                            <div class="input-group">
                                <input class="form-control" id="sites_logo" name="logo" value="<?= htmlspecialchars($editing['logo'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="sites_logo" data-type-media="sites" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label mb-0" for="sites_description">Mô tả</label>
                                <button class="btn btn-sm btn-outline-success fw-bold js-ai-site-description-request" type="button">
                                    <i data-lucide="sparkles" style="width:15px;height:15px"></i> Yêu cầu AI
                                </button>
                            </div>
                            <textarea class="form-control" id="sites_description" name="description" rows="3"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="sites_status">Trạng thái</label>
                                <?php $siteStatus = ($editing['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active'; ?>
                                <select class="form-select" id="sites_status" name="status">
                                    <option value="active" <?= $siteStatus === 'active' ? 'selected' : '' ?>>Hiện</option>
                                    <option value="hidden" <?= $siteStatus === 'hidden' ? 'selected' : '' ?>>Ẩn</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="sites_sort_order">Vị trí sắp xếp</label>
                                <input class="form-control" id="sites_sort_order" name="sort_order" type="number" value="<?= (int) ($editing['sort_order'] ?? 0) ?>">
                            </div>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm site mới' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách site</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $sitesSort, $sitesDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Tên site', $sitesSort, $sitesDir) ?></th>
                                    <th><?= admin_sort_link('status', 'Trạng thái', $sitesSort, $sitesDir) ?></th>
                                    <th class="text-end"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($sites as $site): ?>
                                    <tr>
                                        <td><?= (int) $site['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($site['logo']): ?>
                                                    <img src="<?= htmlspecialchars($site['logo']) ?>" alt="" width="40" height="40" class="rounded object-fit-cover">
                                                <?php else: ?>
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                        <i data-lucide="globe" style="width:20px;height:20px;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($site['name']) ?></strong>
                                                    <div class="muted-text small text-break"><a href="<?= htmlspecialchars($site['url'] ?? '') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(admin_excerpt($site['url'] ?? '', 40)) ?></a></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php $rowStatus = ($site['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active'; ?>
                                            <span class="badge <?= $rowStatus === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $rowStatus === 'active' ? 'Hiện' : 'Ẩn' ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=sites&tab=main&edit=<?= (int) $site['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa site này?">
                                                    <input type="hidden" name="action" value="delete_sites">
                                                    <input type="hidden" name="id" value="<?= (int) $site['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$sites): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($sitesTab === 'google_search'): ?>
            <?php
            $verificationEditing = $editingGoogleSearchVerification;
            $verificationStatus = ($verificationEditing['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active';
            ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_google_search_verification">
                        <input type="hidden" name="original_id" value="<?= (int) ($verificationEditing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $verificationEditing ? 'Cập nhật mã xác thực' : 'Thêm mã xác thực' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="google_search_site_id">Site</label>
                            <select class="form-select js-api-site-select" id="google_search_site_id" name="site_id" required>
                                <option value="">Chọn site</option>
                                <?php foreach ($sites as $site): ?>
                                    <option value="<?= (int) $site['id'] ?>" <?= (int) ($verificationEditing['site_id'] ?? 0) === (int) $site['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($site['name'] . ' - ' . $site['url']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="google_search_name">Tên ghi chú</label>
                            <input class="form-control" id="google_search_name" name="name" maxlength="160" value="<?= htmlspecialchars($verificationEditing['name'] ?? 'Google Search Console') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="google_search_verification_code">Mã xác thực</label>
                            <textarea class="form-control font-monospace" id="google_search_verification_code" name="verification_code" rows="4" required placeholder='Dán mã hoặc thẻ meta google-site-verification'><?= htmlspecialchars($verificationEditing['verification_code'] ?? '') ?></textarea>
                            <div class="form-text">Có thể dán mã trần hoặc cả thẻ meta từ Google Search Console.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="google_search_status">Trạng thái</label>
                                <select class="form-select" id="google_search_status" name="status">
                                    <option value="active" <?= $verificationStatus === 'active' ? 'selected' : '' ?>>Hiện</option>
                                    <option value="hidden" <?= $verificationStatus === 'hidden' ? 'selected' : '' ?>>Ẩn</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="google_search_sort_order">Vị trí sắp xếp</label>
                                <input class="form-control" id="google_search_sort_order" name="sort_order" type="number" value="<?= (int) ($verificationEditing['sort_order'] ?? 0) ?>">
                            </div>
                        </div>

                        <button class="btn <?= $verificationEditing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $verificationEditing ? 'Lưu cập nhật' : 'Thêm mã xác thực' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Mã xác thực theo site</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Site</th>
                                    <th>Mã xác thực</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($siteGoogleSearchVerifications as $verification): ?>
                                    <?php $rowStatus = ($verification['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active'; ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($verification['site_name'] ?? 'Site đã xóa') ?></strong>
                                            <div class="muted-text small text-break"><?= htmlspecialchars(admin_excerpt($verification['site_url'] ?? '', 48)) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($verification['name'] ?? 'Google Search Console') ?></div>
                                            <code class="small text-break"><?= htmlspecialchars(admin_excerpt($verification['verification_code'] ?? '', 80)) ?></code>
                                        </td>
                                        <td><span class="badge <?= $rowStatus === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $rowStatus === 'active' ? 'Hiện' : 'Ẩn' ?></span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=sites&tab=google_search&edit=<?= (int) $verification['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa mã xác thực này?">
                                                    <input type="hidden" name="action" value="delete_google_search_verification">
                                                    <input type="hidden" name="id" value="<?= (int) $verification['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$siteGoogleSearchVerifications): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có mã xác thực.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
