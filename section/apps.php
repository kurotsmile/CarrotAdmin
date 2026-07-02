            <?php if ($section === 'apps'): ?>
            <?php
            $appSearch = trim($_GET['app_q'] ?? '');
            $appPage = max(1, (int) ($_GET['app_page'] ?? 1));
            $appOptions = [];
            $appPhotoRows = [];
            $appPhotoSummary = [];
            $appContentRows = [];
            $appContentSummary = [];
            $appContentUsedLangKeys = [];
            $appCategoryOptions = [];
            $appCategoryRows = [];
            $appCategoryContentRows = [];
            $appCategoryContentUsedLangKeys = [];
            $editingAppCategory = null;
            $editingAppCategoryContent = null;
            $selectedCategoryId = trim($_GET['category_id'] ?? '');
            $editingAppPhoto = null;
            $editingAppContent = null;
            $editingAppStore = null;
            $appStoreRows = [];
            $selectedAppId = trim($_GET['app_id'] ?? '');
            $photoEditId = (int) ($_GET['photo_edit'] ?? 0);
            $contentEditId = (int) ($_GET['content_edit'] ?? 0);
            $categoryEditId = trim($_GET['category_edit'] ?? '');
            $categoryContentEditId = (int) ($_GET['category_content_edit'] ?? 0);
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
                $appCategoryOptions = $pdo->query('SELECT category_id, icon FROM app_category ORDER BY category_id ASC')->fetchAll();

                if ($appTab === 'photos') {
                    $appOptions = $pdo->query('SELECT id FROM app ORDER BY id ASC')->fetchAll();
                    if ($photoEditId > 0) {
                        $editingPhotoStmt = $pdo->prepare('SELECT * FROM app_photo WHERE id = ?');
                        $editingPhotoStmt->execute([$photoEditId]);
                        $editingAppPhoto = $editingPhotoStmt->fetch() ?: null;
                        if ($editingAppPhoto && $selectedAppId === '') {
                            $selectedAppId = (string) $editingAppPhoto['app_id'];
                        }
                    }
                    if ($selectedAppId !== '') {
                        $appPhotoStmt = $pdo->prepare('
                            SELECT *
                            FROM app_photo
                            WHERE app_id = ?
                            ORDER BY sort_order ASC, id DESC
                        ');
                        $appPhotoStmt->execute([$selectedAppId]);
                        $appPhotoRows = $appPhotoStmt->fetchAll();
                    }
                    $appPhotoSummary = $pdo->query('
                        SELECT app.id, app.icon, COALESCE(photo_counts.photo_count, 0) AS photo_count, photo_counts.last_photo_at
                        FROM app
                        LEFT JOIN (
                            SELECT app_id, COUNT(*) AS photo_count, MAX(updated_at) AS last_photo_at
                            FROM app_photo
                            GROUP BY app_id
                        ) AS photo_counts ON photo_counts.app_id = app.id
                        ORDER BY photo_count DESC, app.id ASC
                    ')->fetchAll();
                }

                if ($appTab === 'content') {
                    $appOptions = $pdo->query('SELECT id FROM app ORDER BY id ASC')->fetchAll();
                    if ($contentEditId > 0) {
                        $editingContentStmt = $pdo->prepare('SELECT * FROM app_content WHERE id = ?');
                        $editingContentStmt->execute([$contentEditId]);
                        $editingAppContent = $editingContentStmt->fetch() ?: null;
                        if ($editingAppContent && $selectedAppId === '') {
                            $selectedAppId = (string) $editingAppContent['app_id'];
                        }
                    }
                    if ($selectedAppId !== '') {
                        $appContentStmt = $pdo->prepare('
                            SELECT *
                            FROM app_content
                            WHERE app_id = ?
                            ORDER BY lang_key ASC, id DESC
                        ');
                        $appContentStmt->execute([$selectedAppId]);
                        $appContentRows = $appContentStmt->fetchAll();
                        $appContentUsedLangKeys = array_values(array_unique(array_filter(array_map(static fn(array $row): string => (string) ($row['lang_key'] ?? ''), $appContentRows))));
                    }
                    $appContentSummary = $pdo->query('
                        SELECT app.id, app.icon, COALESCE(content_counts.content_count, 0) AS content_count, content_counts.last_content_at
                        FROM app
                        LEFT JOIN (
                            SELECT app_id, COUNT(*) AS content_count, MAX(updated_at) AS last_content_at
                            FROM app_content
                            GROUP BY app_id
                        ) AS content_counts ON content_counts.app_id = app.id
                        ORDER BY content_count DESC, app.id ASC
                    ')->fetchAll();
                }

                if ($appTab === 'stores') {
                    $storeEditId = (int) ($_GET['store_edit'] ?? 0);
                    if ($storeEditId > 0) {
                        $editingStoreStmt = $pdo->prepare('SELECT * FROM app_store WHERE id = ?');
                        $editingStoreStmt->execute([$storeEditId]);
                        $editingAppStore = $editingStoreStmt->fetch() ?: null;
                    }
                    $appStoreRows = $pdo->query('SELECT * FROM app_store ORDER BY sort_order ASC, title ASC, id DESC')->fetchAll();
                }

                if ($appTab === 'categories') {
                    if ($categoryEditId !== '') {
                        $editingCategoryStmt = $pdo->prepare('SELECT * FROM app_category WHERE category_id = ?');
                        $editingCategoryStmt->execute([$categoryEditId]);
                        $editingAppCategory = $editingCategoryStmt->fetch() ?: null;
                    }
                    if ($categoryContentEditId > 0) {
                        $editingCategoryContentStmt = $pdo->prepare('SELECT * FROM app_category_content WHERE id = ?');
                        $editingCategoryContentStmt->execute([$categoryContentEditId]);
                        $editingAppCategoryContent = $editingCategoryContentStmt->fetch() ?: null;
                        if ($editingAppCategoryContent && $selectedCategoryId === '') {
                            $selectedCategoryId = (string) $editingAppCategoryContent['category_id'];
                        }
                    }
                    if ($selectedCategoryId !== '') {
                        $categoryContentStmt = $pdo->prepare('SELECT * FROM app_category_content WHERE category_id = ? ORDER BY key_lang ASC, id DESC');
                        $categoryContentStmt->execute([$selectedCategoryId]);
                        $appCategoryContentRows = $categoryContentStmt->fetchAll();
                        $appCategoryContentUsedLangKeys = array_values(array_unique(array_filter(array_map(static fn(array $row): string => (string) ($row['key_lang'] ?? ''), $appCategoryContentRows))));
                    }
                    $appCategoryRows = $pdo->query('
                        SELECT c.category_id, c.icon, COUNT(DISTINCT ca.app_id) AS app_count, COUNT(DISTINCT cc.id) AS content_count
                        FROM app_category c
                        LEFT JOIN category_app ca ON ca.category_id = c.category_id
                        LEFT JOIN app_category_content cc ON cc.category_id = c.category_id
                        GROUP BY c.category_id, c.icon
                        ORDER BY c.category_id ASC
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
                <li class="nav-item">
                    <a class="nav-link <?= $appTab === 'content' ? 'active' : '' ?>" href="index.php?section=apps&tab=content">Nội dung mô tả</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $appTab === 'categories' ? 'active' : '' ?>" href="index.php?section=apps&tab=categories">Chuyên mục</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $appTab === 'stores' ? 'active' : '' ?>" href="index.php?section=apps&tab=stores">Store Khác</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $appTab === 'orders' ? 'active' : '' ?>" href="index.php?section=apps&tab=orders">Đơn đặt hàng</a>
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
                                <?php
                                $selectedCategoryIds = $editing ? admin_fetch_app_category_ids($pdo, (string) $editing['id']) : [];
                                if (!$selectedCategoryIds && !empty($editing['category'])) {
                                    $selectedCategoryIds = array_values(array_filter(array_map('trim', explode(',', (string) $editing['category']))));
                                }
                                ?>
                                <select class="form-control js-app-category-select" id="category" name="category_ids[]" multiple>
                                    <?php foreach ($appCategoryOptions as $categoryOption): ?>
                                        <option value="<?= htmlspecialchars($categoryOption['category_id']) ?>" <?= in_array((string) $categoryOption['category_id'], $selectedCategoryIds, true) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoryOption['category_id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                        <?php
                        $appPageParams = $_GET;
                        unset($appPageParams['edit']);
                        $appPageParams['section'] = 'apps';
                        ?>
                        <?= admin_pagination($appPageParams, 'app_page', $appPage, $appTotalPages, 'Phân trang app', 'mb-3') ?>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
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
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($app['icon'])): ?>
                                                    <a href="<?= htmlspecialchars(admin_app_detail_url($app['id'])) ?>" target="_blank" rel="noopener noreferrer" title="Xem chi tiết app" aria-label="Xem chi tiết app">
                                                        <img src="<?= htmlspecialchars($app['icon']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                    </a>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($app['id']) ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($app['type']) ?></td>
                                        <td><?= htmlspecialchars($app['status']) ?></td>
                                        <td><?= (int) $app['priority'] ?></td>
                                        <td><?= number_format((float) ($app['price'] ?? 0), 2) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                            <a class="btn btn-sm btn-secondary" href="index.php?section=apps&tab=photos&app_id=<?= urlencode($app['id']) ?>" title="Thêm ảnh mô tả" aria-label="Thêm ảnh mô tả">
                                                <i data-lucide="image-plus" style="width:16px;height:16px"></i>
                                            </a>
                                            <a class="btn btn-sm btn-secondary" href="index.php?section=apps&tab=content&app_id=<?= urlencode($app['id']) ?>" title="Thêm nội dung mô tả" aria-label="Thêm nội dung mô tả">
                                                <i data-lucide="file-plus-2" style="width:16px;height:16px"></i>
                                            </a>
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
                                    <tr><td colspan="6" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= admin_pagination($appPageParams, 'app_page', $appPage, $appTotalPages, 'Phân trang app') ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($appTab === 'orders'): ?>
            <div class="glass-panel p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0">Đơn đặt hàng App</h2>
                    <span class="badge text-bg-secondary"><?= number_format(count($appOrders)) ?> đơn</span>
                </div>
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>App</th>
                            <th>User</th>
                            <th>PayPal Order</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Created</th>
                            <th>Paid</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($appOrders as $order): ?>
                            <?php
                                $orderAppId = (string) ($order['app_id'] ?? '');
                                $paypalOrderId = (string) ($order['paypal_order_id'] ?? '');
                                $paypalReturnUrl = 'https://home.carrot28.com/paypal-return.php?' . http_build_query([
                                    'slug' => $orderAppId,
                                    'token' => $paypalOrderId,
                                ]);
                            ?>
                            <tr>
                                <td><?= (int) $order['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($order['app_icon'])): ?>
                                            <a href="https://home.carrot28.com/app.php?slug=<?php echo htmlspecialchars($orderAppId) ?>" target="_blank" rel="noopener noreferrer">
                                                <img src="<?= htmlspecialchars($order['app_icon']) ?>" alt="" width="42" height="42" class="rounded-2 object-fit-cover">
                                            </a>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= htmlspecialchars($order['app_id'] ?? '') ?></strong>
                                            <div class="muted-text small"><?= htmlspecialchars(admin_excerpt($order['app_description'] ?? '', 42)) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($order['user_name'] ?? '') ?></strong>
                                    <div class="muted-text small"><?= htmlspecialchars($order['user_email'] ?: $order['payer_email'] ?: '') ?></div>
                                </td>
                                <td class="font-monospace small">
                                    <?php if ($paypalOrderId !== ''): ?>
                                        <a href="<?= htmlspecialchars($paypalReturnUrl) ?>" target="_blank" rel="noopener noreferrer" title="Xem chi tiết đơn trên CarrotHome">
                                            <?= htmlspecialchars($paypalOrderId) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= ($order['status'] ?? '') === 'COMPLETED' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= htmlspecialchars($order['status'] ?? '') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(number_format((float) ($order['amount'] ?? 0), 2)) ?> <?= htmlspecialchars($order['currency'] ?? 'USD') ?></td>
                                <td class="small"><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars($order['paid_at'] ?? '') ?></td>
                                <td class="text-end">
                                    <form class="js-delete d-inline-flex" method="post" data-confirm="Xóa đơn đặt hàng app này?">
                                        <input type="hidden" name="action" value="delete_app_order">
                                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Xóa đơn" aria-label="Xóa đơn">
                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$appOrders): ?>
                            <tr><td colspan="9" class="text-center muted-text py-4">Chưa có đơn đặt hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($appTab === 'stores'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_app_store">
                        <input type="hidden" name="id" value="<?= (int) ($editingAppStore['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editingAppStore ? 'Cập nhật cổng phân phối' : 'Thêm cổng phân phối' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="app_store_title">Tên store</label>
                            <input class="form-control" id="app_store_title" name="title" value="<?= htmlspecialchars($editingAppStore['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="app_store_description">Mô tả</label>
                            <textarea class="form-control" id="app_store_description" name="description" rows="3"><?= htmlspecialchars($editingAppStore['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="app_store_icon">Icon</label>
                            <div class="input-group">
                                <input class="form-control" id="app_store_icon" name="icon" value="<?= htmlspecialchars($editingAppStore['icon'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="app_store_icon" data-type-media="carrot_app" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="app_store_link">Link</label>
                            <input class="form-control" id="app_store_link" name="link" value="<?= htmlspecialchars($editingAppStore['link'] ?? '') ?>" required>
                        </div>

                        <button class="btn <?= $editingAppStore ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editingAppStore ? 'Lưu cập nhật' : 'Thêm cổng' ?></button>
                        <?php if ($editingAppStore): ?>
                            <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=apps&tab=stores">Hủy sửa</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Danh sách Store Khác</h2>
                            <div class="muted-text small"><?= number_format(count($appStoreRows)) ?> mục</div>
                        </div>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Store</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($appStoreRows as $store): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($store['icon'])): ?>
                                                <img src="<?= htmlspecialchars($store['icon']) ?>" alt="" width="40" height="40" class="rounded-2 object-fit-cover">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($store['title']) ?></div>
                                            <div class="small muted-text"><?= htmlspecialchars($store['slug']) ?></div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=apps&tab=stores&store_edit=<?= (int) $store['id'] ?>" title="Sửa" aria-label="Sửa">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa cổng phân phối này?">
                                                    <input type="hidden" name="action" value="delete_app_store">
                                                    <input type="hidden" name="id" value="<?= (int) $store['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$appStoreRows): ?>
                                    <tr><td colspan="3" class="text-center muted-text py-4">Chưa có cổng phân phối nào.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($appTab === 'photos'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="glass-panel p-4">
                    <form method="post">
                        <input type="hidden" name="action" value="save_app_photo">
                        <input type="hidden" name="id" value="<?= (int) ($editingAppPhoto['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editingAppPhoto ? 'Cập nhật ảnh mô tả' : ($selectedAppId !== '' ? 'Ảnh mô tả: ' . htmlspecialchars($selectedAppId) : 'Thêm ảnh mô tả') ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="app_photo_app_id">App</label>
                            <select class="form-select" id="app_photo_app_id" name="app_id" required>
                                <option value="">Chọn app</option>
                                <?php foreach ($appOptions as $appOption): ?>
                                    <option value="<?= htmlspecialchars($appOption['id']) ?>" <?= (($editingAppPhoto['app_id'] ?? $selectedAppId) === $appOption['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($appOption['id']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="app_photo_image_url">Ảnh mô tả</label>
                            <div class="input-group">
                                <input class="form-control" id="app_photo_image_url" name="image_url" value="<?= htmlspecialchars($editingAppPhoto['image_url'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="app_photo_image_url" data-type-media="carrot_app_photo" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="app_photo_display_mode">Kiểu hiển thị</label>
                                <select class="form-select" id="app_photo_display_mode" name="display_mode">
                                    <?php $photoDisplayMode = (string) ($editingAppPhoto['display_mode'] ?? 'vertical'); ?>
                                    <option value="vertical" <?= $photoDisplayMode !== 'horizontal' ? 'selected' : '' ?>>Dọc</option>
                                    <option value="horizontal" <?= $photoDisplayMode === 'horizontal' ? 'selected' : '' ?>>Ngang</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="app_photo_sort_order">Thứ tự</label>
                                <input class="form-control" id="app_photo_sort_order" name="sort_order" type="number" value="<?= htmlspecialchars((string) ($editingAppPhoto['sort_order'] ?? 0)) ?>">
                            </div>
                        </div>

                        <button class="btn <?= $editingAppPhoto ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editingAppPhoto ? 'Lưu cập nhật' : 'Thêm ảnh' ?></button>
                        <?php if ($editingAppPhoto): ?>
                            <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=apps&tab=photos&app_id=<?= urlencode((string) $editingAppPhoto['app_id']) ?>">Hủy sửa</a>
                        <?php endif; ?>
                    </form>

                    <?php if ($selectedAppId !== ''): ?>
                        <hr>
                        <h3 class="h6 mb-3">Ảnh đã thêm</h3>
                        <div class="row g-3">
                            <?php foreach ($appPhotoRows as $photo): ?>
                                <div class="col-6">
                                    <div class="border rounded-2 overflow-hidden bg-white">
                                        <img src="<?= htmlspecialchars($photo['image_url']) ?>" alt="" class="w-100 object-fit-cover" style="aspect-ratio:16/10">
                                        <div class="d-flex align-items-center justify-content-between gap-2 p-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="muted-text small">#<?= (int) $photo['sort_order'] ?></span>
                                                <span class="badge text-bg-light"><?= ($photo['display_mode'] ?? 'vertical') === 'horizontal' ? 'Ngang' : 'Dọc' ?></span>
                                            </div>
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=apps&tab=photos&app_id=<?= urlencode($photo['app_id']) ?>&photo_edit=<?= (int) $photo['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
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
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$appPhotoRows): ?>
                                <div class="col-12">
                                    <div class="text-center muted-text py-4">App này chưa có ảnh mô tả.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    </div>
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
                                                    <a href="<?= htmlspecialchars(admin_app_detail_url($row['id'])) ?>" target="_blank" rel="noopener noreferrer" title="Xem chi tiết app" aria-label="Xem chi tiết app">
                                                        <img src="<?= htmlspecialchars($row['icon']) ?>" alt="" width="44" height="44" class="rounded-2 object-fit-cover">
                                                    </a>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($row['id']) ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $row['photo_count'] ?></span></td>
                                        <td><?= htmlspecialchars($row['last_photo_at'] ?? '') ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm <?= $selectedAppId === $row['id'] ? 'btn-success' : 'btn-secondary' ?>" href="index.php?section=apps&tab=photos&app_id=<?= urlencode($row['id']) ?>" title="Thêm ảnh cho app" aria-label="Thêm ảnh cho app">
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
            </div>
            <?php endif; ?>

            <?php if ($appTab === 'content'): ?>
            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <form method="post">
                            <input type="hidden" name="action" value="save_app_content">
                            <input type="hidden" name="id" value="<?= (int) ($editingAppContent['id'] ?? 0) ?>">
                            <h2 class="h5 mb-3"><?= $editingAppContent ? 'Cập nhật nội dung mô tả' : ($selectedAppId !== '' ? 'Nội dung mô tả: ' . htmlspecialchars($selectedAppId) : 'Thêm nội dung mô tả') ?></h2>

                            <div class="mb-3">
                                <label class="form-label" for="app_content_app_id">App</label>
                                <select class="form-select" id="app_content_app_id" name="app_id" required>
                                    <option value="">Chọn app</option>
                                    <?php foreach ($appOptions as $appOption): ?>
                                        <option value="<?= htmlspecialchars($appOption['id']) ?>" <?= (($editingAppContent['app_id'] ?? $selectedAppId) === $appOption['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($appOption['id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="app_content_lang_key">Lang key</label>
                                <?php
                                $appContentLangValue = (string) ($editingAppContent['lang_key'] ?? '');
                                $appContentAvailableLanguages = array_values(array_filter($languageOptions, static function (array $language) use ($appContentUsedLangKeys, $appContentLangValue): bool {
                                    $langKey = (string) ($language['lang_key'] ?? '');
                                    return $langKey === $appContentLangValue || !in_array($langKey, $appContentUsedLangKeys, true);
                                }));
                                if ($appContentLangValue === '') {
                                    $appContentLangValue = (string) ($appContentAvailableLanguages[0]['lang_key'] ?? '');
                                }
                                ?>
                                <select class="form-control js-app-content-lang-select" id="app_content_lang_key" name="lang_key" required>
                                    <?php if ($appContentLangValue === ''): ?>
                                        <option value=""></option>
                                    <?php endif; ?>
                                    <?= admin_language_select_options($appContentAvailableLanguages, $appContentLangValue) ?>
                                    <?php if ($appContentLangValue !== '' && !in_array($appContentLangValue, array_column($appContentAvailableLanguages, 'lang_key'), true)): ?>
                                        <option value="<?= htmlspecialchars($appContentLangValue) ?>" selected><?= htmlspecialchars($appContentLangValue) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="app_content_title">Title</label>
                                <input class="form-control" id="app_content_title" name="title" value="<?= htmlspecialchars($editingAppContent['title'] ?? '') ?>" placeholder="<?= htmlspecialchars((string) ($editingAppContent['app_id'] ?? $selectedAppId)) ?>">
                            </div>

                            <div class="mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <label class="form-label mb-0" for="app_content_html">HTML content</label>
                                    <button class="btn btn-sm btn-secondary d-none js-ai-app-content-generate" type="button">
                                        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="sparkles" style="width:16px;height:16px"></i>Tạo bằng AI</span>
                                    </button>
                                </div>
                                <div class="simple-editor-toolbar" aria-label="Editor toolbar">
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="bold"><strong>B</strong></button>
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="italic"><em>I</em></button>
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="formatBlock" data-editor-value="h2">H2</button>
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="formatBlock" data-editor-value="p">P</button>
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="insertUnorderedList">List</button>
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="createLink">Link</button>
                                    <button class="btn btn-sm btn-light" type="button" data-editor-target="app_content" data-editor-command="removeFormat">Clear</button>
                                </div>
                                <div class="simple-editor-canvas" id="app_content_editor" contenteditable="true" spellcheck="true" style="min-height:520px;margin-top:8px;padding:5px;border:1px solid rgba(0,0,0,.15)"><?= $editingAppContent['content_html'] ?? '<p></p>' ?></div>
                                <textarea class="form-control font-monospace d-none" id="app_content_html" name="content_html" required><?= htmlspecialchars($editingAppContent['content_html'] ?? '') ?></textarea>
                            </div>

                            <button class="btn <?= $editingAppContent ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editingAppContent ? 'Lưu cập nhật' : 'Lưu nội dung' ?></button>
                            <?php if ($editingAppContent): ?>
                                <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=apps&tab=content&app_id=<?= urlencode((string) $editingAppContent['app_id']) ?>">Hủy sửa</a>
                            <?php endif; ?>
                        </form>

                        <?php if ($selectedAppId !== ''): ?>
                            <hr>
                            <h3 class="h6 mb-3">Nội dung đã thêm</h3>
                            <div class="table-responsive-sm">
                                <table class="table table-striped table-hover table-sm align-middle">
                                    <thead>
                                    <tr>
                                        <th>Lang</th>
                                        <th>Title</th>
                                        <th>Cập nhật</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($appContentRows as $content): ?>
                                        <tr>
                                            <td class="font-monospace small"><?= htmlspecialchars($content['lang_key']) ?></td>
                                            <td><?= htmlspecialchars(trim((string) ($content['title'] ?? '')) ?: (string) ($content['app_id'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($content['updated_at'] ?? '') ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                    <a class="btn btn-sm btn-warning" href="index.php?section=apps&tab=content&app_id=<?= urlencode($content['app_id']) ?>&content_edit=<?= (int) $content['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                        <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                    </a>
                                                    <form class="js-delete" method="post" data-confirm="Xóa nội dung mô tả này?">
                                                        <input type="hidden" name="action" value="delete_app_content">
                                                        <input type="hidden" name="id" value="<?= (int) $content['id'] ?>">
                                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$appContentRows): ?>
                                        <tr><td colspan="4" class="text-center muted-text py-4">App này chưa có nội dung mô tả.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Số lượng nội dung theo app</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>App</th>
                                    <th class="text-center">Số lang</th>
                                    <th>Cập nhật</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($appContentSummary as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($row['icon'])): ?>
                                                    <a href="<?= htmlspecialchars(admin_app_detail_url($row['id'])) ?>" target="_blank" rel="noopener noreferrer" title="Xem chi tiết app" aria-label="Xem chi tiết app">
                                                        <img src="<?= htmlspecialchars($row['icon']) ?>" alt="" width="44" height="44" class="rounded-2 object-fit-cover">
                                                    </a>
                                                <?php endif; ?>
                                                <div><strong><?= htmlspecialchars($row['id']) ?></strong></div>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $row['content_count'] ?></span></td>
                                        <td><?= htmlspecialchars($row['last_content_at'] ?? '') ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm <?= $selectedAppId === $row['id'] ? 'btn-success' : 'btn-secondary' ?>" href="index.php?section=apps&tab=content&app_id=<?= urlencode($row['id']) ?>" title="Thêm nội dung cho app" aria-label="Thêm nội dung cho app">
                                                <i data-lucide="file-plus-2" style="width:16px;height:16px"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$appContentSummary): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($appTab === 'categories'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="glass-panel p-4 mb-4">
                        <form method="post">
                            <input type="hidden" name="action" value="save_app_category">
                            <input type="hidden" name="original_category_id" value="<?= htmlspecialchars($editingAppCategory['category_id'] ?? '') ?>">
                            <h2 class="h5 mb-3"><?= $editingAppCategory ? 'Cập nhật chuyên mục' : 'Thêm chuyên mục' ?></h2>
                            <div class="mb-3">
                                <label class="form-label" for="app_category_id">Category ID</label>
                                <input class="form-control" id="app_category_id" name="category_id" value="<?= htmlspecialchars($editingAppCategory['category_id'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="app_category_icon">Icon</label>
                                <div class="input-group">
                                    <input class="form-control" id="app_category_icon" name="icon" value="<?= htmlspecialchars($editingAppCategory['icon'] ?? '') ?>">
                                    <button class="btn btn-secondary js-upload" type="button" data-target="app_category_icon" data-type-media="carrot_app" data-mode="replace" data-accept="image/*">Upload</button>
                                </div>
                            </div>
                            <button class="btn <?= $editingAppCategory ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editingAppCategory ? 'Lưu cập nhật' : 'Lưu chuyên mục' ?></button>
                            <?php if ($editingAppCategory): ?>
                                <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=apps&tab=categories">Hủy sửa</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="glass-panel p-4">
                        <form method="post">
                            <input type="hidden" name="action" value="save_app_category_content">
                            <input type="hidden" name="id" value="<?= (int) ($editingAppCategoryContent['id'] ?? 0) ?>">
                            <h2 class="h5 mb-3"><?= $editingAppCategoryContent ? 'Cập nhật nội dung chuyên mục' : 'Thêm nội dung chuyên mục' ?></h2>
                            <div class="mb-3">
                                <label class="form-label" for="category_content_category_id">Category</label>
                                <select class="form-select" id="category_content_category_id" name="category_id" required>
                                    <option value="">Chọn category</option>
                                    <?php foreach ($appCategoryOptions as $categoryOption): ?>
                                        <option value="<?= htmlspecialchars($categoryOption['category_id']) ?>" <?= (($editingAppCategoryContent['category_id'] ?? $selectedCategoryId) === $categoryOption['category_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoryOption['category_id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="category_content_key_lang">Key lang</label>
                                <?php
                                $categoryLangValue = (string) ($editingAppCategoryContent['key_lang'] ?? '');
                                $categoryAvailableLanguages = array_values(array_filter($languageOptions, static function (array $language) use ($appCategoryContentUsedLangKeys, $categoryLangValue): bool {
                                    $langKey = (string) ($language['lang_key'] ?? '');
                                    return $langKey === $categoryLangValue || !in_array($langKey, $appCategoryContentUsedLangKeys, true);
                                }));
                                if ($categoryLangValue === '') {
                                    $categoryLangValue = (string) ($categoryAvailableLanguages[0]['lang_key'] ?? '');
                                }
                                ?>
                                <select class="form-control js-app-content-lang-select" id="category_content_key_lang" name="key_lang" required>
                                    <?php if ($categoryLangValue === ''): ?>
                                        <option value=""></option>
                                    <?php endif; ?>
                                    <?= admin_language_select_options($categoryAvailableLanguages, $categoryLangValue) ?>
                                    <?php if ($categoryLangValue !== '' && !in_array($categoryLangValue, array_column($categoryAvailableLanguages, 'lang_key'), true)): ?>
                                        <option value="<?= htmlspecialchars($categoryLangValue) ?>" selected><?= htmlspecialchars($categoryLangValue) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <label class="form-label mb-0" for="category_content_title">Title</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-primary js-ai-category-request" type="button">
                                            <span class="d-inline-flex align-items-center gap-2"><i data-lucide="wand-sparkles" style="width:16px;height:16px"></i>Yêu cầu AI</span>
                                        </button>
                                        <button class="btn btn-sm btn-secondary d-none js-ai-category-generate" type="button">
                                            <span class="d-inline-flex align-items-center gap-2"><i data-lucide="sparkles" style="width:16px;height:16px"></i>Tạo bằng AI</span>
                                        </button>
                                    </div>
                                </div>
                                <input class="form-control" id="category_content_title" name="title" value="<?= htmlspecialchars($editingAppCategoryContent['title'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="category_content_description">Description</label>
                                <textarea class="form-control" id="category_content_description" name="description" rows="5"><?= htmlspecialchars($editingAppCategoryContent['description'] ?? '') ?></textarea>
                            </div>
                            <button class="btn <?= $editingAppCategoryContent ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editingAppCategoryContent ? 'Lưu cập nhật' : 'Lưu nội dung' ?></button>
                            <?php if ($editingAppCategoryContent): ?>
                                <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=apps&tab=categories&category_id=<?= urlencode((string) $editingAppCategoryContent['category_id']) ?>">Hủy sửa</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách chuyên mục</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-center">App</th>
                                    <th class="text-center">Lang</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($appCategoryRows as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($category['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($category['icon']) ?>" alt="" width="44" height="44" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($category['category_id']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $category['app_count'] ?></span></td>
                                        <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $category['content_count'] ?></span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm <?= $selectedCategoryId === $category['category_id'] ? 'btn-success' : 'btn-secondary' ?>" href="index.php?section=apps&tab=categories&category_id=<?= urlencode($category['category_id']) ?>" title="Xem nội dung" aria-label="Xem nội dung">
                                                    <i data-lucide="languages" style="width:16px;height:16px"></i>
                                                </a>
                                                <a class="btn btn-sm btn-warning" href="index.php?section=apps&tab=categories&category_edit=<?= urlencode($category['category_id']) ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa chuyên mục này?">
                                                    <input type="hidden" name="action" value="delete_app_category">
                                                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($category['category_id']) ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$appCategoryRows): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có chuyên mục.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($selectedCategoryId !== ''): ?>
                            <hr>
                            <h3 class="h6 mb-3">Nội dung: <?= htmlspecialchars($selectedCategoryId) ?></h3>
                            <div class="table-responsive-sm">
                                <table class="table table-striped table-hover table-sm align-middle">
                                    <thead>
                                    <tr>
                                        <th>Lang</th>
                                        <th>Title</th>
                                        <th>Cập nhật</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($appCategoryContentRows as $content): ?>
                                        <tr>
                                            <td class="font-monospace small"><?= htmlspecialchars($content['key_lang']) ?></td>
                                            <td><?= htmlspecialchars($content['title']) ?></td>
                                            <td><?= htmlspecialchars($content['updated_at'] ?? '') ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                    <a class="btn btn-sm btn-warning" href="index.php?section=apps&tab=categories&category_id=<?= urlencode($content['category_id']) ?>&category_content_edit=<?= (int) $content['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                        <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                    </a>
                                                    <form class="js-delete" method="post" data-confirm="Xóa nội dung chuyên mục này?">
                                                        <input type="hidden" name="action" value="delete_app_category_content">
                                                        <input type="hidden" name="id" value="<?= (int) $content['id'] ?>">
                                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$appCategoryContentRows): ?>
                                        <tr><td colspan="4" class="text-center muted-text py-4">Chuyên mục này chưa có nội dung.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
