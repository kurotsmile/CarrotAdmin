            <?php if ($section === 'ebook'): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link <?= $ebookTab === 'books' ? 'active' : '' ?>" href="index.php?section=ebook&tab=books">Ebook</a></li>
                <li class="nav-item"><a class="nav-link <?= $ebookTab === 'categories' ? 'active' : '' ?>" href="index.php?section=ebook&tab=categories">Chuyên mục</a></li>
                <li class="nav-item"><a class="nav-link <?= $ebookTab === 'stores' ? 'active' : '' ?>" href="index.php?section=ebook&tab=stores">Liên kết cửa hàng</a></li>
                <li class="nav-item"><a class="nav-link <?= $ebookTab === 'orders' ? 'active' : '' ?>" href="index.php?section=ebook&tab=orders">Đơn đặt hàng</a></li>
            </ul>

            <?php if ($ebookTab === 'books'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_ebook">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật ebook' : 'Thêm ebook' ?></h2>
                        <div class="row g-3">
                            <div class="col-md-5"><label class="form-label" for="ebook_id">ID</label><input class="form-control" id="ebook_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required></div>
                            <div class="col-md-7"><label class="form-label" for="ebook_name">Tên sách</label><input class="form-control" id="ebook_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required></div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-md-6"><label class="form-label" for="ebook_author">Tác giả</label><input class="form-control" id="ebook_author" name="author" value="<?= htmlspecialchars($editing['author'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label" for="ebook_category">Chuyên mục</label><select class="form-control js-ebook-category-select" id="ebook_category" name="category_id"><option value="">Không chọn</option><?php foreach ($ebookCategoryOptions as $category): ?><option value="<?= htmlspecialchars($category['id']) ?>" <?= (($editing['category_id'] ?? '') === $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name'] . ' (' . $category['id'] . ')') ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-md-8"><label class="form-label" for="ebook_user_id">User viết</label><select class="form-control js-user-select" id="ebook_user_id" name="user_id"><option value="">Không chọn</option><?php foreach ($ebookUserOptions as $userOption): ?><option value="<?= (int) $userOption['id'] ?>" <?= ((int) ($editing['user_id'] ?? 0) === (int) $userOption['id']) ? 'selected' : '' ?>><?= htmlspecialchars(trim((string) ($userOption['name'] ?? '')) . ' · ' . ($userOption['email'] ?? '') . ' (#' . (int) $userOption['id'] . ')') ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label" for="ebook_user">User field</label><input class="form-control" id="ebook_user" name="user" value="<?= htmlspecialchars($editing['user'] ?? '') ?>" placeholder="ID/email legacy"></div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-md-3"><label class="form-label" for="ebook_lang">Lang</label><input class="form-control" id="ebook_lang" name="lang" value="<?= htmlspecialchars($editing['lang'] ?? 'en') ?>"></div>
                            <div class="col-md-3"><label class="form-label" for="ebook_price">Giá</label><input class="form-control" id="ebook_price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($editing['price'] ?? '0.00')) ?>"></div>
                            <div class="col-md-3"><label class="form-label" for="ebook_currency">Tiền</label><input class="form-control" id="ebook_currency" name="currency" value="<?= htmlspecialchars($editing['currency'] ?? 'USD') ?>"></div>
                            <div class="col-md-3"><label class="form-label" for="ebook_status">Status</label><input class="form-control" id="ebook_status" name="status" value="<?= htmlspecialchars($editing['status'] ?? 'draft') ?>"></div>
                        </div>
                        <div class="form-check form-switch my-3">
                            <input class="form-check-input" id="ebook_is_free" name="is_free" type="checkbox" value="1" <?= !empty($editing['is_free']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ebook_is_free">Miễn phí</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="ebook_cover">Cover</label>
                            <div class="input-group"><input class="form-control" id="ebook_cover" name="cover" value="<?= htmlspecialchars($editing['cover'] ?? '') ?>"><button class="btn btn-secondary js-upload" type="button" data-target="ebook_cover" data-type-media="carrot_ebook_cover" data-mode="replace" data-accept="image/*">Upload</button></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="ebook_preview_file">File đọc / preview</label>
                            <div class="input-group"><input class="form-control" id="ebook_preview_file" name="preview_file" value="<?= htmlspecialchars($editing['preview_file'] ?? '') ?>"><button class="btn btn-secondary js-upload" type="button" data-target="ebook_preview_file" data-type-media="carrot_ebook_file" data-mode="replace" data-accept=".epub,application/epub+zip,.pdf,application/pdf,text/*">Upload</button></div>
                        </div>
                        <div class="mb-3"><label class="form-label" for="ebook_published_at">Published at</label><input class="form-control" id="ebook_published_at" name="published_at" value="<?= htmlspecialchars($editing['published_at'] ?? '') ?>"></div>
                        <div class="mb-3"><label class="form-label" for="ebook_description">Mô tả</label><textarea class="form-control" id="ebook_description" name="description" rows="7"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea></div>
                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu ebook</button>
                    </form>
                </div>
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách ebook</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>Sách</th><th>Chuyên mục</th><th>User</th><th>Giá</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($ebooks as $book): ?>
                                    <tr>
                                        <td><div class="d-flex align-items-center gap-2"><?php if (!empty($book['cover'])): ?><img src="<?= htmlspecialchars($book['cover']) ?>" alt="" style="width:42px;height:58px;object-fit:cover;border-radius:6px"><?php endif; ?><div><strong><?= htmlspecialchars($book['name'] ?? $book['id']) ?></strong><div class="small text-muted"><?= htmlspecialchars(($book['author'] ?? '') . ' · ' . ($book['id'] ?? '')) ?></div></div></div></td>
                                        <td><?= htmlspecialchars($book['category_name'] ?? $book['category_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($book['user_name'] ?? ($book['user'] ?? '-')) ?><div class="small text-muted"><?= htmlspecialchars($book['user_email'] ?? (!empty($book['user_id']) ? '#' . (int) $book['user_id'] : '')) ?></div></td>
                                        <td><?= !empty($book['is_free']) ? '<span class="badge text-bg-success">Free</span>' : htmlspecialchars(number_format((float) ($book['price'] ?? 0), 2) . ' ' . ($book['currency'] ?? 'USD')) ?></td>
                                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($book['status'] ?? '') ?></span></td>
                                        <td class="text-end"><a class="btn btn-sm btn-warning" href="index.php?section=ebook&tab=books&edit=<?= urlencode($book['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a> <form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_ebook"><input type="hidden" name="id" value="<?= htmlspecialchars($book['id']) ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$ebooks): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có ebook.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($ebookTab === 'categories'): ?>
            <div class="row g-4">
                <div class="col-xl-5"><form class="glass-panel p-4" method="post"><input type="hidden" name="action" value="save_ebook_category"><input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>"><h2 class="h5 mb-3"><?= $editing ? 'Cập nhật chuyên mục' : 'Thêm chuyên mục' ?></h2><div class="mb-3"><label class="form-label" for="category_id">ID</label><input class="form-control" id="category_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required></div><div class="mb-3"><label class="form-label" for="category_name">Tên</label><input class="form-control" id="category_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required></div><div class="mb-3"><label class="form-label" for="category_description">Mô tả</label><textarea class="form-control" id="category_description" name="description" rows="7"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea></div><button class="btn btn-success fw-bold w-100" type="submit">Lưu chuyên mục</button></form></div>
                <div class="col-xl-7"><div class="glass-panel p-4"><h2 class="h5 mb-3">Chuyên mục</h2><div class="table-responsive-sm"><table class="table table-striped table-hover table-sm align-middle"><thead><tr><th>ID</th><th>Tên</th><th>Sách</th><th></th></tr></thead><tbody><?php foreach ($ebookCategories as $category): ?><tr><td class="font-monospace small"><?= htmlspecialchars($category['id']) ?></td><td><strong><?= htmlspecialchars($category['name']) ?></strong><div class="small text-muted"><?= htmlspecialchars(mb_strimwidth((string) ($category['description'] ?? ''), 0, 110, '...')) ?></div></td><td><?= number_format((int) ($category['ebook_count'] ?? 0)) ?></td><td class="text-end"><a class="btn btn-sm btn-warning" href="index.php?section=ebook&tab=categories&edit=<?= urlencode($category['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a> <form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_ebook_category"><input type="hidden" name="id" value="<?= htmlspecialchars($category['id']) ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td></tr><?php endforeach; ?><?php if (!$ebookCategories): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có chuyên mục.</td></tr><?php endif; ?></tbody></table></div></div></div>
            </div>
            <?php endif; ?>

            <?php if ($ebookTab === 'stores'): ?>
            <div class="row g-4">
                <div class="col-xl-5"><form class="glass-panel p-4" method="post"><input type="hidden" name="action" value="save_ebook_store_link"><input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>"><h2 class="h5 mb-3"><?= $editing ? 'Cập nhật liên kết' : 'Thêm liên kết cửa hàng' ?></h2><div class="mb-3"><label class="form-label" for="store_link_id">ID</label><input class="form-control" id="store_link_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" placeholder="Tự tạo nếu bỏ trống"></div><div class="mb-3"><label class="form-label" for="store_ebook_id">Ebook</label><select class="form-control js-ebook-select" id="store_ebook_id" name="ebook_id" required><?php foreach ($ebooks as $book): ?><option value="<?= htmlspecialchars($book['id']) ?>" <?= (($editing['ebook_id'] ?? '') === $book['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($book['name'] ?? $book['id']) . ' (' . $book['id'] . ')') ?></option><?php endforeach; ?></select></div><div class="row g-3"><div class="col-md-6"><label class="form-label" for="store_id">Store ID</label><input class="form-control" id="store_id" name="store_id" value="<?= htmlspecialchars($editing['store_id'] ?? '') ?>" required></div><div class="col-md-6"><label class="form-label" for="store_name">Tên store</label><input class="form-control" id="store_name" name="store_name" value="<?= htmlspecialchars($editing['store_name'] ?? '') ?>" required></div></div><div class="mb-3 mt-3"><label class="form-label" for="store_icon">Icon</label><input class="form-control" id="store_icon" name="store_icon" value="<?= htmlspecialchars($editing['store_icon'] ?? '') ?>"></div><div class="mb-3"><label class="form-label" for="store_url">URL</label><input class="form-control" id="store_url" name="url" value="<?= htmlspecialchars($editing['url'] ?? '') ?>" required></div><div class="row g-3"><div class="col-md-6"><label class="form-label" for="sort_order">Thứ tự</label><input class="form-control" id="sort_order" name="sort_order" type="number" value="<?= htmlspecialchars((string) ($editing['sort_order'] ?? 0)) ?>"></div><div class="col-md-6"><div class="form-check form-switch mt-4 pt-2"><input class="form-check-input" id="is_primary" name="is_primary" type="checkbox" value="1" <?= !empty($editing['is_primary']) ? 'checked' : '' ?>><label class="form-check-label" for="is_primary">Primary</label></div></div></div><button class="btn btn-success fw-bold w-100 mt-3" type="submit">Lưu liên kết</button></form></div>
                <div class="col-xl-7"><div class="glass-panel p-4"><h2 class="h5 mb-3">Liên kết cửa hàng</h2><div class="table-responsive-sm"><table class="table table-striped table-hover table-sm align-middle"><thead><tr><th>Ebook</th><th>Store</th><th>URL</th><th></th></tr></thead><tbody><?php foreach ($ebookStoreLinks as $link): ?><tr><td><strong><?= htmlspecialchars($link['ebook_name'] ?? $link['ebook_id']) ?></strong><div class="small text-muted"><?= htmlspecialchars($link['ebook_id']) ?></div></td><td><?= !empty($link['is_primary']) ? '<span class="badge text-bg-success">Primary</span> ' : '' ?><?= htmlspecialchars($link['store_name']) ?><div class="small text-muted"><?= htmlspecialchars($link['store_id']) ?></div></td><td><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(mb_strimwidth((string) $link['url'], 0, 54, '...')) ?></a></td><td class="text-end"><a class="btn btn-sm btn-warning" href="index.php?section=ebook&tab=stores&edit=<?= urlencode($link['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a> <form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_ebook_store_link"><input type="hidden" name="id" value="<?= htmlspecialchars($link['id']) ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td></tr><?php endforeach; ?><?php if (!$ebookStoreLinks): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có liên kết.</td></tr><?php endif; ?></tbody></table></div></div></div>
            </div>
            <?php endif; ?>

            <?php if ($ebookTab === 'orders'): ?>
            <div class="glass-panel p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Đơn đặt hàng ebook</h2><form class="js-delete" method="post" data-confirm="Xóa tất cả đơn ebook chưa COMPLETED?"><input type="hidden" name="action" value="delete_failed_ebook_orders"><button class="btn btn-sm btn-outline-danger" type="submit">Xóa đơn lỗi</button></form></div>
                <div class="table-responsive-sm"><table class="table table-striped table-hover table-sm align-middle"><thead><tr><th>Đơn</th><th>Ebook</th><th>User</th><th>Trạng thái</th><th>Tiền</th><th>Ngày</th><th></th></tr></thead><tbody><?php foreach ($ebookOrders as $order): ?><tr><td class="font-monospace small"><?= htmlspecialchars($order['paypal_order_id'] ?? '') ?></td><td><div class="d-flex align-items-center gap-2"><?php if (!empty($order['ebook_cover'])): ?><img src="<?= htmlspecialchars($order['ebook_cover']) ?>" alt="" style="width:34px;height:46px;object-fit:cover;border-radius:6px"><?php endif; ?><div><strong><?= htmlspecialchars($order['ebook_name'] ?? $order['ebook_id']) ?></strong><div class="small text-muted"><?= htmlspecialchars($order['ebook_id']) ?></div></div></div></td><td><?= htmlspecialchars($order['user_name'] ?? '-') ?><div class="small text-muted"><?= htmlspecialchars($order['user_email'] ?? $order['payer_email'] ?? '') ?></div></td><td><span class="badge <?= ($order['status'] ?? '') === 'COMPLETED' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars($order['status'] ?? '') ?></span></td><td><?= htmlspecialchars(number_format((float) ($order['amount'] ?? 0), 2) . ' ' . ($order['currency'] ?? 'USD')) ?></td><td><div class="small"><?= htmlspecialchars($order['created_at'] ?? '') ?></div><div class="small text-muted"><?= htmlspecialchars($order['paid_at'] ?? '') ?></div></td><td class="text-end"><form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_ebook_order"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td></tr><?php endforeach; ?><?php if (!$ebookOrders): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có đơn ebook.</td></tr><?php endif; ?></tbody></table></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
