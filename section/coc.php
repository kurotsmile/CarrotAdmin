            <?php if ($section === 'coc'): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $cocTab === 'accounts' ? 'active' : '' ?>" href="index.php?section=coc&tab=accounts">Các Tài khoản</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $cocTab === 'orders' ? 'active' : '' ?>" href="index.php?section=coc&tab=orders">Đơn Đặt hàng</a>
                </li>
            </ul>

            <?php if ($cocTab === 'accounts'): ?>
            <?php
            $cocSearch = trim($_GET['coc_q'] ?? '');
            $cocPage = max(1, (int) ($_GET['coc_page'] ?? 1));
            $cocPerPage = 25;
            $cocTotal = 0;
            $cocTotalPages = 1;
            if ($pdo instanceof PDO) {
                $cocSearchWhere = '';
                $cocSearchParams = [];
                if ($cocSearch !== '') {
                    $cocSearchWhere = ' WHERE CAST(id AS CHAR) LIKE :coc_id_q OR name LIKE :coc_name_q OR username LIKE :coc_username_q OR CAST(hall AS CHAR) LIKE :coc_hall_q';
                    $cocSearchValue = '%' . $cocSearch . '%';
                    $cocSearchParams = [
                        ':coc_id_q' => $cocSearchValue,
                        ':coc_name_q' => $cocSearchValue,
                        ':coc_username_q' => $cocSearchValue,
                        ':coc_hall_q' => $cocSearchValue,
                    ];
                }

                $cocCountStmt = $pdo->prepare('SELECT COUNT(*) FROM coc' . $cocSearchWhere);
                $cocCountStmt->execute($cocSearchParams);
                $cocTotal = (int) $cocCountStmt->fetchColumn();
                $cocTotalPages = max(1, (int) ceil($cocTotal / $cocPerPage));
                $cocPage = min($cocPage, $cocTotalPages);
                $cocOffset = ($cocPage - 1) * $cocPerPage;
                $cocStmt = $pdo->prepare('SELECT * FROM coc' . $cocSearchWhere . ' ORDER BY ' . admin_order_by($accountSortColumns, $accountSort, $accountDir) . ' LIMIT :limit OFFSET :offset');
                foreach ($cocSearchParams as $paramKey => $paramValue) {
                    $cocStmt->bindValue($paramKey, $paramValue);
                }
                $cocStmt->bindValue(':limit', $cocPerPage, PDO::PARAM_INT);
                $cocStmt->bindValue(':offset', $cocOffset, PDO::PARAM_INT);
                $cocStmt->execute();
                $accounts = $cocStmt->fetchAll();
            }
            ?>
            <?php
            $cocFormPhotos = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $photoText))));
            if (!$cocFormPhotos) {
                $cocFormPhotos = [''];
            }
            ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật acc' : 'Thêm acc mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="price">Giá USD</label>
                                <input class="form-control" id="price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($editing['price'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="hall">Town Hall</label>
                                <input class="form-control" id="hall" name="hall" type="number" min="1" max="99" step="1" value="<?= htmlspecialchars((string) ($editing ? coc_account_hall($editing) : '')) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="avatar">Avatar URL</label>
                            <div class="input-group">
                                <input class="form-control" id="avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="avatar" data-type-media="coc_images" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" id="username" name="username" value="<?= htmlspecialchars($editing['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control" id="password" name="password" value="<?= htmlspecialchars($editing['password'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3 js-coc-photos-field">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label mb-0" for="photos">Photos</label>
                                <button class="btn btn-sm btn-secondary js-coc-photo-add" type="button">
                                    <i data-lucide="plus" style="width:16px;height:16px"></i>
                                    Thêm ảnh
                                </button>
                            </div>
                            <textarea class="visually-hidden js-coc-photos-source" id="photos" name="photos"><?= htmlspecialchars($photoText) ?></textarea>
                            <div class="vstack gap-2 js-coc-photos-list">
                                <?php foreach ($cocFormPhotos as $photoUrl): ?>
                                    <div class="coc-photo-item js-coc-photo-item">
                                        <div class="coc-photo-preview">
                                            <?php if ($photoUrl !== ''): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="">
                                            <?php else: ?>
                                                <i data-lucide="image" style="width:22px;height:22px"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="input-group">
                                            <input class="form-control js-coc-photo-url" value="<?= htmlspecialchars($photoUrl) ?>" placeholder="Image URL">
                                            <button class="btn btn-secondary js-coc-photo-upload" type="button" data-type-media="coc_images" data-accept="image/*" title="Upload ảnh" aria-label="Upload ảnh">
                                                <i data-lucide="upload" style="width:16px;height:16px"></i>
                                            </button>
                                            <button class="btn btn-outline-danger js-coc-photo-remove" type="button" title="Xóa item ảnh" aria-label="Xóa item ảnh">
                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="data">Data JSON Supercell</label>
                            <textarea class="form-control font-monospace small" id="data" name="data" rows="12" required><?= htmlspecialchars($editing['data'] ?? '') ?></textarea>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm acc' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Danh sách acc</h2>
                            <form class="d-flex gap-2" method="get">
                                <input type="hidden" name="section" value="coc">
                                <input type="hidden" name="tab" value="accounts">
                                <input class="form-control form-control-sm" name="coc_q" value="<?= htmlspecialchars($cocSearch) ?>" placeholder="Search acc">
                                <button class="btn btn-sm btn-secondary" type="submit" title="Search" aria-label="Search">
                                    <i data-lucide="search" style="width:16px;height:16px"></i>
                                </button>
                                <?php if ($cocSearch !== ''): ?>
                                    <a class="btn btn-sm btn-light" href="index.php?section=coc&tab=accounts" title="Clear" aria-label="Clear">
                                        <i data-lucide="x" style="width:16px;height:16px"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="muted-text small">
                                <?= number_format($cocTotal) ?> acc
                                <?php if ($cocSearch !== ''): ?>
                                    cho từ khóa "<?= htmlspecialchars($cocSearch) ?>"
                                <?php endif; ?>
                            </div>
                            <div class="muted-text small">Trang <?= number_format($cocPage) ?>/<?= number_format($cocTotalPages) ?></div>
                        </div>
                        <?php
                        $cocPageParams = $_GET;
                        unset($cocPageParams['edit']);
                        $cocPageParams['section'] = 'coc';
                        $cocPageParams['tab'] = 'accounts';
                        ?>
                        <?= admin_pagination($cocPageParams, 'coc_page', $cocPage, $cocTotalPages, 'Phân trang acc', 'mb-3') ?>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Acc', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('hall', 'Town Hall', $accountSort, $accountDir) ?></th>
                                    <th><?= admin_sort_link('price', 'Giá', $accountSort, $accountDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($accounts as $account):
                                    $th = coc_account_hall($account);
                                    $isEditingAccount = $editing && (int) $editing['id'] === (int) $account['id'];
                                    ?>
                                    <tr class="<?= $isEditingAccount ? 'coc-account-row-editing' : '' ?>">
                                        <td><?= (int) $account['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="https://coc.carrot28.com/account.php?id=<?php echo htmlspecialchars($account['id']); ?>" target="_blank" rel="noopener noreferrer">
                                                    <img src="<?= htmlspecialchars($account['avatar']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                </a>
                                                <div>
                                                    <strong><?= htmlspecialchars($account['name']) ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars($account['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $th ?: 'N/A' ?></td>
                                        <td><?= coc_money($account['price']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=coc&tab=accounts&edit=<?= (int) $account['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa acc này?">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$accounts): ?>
                                    <tr><td colspan="5" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= admin_pagination($cocPageParams, 'coc_page', $cocPage, $cocTotalPages, 'Phân trang acc') ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($cocTab === 'orders'): ?>
            <div class="glass-panel p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0">Đơn Đặt hàng</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge text-bg-secondary"><?= number_format(count($orders)) ?> đơn</span>
                        <form class="js-delete d-inline-flex" method="post" data-confirm="Xóa tất cả đơn COC thanh toán không thành công? Các đơn COMPLETED sẽ được giữ lại.">
                            <input type="hidden" name="action" value="delete_failed_coc_orders">
                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                Xóa đơn lỗi
                            </button>
                        </form>
                    </div>
                </div>
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead>
                        <tr>
                            <th><?= admin_sort_link('id', 'ID', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('account', 'Acc', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('paypal_order_id', 'PayPal Order', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('status', 'Status', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('amount', 'Amount', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('payer_email', 'Payer', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('created_at', 'Created', $orderSort, $orderDir) ?></th>
                            <th><?= admin_sort_link('paid_at', 'Paid', $orderSort, $orderDir) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= (int) $order['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($order['coc_name'] ?? ('#' . $order['coc_id'])) ?></strong>
                                    <div class="muted-text small"><?= htmlspecialchars($order['coc_username'] ?? '') ?></div>
                                </td>
                                <td class="font-monospace small"><?= htmlspecialchars($order['paypal_order_id']) ?></td>
                                <td><?= htmlspecialchars($order['status']) ?></td>
                                <td><?= coc_money($order['amount']) ?></td>
                                <td><?= htmlspecialchars($order['payer_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td><?= htmlspecialchars($order['paid_at'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$orders): ?>
                            <tr><td colspan="8" class="text-center muted-text py-4">Chưa có đơn đặt hàng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
