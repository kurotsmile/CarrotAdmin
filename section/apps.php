            <?php if ($section === 'apps'): ?>
            <?php
            $appSearch = trim($_GET['app_q'] ?? '');
            $appPage = max(1, (int) ($_GET['app_page'] ?? 1));
            $appOptions = [];
            $appPhotoRows = [];
            $appPhotoSummary = [];
            $appPerPage = 25;
            $appTotal = 0;
            $appTotalPages = 1;
            if ($pdo instanceof PDO) {
                $appSearchWhere = '';
                $appSearchParams = [];
                if ($appSearch !== '') {
                    $appSearchWhere = ' WHERE id LIKE :app_id_q OR decription LIKE :app_description_q OR type LIKE :app_type_q OR category LIKE :app_category_q';
                    $appSearchValue = '%' . $appSearch . '%';
                    $appSearchParams = [
                        ':app_id_q' => $appSearchValue,
                        ':app_description_q' => $appSearchValue,
                        ':app_type_q' => $appSearchValue,
                        ':app_category_q' => $appSearchValue,
                    ];
                }

                $appCountStmt = $pdo->prepare('SELECT COUNT(*) FROM app' . $appSearchWhere);
                $appCountStmt->execute($appSearchParams);
                $appTotal = (int) $appCountStmt->fetchColumn();
                $appTotalPages = max(1, (int) ceil($appTotal / $appPerPage));
                $appPage = min($appPage, $appTotalPages);
                $appOffset = ($appPage - 1) * $appPerPage;
                $appStmt = $pdo->prepare('SELECT * FROM app' . $appSearchWhere . ' ORDER BY ' . admin_order_by($appSortColumns, $appSort, $appDir) . ', id ASC LIMIT :limit OFFSET :offset');
                foreach ($appSearchParams as $paramKey => $paramValue) {
                    $appStmt->bindValue($paramKey, $paramValue);
                }
                $appStmt->bindValue(':limit', $appPerPage, PDO::PARAM_INT);
                $appStmt->bindValue(':offset', $appOffset, PDO::PARAM_INT);
                $appStmt->execute();
                $apps = $appStmt->fetchAll();

                if ($appTab === 'photos') {
                    $appOptions = $pdo->query('SELECT id FROM app ORDER BY id ASC')->fetchAll();
                    $appPhotoRows = $pdo->query('
                        SELECT app_photo.*, app.decription AS app_decription, app.icon AS app_icon
                        FROM app_photo
                        LEFT JOIN app ON app.id = app_photo.app_id
                        ORDER BY app_photo.app_id ASC, app_photo.sort_order ASC, app_photo.id DESC
                    ')->fetchAll();
                    $appPhotoSummary = $pdo->query('
                        SELECT app.id, app.decription, app.icon, COALESCE(photo_counts.photo_count, 0) AS photo_count, photo_counts.last_photo_at
                        FROM app
                        LEFT JOIN (
                            SELECT app_id, COUNT(*) AS photo_count, MAX(updated_at) AS last_photo_at
                            FROM app_photo
                            GROUP BY app_id
                        ) AS photo_counts ON photo_counts.app_id = app.id
                        ORDER BY photo_count DESC, app.id ASC
                    ')->fetchAll();
                }
            }
            ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $appTab === 'main' ? 'active' : '' ?>" href="index.php?section=apps&tab=main">Dữ liệu chính</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $appTab === 'photos' ? 'active' : '' ?>" href="index.php?section=apps&tab=photos">Ảnh mô tả</a>
                </li>
            </ul>

            <?php if ($appTab === 'main'): ?>
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
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Danh sách app</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-success" href="https://home.carrot28.com" target="_blank" rel="noopener noreferrer" title="Xem Shop" aria-label="Xem Shop">
                                    <i data-lucide="external-link" style="width:16px;height:16px"></i>
                                    <span>Xem Shop</span>
                                </a>
                                <form class="d-flex gap-2" method="get">
                                    <input type="hidden" name="section" value="apps">
                                    <input type="hidden" name="tab" value="main">
                                    <input class="form-control form-control-sm" name="app_q" value="<?= htmlspecialchars($appSearch) ?>" placeholder="Search app">
                                    <button class="btn btn-sm btn-secondary" type="submit" title="Search" aria-label="Search">
                                        <i data-lucide="search" style="width:16px;height:16px"></i>
                                    </button>
                                    <?php if ($appSearch !== ''): ?>
                                        <a class="btn btn-sm btn-light" href="index.php?section=apps&tab=main" title="Clear" aria-label="Clear">
                                            <i data-lucide="x" style="width:16px;height:16px"></i>
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="muted-text small">
                                <?= number_format($appTotal) ?> app
                                <?php if ($appSearch !== ''): ?>
                                    cho từ khóa "<?= htmlspecialchars($appSearch) ?>"
                                <?php endif; ?>
                            </div>
                            <div class="muted-text small">Trang <?= number_format($appPage) ?>/<?= number_format($appTotalPages) ?></div>
                        </div>
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
                        <?php if ($appTotalPages > 1): ?>
                            <?php
                            $appPageParams = $_GET;
                            unset($appPageParams['edit']);
                            $appPageParams['section'] = 'apps';
                            ?>
                            <nav class="d-flex flex-wrap justify-content-end gap-2 mt-3" aria-label="Phân trang app">
                                <a class="btn btn-sm btn-light <?= $appPage <= 1 ? 'disabled' : '' ?>" href="<?= admin_page_url(array_merge($appPageParams, ['app_page' => max(1, $appPage - 1)])) ?>">Trước</a>
                                <?php
                                $pageStart = max(1, $appPage - 2);
                                $pageEnd = min($appTotalPages, $appPage + 2);
                                for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++):
                                ?>
                                    <a class="btn btn-sm <?= $pageNumber === $appPage ? 'btn-dark' : 'btn-light' ?>" href="<?= admin_page_url(array_merge($appPageParams, ['app_page' => $pageNumber])) ?>"><?= number_format($pageNumber) ?></a>
                                <?php endfor; ?>
                                <a class="btn btn-sm btn-light <?= $appPage >= $appTotalPages ? 'disabled' : '' ?>" href="<?= admin_page_url(array_merge($appPageParams, ['app_page' => min($appTotalPages, $appPage + 1)])) ?>">Sau</a>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($appTab === 'photos'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_app_photo">
                        <input type="hidden" name="id" value="<?= (int) ($editingAppPhoto['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editingAppPhoto ? 'Cập nhật ảnh mô tả' : 'Thêm ảnh mô tả' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="app_photo_app_id">App</label>
                            <select class="form-select" id="app_photo_app_id" name="app_id" required>
                                <option value="">Chọn app</option>
                                <?php foreach ($appOptions as $appOption): ?>
                                    <option value="<?= htmlspecialchars($appOption['id']) ?>" <?= (($editingAppPhoto['app_id'] ?? ($_GET['app_id'] ?? '')) === $appOption['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($appOption['id']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="app_photo_image_url">Ảnh mô tả</label>
                            <div class="input-group">
                                <input class="form-control" id="app_photo_image_url" name="image_url" value="<?= htmlspecialchars($editingAppPhoto['image_url'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="app_photo_image_url" data-type-media="carrot_app" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="app_photo_title">Tiêu đề</label>
                                <input class="form-control" id="app_photo_title" name="title" value="<?= htmlspecialchars($editingAppPhoto['title'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="app_photo_sort_order">Thứ tự</label>
                                <input class="form-control" id="app_photo_sort_order" name="sort_order" type="number" value="<?= htmlspecialchars((string) ($editingAppPhoto['sort_order'] ?? 0)) ?>">
                            </div>
                        </div>

                        <button class="btn <?= $editingAppPhoto ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editingAppPhoto ? 'Lưu cập nhật' : 'Thêm ảnh' ?></button>
                        <?php if ($editingAppPhoto): ?>
                            <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=apps&tab=photos">Hủy sửa</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Số lượng ảnh theo app</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>App</th>
                                    <th class="text-center">Số ảnh</th>
                                    <th>Cập nhật</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($appPhotoSummary as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($row['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($row['icon']) ?>" alt="" width="44" height="44" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($row['id']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars(admin_excerpt($row['decription'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $row['photo_count'] ?></span></td>
                                        <td><?= htmlspecialchars($row['last_photo_at'] ?? '') ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-secondary" href="index.php?section=apps&tab=photos&app_id=<?= urlencode($row['id']) ?>" title="Thêm ảnh cho app" aria-label="Thêm ảnh cho app">
                                                <i data-lucide="image-plus" style="width:16px;height:16px"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$appPhotoSummary): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Chi tiết ảnh mô tả</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ảnh</th>
                                    <th>App</th>
                                    <th>Tiêu đề</th>
                                    <th>Thứ tự</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($appPhotoRows as $photo): ?>
                                    <tr>
                                        <td><?= (int) $photo['id'] ?></td>
                                        <td><img src="<?= htmlspecialchars($photo['image_url']) ?>" alt="" width="96" height="54" class="rounded-2 object-fit-cover"></td>
                                        <td><?= htmlspecialchars($photo['app_id']) ?></td>
                                        <td><?= htmlspecialchars($photo['title'] ?? '') ?></td>
                                        <td><?= (int) $photo['sort_order'] ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=apps&tab=photos&photo_edit=<?= (int) $photo['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa ảnh mô tả này?">
                                                    <input type="hidden" name="action" value="delete_app_photo">
                                                    <input type="hidden" name="id" value="<?= (int) $photo['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$appPhotoRows): ?>
                                    <tr><td colspan="6" class="text-center muted-text py-4">Chưa có ảnh mô tả.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
