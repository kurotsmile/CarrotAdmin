            <?php if ($section === 'sites'): ?>
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
                            <label class="form-label" for="sites_description">Mô tả</label>
                            <textarea class="form-control" id="sites_description" name="description" rows="3"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="form-label" for="sites_sort_order">Vị trí sắp xếp</label>
                            <input class="form-control" id="sites_sort_order" name="sort_order" type="number" value="<?= (int) ($editing['sort_order'] ?? 0) ?>">
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
                                                    <div class="muted-text small text-break"><a href="<?php echo $site['url'];?>" target="_blank"><?= htmlspecialchars(admin_excerpt($site['url'] ?? '', 40)) ?></a></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=sites&edit=<?= (int) $site['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
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
                                    <tr><td colspan="3" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
