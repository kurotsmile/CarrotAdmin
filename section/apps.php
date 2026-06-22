            <?php if ($section === 'apps'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_app">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật app' : 'Thêm app mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="id">ID</label>
                            <input class="form-control" id="id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="decription">Decription</label>
                            <textarea class="form-control" id="decription" name="decription" rows="4"><?= htmlspecialchars($editing['decription'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="type">Type</label>
                                <input class="form-control" id="type" name="type" value="<?= htmlspecialchars($editing['type'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <input class="form-control" id="status" name="status" value="<?= htmlspecialchars($editing['status'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="priority">Priority</label>
                                <input class="form-control" id="priority" name="priority" type="number" value="<?= htmlspecialchars((string) ($editing['priority'] ?? 0)) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="app_price">Price</label>
                                <input class="form-control" id="app_price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($editing['price'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category">Category</label>
                                <input class="form-control" id="category" name="category" value="<?= htmlspecialchars($editing['category'] ?? '') ?>">
                            </div>
                        </div>

                        <?php
                        $appFields = [
                            'icon' => 'Icon URL',
                            'github' => 'Github',
                            'google_play' => 'Google Play',
                            'microsoft_store' => 'Microsoft Store',
                            'amazon_app_store' => 'Amazon App Store',
                            'huawei_store' => 'Huawei Store',
                            'itch' => 'Itch',
                            'uptodown' => 'Uptodown',
                            'simmer' => 'Simmer',
                            'youtube_link' => 'Youtube link',
                            'apk_file' => 'APK file',
                            'exe_file' => 'EXE file',
                            'deb_file' => 'DEB file',
                            'dmg_file' => 'DMG file',
                            'ipa_file' => 'IPA file',
                        ];
                        foreach ($appFields as $field => $label):
                        ?>
                            <div class="mb-3">
                                <label class="form-label" for="<?= $field ?>"><?= $label ?></label>
                                <?php if (in_array($field, ['icon', 'apk_file', 'exe_file', 'deb_file', 'dmg_file', 'ipa_file'], true)): ?>
                                    <div class="input-group">
                                        <input class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= htmlspecialchars($editing[$field] ?? '') ?>">
                                        <button class="btn btn-secondary js-upload" type="button" data-target="<?= $field ?>" data-type-media="carrot_app" data-mode="replace" data-accept="<?= $field === 'icon' ? 'image/*' : '' ?>">Upload</button>
                                    </div>
                                <?php else: ?>
                                    <input class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= htmlspecialchars($editing[$field] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm app' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách app</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('id', 'App', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('type', 'Type', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('status', 'Status', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('priority', 'Priority', $appSort, $appDir) ?></th>
                                    <th><?= admin_sort_link('price', 'Price', $appSort, $appDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($apps as $app): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($app['id']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($app['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($app['icon']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($app['id']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars(admin_excerpt($app['decription'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($app['type']) ?></td>
                                        <td><?= htmlspecialchars($app['status']) ?></td>
                                        <td><?= (int) $app['priority'] ?></td>
                                        <td><?= number_format((float) ($app['price'] ?? 0), 2) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                            <a class="btn btn-sm btn-warning" href="index.php?section=apps&edit=<?= urlencode($app['id']) ?>" title="Cập nhật" aria-label="Cập nhật">
                                                <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                            </a>
                                            <form class="js-delete" method="post" data-confirm="Xóa app này?">
                                                <input type="hidden" name="action" value="delete_app">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($app['id']) ?>">
                                                <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                    <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$apps): ?>
                                    <tr><td colspan="7" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
