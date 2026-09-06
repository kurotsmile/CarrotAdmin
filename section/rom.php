            <?php if ($section === 'rom'): ?>
            <?php
            $selectedRomConsoleIds = $editing && $romTab === 'games' ? admin_fetch_rom_console_ids($pdo, (string) ($editing['id'] ?? '')) : [];
            if (!$selectedRomConsoleIds && $editing && $romTab === 'games' && !empty($editing['platform'])) {
                $selectedRomConsoleIds = admin_clean_rom_relation_ids(preg_split('/\s*,\s*/', (string) $editing['platform'], -1, PREG_SPLIT_NO_EMPTY) ?: []);
            }
            $selectedRomCategoryIds = $editing && $romTab === 'games' ? admin_fetch_rom_category_ids($pdo, (string) ($editing['id'] ?? '')) : [];
            if (!$selectedRomCategoryIds && $editing && $romTab === 'games' && !empty($editing['category'])) {
                $selectedRomCategoryIds = admin_clean_rom_relation_ids(preg_split('/\s*,\s*/', (string) $editing['category'], -1, PREG_SPLIT_NO_EMPTY) ?: []);
            }
            $romPublishedAtValue = (string) ($editing['published_at'] ?? '');
            if ($romTab === 'games' && !$editing && $romPublishedAtValue === '') {
                $romPublishedAtValue = date('Y-m-d');
            }
            $romConsoleNameMap = [];
            foreach ($romConsoles as $console) {
                $romConsoleNameMap[(string) ($console['id'] ?? '')] = (string) (($console['name'] ?? '') ?: ($console['id'] ?? ''));
            }
            $romFileRows = admin_rom_decode_files($editing['file_rom'] ?? '', $editing['file_size'] ?? '');
            foreach ($romFileRows as &$romFileRow) {
                $fileConsoleId = (string) ($romFileRow['console_id'] ?? '');
                if ($fileConsoleId === '' && count($selectedRomConsoleIds) === 1) {
                    $fileConsoleId = (string) $selectedRomConsoleIds[0];
                    $romFileRow['console_id'] = $fileConsoleId;
                }
                if (($romFileRow['console_name'] ?? '') === '' && $fileConsoleId !== '' && isset($romConsoleNameMap[$fileConsoleId])) {
                    $romFileRow['console_name'] = $romConsoleNameMap[$fileConsoleId];
                }
            }
            unset($romFileRow);
            ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link <?= $romTab === 'games' ? 'active' : '' ?>" href="index.php?section=rom&tab=games">ROM</a></li>
                <li class="nav-item"><a class="nav-link <?= $romTab === 'consoles' ? 'active' : '' ?>" href="index.php?section=rom&tab=consoles">Console</a></li>
                <li class="nav-item"><a class="nav-link <?= $romTab === 'categories' ? 'active' : '' ?>" href="index.php?section=rom&tab=categories">Category</a></li>
            </ul>

            <?php if ($romTab === 'games'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_rom">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật ROM' : 'Thêm ROM' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="rom_id">ID</label>
                                <div class="input-group">
                                    <input class="form-control" id="rom_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required>
                                    <button class="btn btn-outline-secondary js-copy-field" type="button" data-copy-target="#rom_id" title="Copy ID" aria-label="Copy ID">
                                        <i data-lucide="copy" style="width:16px;height:16px"></i>
                                    </button>
                                    <button class="btn btn-secondary" id="rom_generate_id" type="button" title="Tạo ID từ tên game">
                                        <i data-lucide="wand-sparkles" style="width:16px;height:16px"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="rom_name">Tên game</label>
                                <input class="form-control" id="rom_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="rom_console_ids">Hệ máy</label>
                                <select class="form-control js-rom-console-select" id="rom_console_ids" name="console_ids[]" multiple>
                                    <?php foreach ($romConsoles as $console): ?>
                                        <option value="<?= htmlspecialchars($console['id']) ?>" <?= in_array((string) $console['id'], $selectedRomConsoleIds, true) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(($console['name'] ?: $console['id']) . ' (' . $console['id'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php
                                    $knownConsoleIds = array_map(static fn(array $console): string => (string) ($console['id'] ?? ''), $romConsoles);
                                    foreach (array_diff($selectedRomConsoleIds, $knownConsoleIds) as $missingConsoleId):
                                    ?>
                                        <option value="<?= htmlspecialchars($missingConsoleId) ?>" selected><?= htmlspecialchars($missingConsoleId) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="rom_category_ids">Thể loại</label>
                                <select class="form-control js-rom-category-select" id="rom_category_ids" name="category_ids[]" multiple>
                                    <?php foreach ($romCategories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>" <?= in_array((string) $category['id'], $selectedRomCategoryIds, true) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(($category['name'] ?: $category['id']) . ' (' . $category['id'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php
                                    $knownCategoryIds = array_map(static fn(array $category): string => (string) ($category['id'] ?? ''), $romCategories);
                                    foreach (array_diff($selectedRomCategoryIds, $knownCategoryIds) as $missingCategoryId):
                                    ?>
                                        <option value="<?= htmlspecialchars($missingCategoryId) ?>" selected><?= htmlspecialchars($missingCategoryId) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-4"><label class="form-label" for="rom_emulator">Giả lập</label><input class="form-control" id="rom_emulator" name="emulator" value="<?= htmlspecialchars($editing['emulator'] ?? 'PPSSPP') ?>"></div>
                            <div class="col-md-4"><label class="form-label" for="rom_region">Region</label><input class="form-control" id="rom_region" name="region" value="<?= htmlspecialchars($editing['region'] ?? '') ?>" placeholder="USA"></div>
                            <div class="col-md-4">
                                <label class="form-label" for="rom_lang">Lang</label>
                                <select class="form-control js-country-select" id="rom_lang" name="lang">
                                    <?= admin_language_select_options($languageOptions, (string) ($editing['lang'] ?? 'en')) ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-4">
                                <label class="form-label" for="rom_status">Status</label>
                                <?php
                                $romStatusValue = (string) ($editing['status'] ?? 'draft');
                                $romStatusOptions = ['draft' => 'Draft', 'public' => 'Public', 'private' => 'Private', 'inactive' => 'Inactive'];
                                ?>
                                <select class="form-control js-rom-status-select" id="rom_status" name="status">
                                    <?php if ($romStatusValue !== '' && !array_key_exists($romStatusValue, $romStatusOptions)): ?>
                                        <option value="<?= htmlspecialchars($romStatusValue) ?>" selected><?= htmlspecialchars($romStatusValue) ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($romStatusOptions as $statusKey => $statusLabel): ?>
                                        <option value="<?= htmlspecialchars($statusKey) ?>" <?= $romStatusValue === $statusKey ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label" for="rom_sort_order">Thứ tự</label><input class="form-control" id="rom_sort_order" name="sort_order" type="number" value="<?= htmlspecialchars((string) ($editing['sort_order'] ?? 0)) ?>"></div>
                            <div class="col-md-4"><label class="form-label" for="rom_price">Giá USD</label><input class="form-control" id="rom_price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($editing['price'] ?? '0.00')) ?>"></div>
                        </div>

                        <div class="form-check form-switch my-3">
                            <input class="form-check-input" id="rom_is_free" name="is_free" type="checkbox" value="1" <?= !isset($editing['is_free']) || !empty($editing['is_free']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="rom_is_free">Miễn phí</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="rom_avatar">Avatar</label>
                            <div class="input-group"><input class="form-control" id="rom_avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>"><button class="btn btn-secondary js-upload" type="button" data-target="rom_avatar" data-type-media="carrot_rom_avatar" data-mode="replace" data-accept="image/*">Upload</button></div>
                        </div>

                        <?php
                        $romFormPhotos = admin_rom_decode_photos($editing['photos'] ?? '');
                        if (!$romFormPhotos) {
                            $romFormPhotos = [''];
                        }
                        ?>
                        <div class="mb-3 js-coc-photos-field" data-type-media="carrot_rom_photo">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label mb-0" for="rom_photos">Photos</label>
                                <button class="btn btn-sm btn-secondary js-coc-photo-add" type="button">
                                    <i data-lucide="plus" style="width:16px;height:16px"></i>
                                    Thêm ảnh
                                </button>
                            </div>
                            <textarea class="visually-hidden js-coc-photos-source" id="rom_photos" name="photos"><?= htmlspecialchars(implode("\n", admin_rom_decode_photos($editing['photos'] ?? ''))) ?></textarea>
                            <div class="vstack gap-2 js-coc-photos-list">
                                <?php foreach ($romFormPhotos as $photoUrl): ?>
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
                                            <button class="btn btn-secondary js-coc-photo-upload" type="button" data-type-media="carrot_rom_photo" data-accept="image/*" title="Upload ảnh" aria-label="Upload ảnh">
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

                        <div class="mb-3 js-rom-files-field" data-existing-files="<?= htmlspecialchars(json_encode($romFileRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                            <label class="form-label" for="rom_file_rom">File ROM</label>
                            <textarea class="visually-hidden js-rom-files-source" id="rom_file_rom" name="file_rom"><?= htmlspecialchars($editing['file_rom'] ?? '') ?></textarea>
                            <input type="hidden" id="rom_file_size" name="file_size" value="<?= htmlspecialchars($editing['file_size'] ?? '') ?>">
                            <div class="vstack gap-2 js-rom-files-list"></div>
                        </div>

                        <div class="mb-3"><label class="form-label" for="rom_published_at">Published at</label><input class="form-control" id="rom_published_at" name="published_at" type="date" value="<?= htmlspecialchars($romPublishedAtValue) ?>"></div>
                        <div class="mb-3"><label class="form-label" for="rom_description">Mô tả</label><textarea class="form-control" id="rom_description" name="description" rows="7"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea></div>
                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu ROM</button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách ROM</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>Game</th><th>Hệ máy</th><th>Giá</th><th>Status</th><th>File</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($roms as $rom): ?>
                                    <tr class="<?= (($editing['id'] ?? '') === ($rom['id'] ?? '')) ? 'admin-row-editing' : '' ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($rom['avatar'])): ?><img src="<?= htmlspecialchars($rom['avatar']) ?>" alt="" style="width:46px;height:46px;object-fit:cover;border-radius:8px"><?php endif; ?>
                                                <div><strong><?= htmlspecialchars($rom['name'] ?? $rom['id']) ?></strong><div class="small text-muted"><?= htmlspecialchars(($rom['category_names'] ?: $rom['category'] ?: '') . ' · ' . ($rom['id'] ?? '')) ?></div></div>
                                            </div>
                                        </td>
                                        <td><span class="badge text-bg-dark"><?= htmlspecialchars((string) ($rom['console_names'] ?: strtoupper((string) ($rom['platform'] ?? '')))) ?></span><div class="small text-muted"><?= htmlspecialchars($rom['emulator'] ?? '') ?></div></td>
                                        <td><?= !empty($rom['is_free']) ? '<span class="badge text-bg-success">Free</span>' : htmlspecialchars(number_format((float) ($rom['price'] ?? 0), 2) . ' USD') ?></td>
                                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($rom['status'] ?? '') ?></span></td>
                                        <?php $romFirstFile = admin_rom_first_file($rom['file_rom'] ?? '', $rom['file_size'] ?? ''); ?>
                                        <td><?php if (!empty($romFirstFile['url'])): ?><a href="<?= htmlspecialchars($romFirstFile['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($romFirstFile['file_size'] ?: 'Download') ?></a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                        <td class="text-end"><a class="btn btn-sm btn-warning" href="index.php?section=rom&tab=games&edit=<?= urlencode($rom['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a> <form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_rom"><input type="hidden" name="id" value="<?= htmlspecialchars($rom['id']) ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$roms): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có ROM.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($romTab === 'consoles'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_rom_console">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật hệ máy' : 'Thêm hệ máy' ?></h2>
                        <div class="mb-3"><label class="form-label" for="rom_console_id">ID</label><input class="form-control" id="rom_console_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="rom_console_name">Tên</label><input class="form-control" id="rom_console_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="rom_console_emulator">Giả lập mặc định</label><input class="form-control" id="rom_console_emulator" name="emulator" value="<?= htmlspecialchars($editing['emulator'] ?? '') ?>"></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="rom_console_sort_order">Thứ tự</label><input class="form-control" id="rom_console_sort_order" name="sort_order" type="number" value="<?= htmlspecialchars((string) ($editing['sort_order'] ?? 0)) ?>"></div>
                            <div class="col-md-6"><label class="form-label" for="rom_console_status">Status</label><input class="form-control" id="rom_console_status" name="status" value="<?= htmlspecialchars($editing['status'] ?? 'active') ?>"></div>
                        </div>
                        <button class="btn btn-success fw-bold w-100 mt-3" type="submit">Lưu hệ máy</button>
                    </form>
                </div>
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Console</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>ID</th><th>Tên</th><th>Giả lập</th><th>ROM</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($romConsoles as $console): ?>
                                    <tr class="<?= (($editing['id'] ?? '') === ($console['id'] ?? '')) ? 'admin-row-editing' : '' ?>">
                                        <td class="font-monospace small"><?= htmlspecialchars($console['id']) ?></td>
                                        <td><strong><?= htmlspecialchars($console['name']) ?></strong><div class="small text-muted">Thứ tự <?= number_format((int) ($console['sort_order'] ?? 0)) ?></div></td>
                                        <td><?= htmlspecialchars($console['emulator'] ?? '') ?></td>
                                        <td><?= number_format((int) ($console['rom_count'] ?? 0)) ?></td>
                                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($console['status'] ?? '') ?></span></td>
                                        <td class="text-end"><a class="btn btn-sm btn-warning" href="index.php?section=rom&tab=consoles&edit=<?= urlencode($console['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a> <form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_rom_console"><input type="hidden" name="id" value="<?= htmlspecialchars($console['id']) ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$romConsoles): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có hệ máy.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($romTab === 'categories'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_rom_category">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật thể loại' : 'Thêm thể loại' ?></h2>
                        <div class="mb-3"><label class="form-label" for="rom_category_id">ID</label><input class="form-control" id="rom_category_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="rom_category_name">Tên</label><input class="form-control" id="rom_category_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="rom_category_description">Mô tả</label><textarea class="form-control" id="rom_category_description" name="description" rows="7"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="rom_category_sort_order">Thứ tự</label><input class="form-control" id="rom_category_sort_order" name="sort_order" type="number" value="<?= htmlspecialchars((string) ($editing['sort_order'] ?? 0)) ?>"></div>
                            <div class="col-md-6"><label class="form-label" for="rom_category_status">Status</label><input class="form-control" id="rom_category_status" name="status" value="<?= htmlspecialchars($editing['status'] ?? 'active') ?>"></div>
                        </div>
                        <button class="btn btn-success fw-bold w-100 mt-3" type="submit">Lưu thể loại</button>
                    </form>
                </div>
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Category</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>ID</th><th>Tên</th><th>ROM</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($romCategories as $category): ?>
                                    <tr class="<?= (($editing['id'] ?? '') === ($category['id'] ?? '')) ? 'admin-row-editing' : '' ?>">
                                        <td class="font-monospace small"><?= htmlspecialchars($category['id']) ?></td>
                                        <td><strong><?= htmlspecialchars($category['name']) ?></strong><div class="small text-muted"><?= htmlspecialchars(mb_strimwidth((string) ($category['description'] ?? ''), 0, 110, '...')) ?></div></td>
                                        <td><?= number_format((int) ($category['rom_count'] ?? 0)) ?></td>
                                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($category['status'] ?? '') ?></span></td>
                                        <td class="text-end"><a class="btn btn-sm btn-warning" href="index.php?section=rom&tab=categories&edit=<?= urlencode($category['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a> <form class="d-inline js-delete" method="post"><input type="hidden" name="action" value="delete_rom_category"><input type="hidden" name="id" value="<?= htmlspecialchars($category['id']) ?>"><button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$romCategories): ?><tr><td colspan="5" class="text-center text-muted py-4">Chưa có thể loại.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($romTab === 'games'): ?>
            <script>
            const romSlugify = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[đĐ]/g, 'd')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');
            const romEscapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

            const romGenerateIdButton = document.getElementById('rom_generate_id');
            if (romGenerateIdButton) {
                romGenerateIdButton.addEventListener('click', () => {
                    const idInput = document.getElementById('rom_id');
                    const nameInput = document.getElementById('rom_name');
                    const slug = romSlugify(nameInput?.value || '');
                    if (idInput && slug) {
                        idInput.value = slug;
                    }
                });
            }

            const romFilesField = document.querySelector('.js-rom-files-field');
            const romConsoleSelect = document.getElementById('rom_console_ids');
            if (romFilesField && romConsoleSelect) {
                const romFilesList = romFilesField.querySelector('.js-rom-files-list');
                const romFilesSource = romFilesField.querySelector('.js-rom-files-source');
                const romFileSizeInput = document.getElementById('rom_file_size');
                const romFileAccept = '.zip,.7z,.rar,.iso,.bin,.cue,.pbp,.cso,.chd,.pkg,.rom,.nes,.sfc,.gba,.gbc,.apk';
                let romFileStore = {};

                try {
                    const existingFiles = JSON.parse(romFilesField.dataset.existingFiles || '[]');
                    if (Array.isArray(existingFiles)) {
                        existingFiles.forEach((file, index) => {
                            const consoleId = String(file.console_id || `legacy-${index}`);
                            romFileStore[consoleId] = {
                                console_id: String(file.console_id || ''),
                                console_name: String(file.console_name || ''),
                                url: String(file.url || ''),
                                file_size: String(file.file_size || ''),
                            };
                        });
                    }
                } catch (error) {
                    romFileStore = {};
                }

                const selectedRomConsoles = () => Array.from(romConsoleSelect.selectedOptions || []).map((option) => ({
                    id: String(option.value || '').trim(),
                    name: String(option.textContent || option.value || '').replace(/\s*\([^)]*\)\s*$/, '').trim(),
                })).filter((item) => item.id !== '');

                const syncRomFileStoreFromDom = () => {
                    romFilesList.querySelectorAll('.js-rom-file-item').forEach((item) => {
                        const consoleId = item.dataset.consoleId || '';
                        if (!consoleId) {
                            return;
                        }
                        romFileStore[consoleId] = {
                            console_id: consoleId,
                            console_name: item.dataset.consoleName || consoleId,
                            url: item.querySelector('.js-rom-file-url')?.value.trim() || '',
                            file_size: item.querySelector('.js-rom-file-size')?.value.trim() || '',
                        };
                    });
                };

                const syncRomFilesSource = () => {
                    syncRomFileStoreFromDom();
                    const selectedIds = selectedRomConsoles().map((item) => item.id);
                    const files = selectedIds.map((consoleId) => romFileStore[consoleId]).filter(Boolean);
                    if (romFilesSource) {
                        romFilesSource.value = JSON.stringify(files);
                    }
                    if (romFileSizeInput) {
                        const firstSize = (files.find((file) => file.file_size)?.file_size || '').trim();
                        romFileSizeInput.value = firstSize;
                    }
                };

                const createRomFileItem = (consoleItem, index) => {
                    const storedFile = romFileStore[consoleItem.id] || {
                        console_id: consoleItem.id,
                        console_name: consoleItem.name,
                        url: '',
                        file_size: '',
                    };
                    storedFile.console_id = consoleItem.id;
                    storedFile.console_name = consoleItem.name || storedFile.console_name || consoleItem.id;
                    romFileStore[consoleItem.id] = storedFile;

                    const wrapper = document.createElement('div');
                    wrapper.className = 'rom-file-item js-rom-file-item';
                    wrapper.dataset.consoleId = consoleItem.id;
                    wrapper.dataset.consoleName = storedFile.console_name;
                    wrapper.innerHTML = `
                        <div class="rom-file-title">
                            <strong>${romEscapeHtml(storedFile.console_name)}</strong>
                            <span class="badge text-bg-dark">${romEscapeHtml(consoleItem.id)}</span>
                        </div>
                        <input type="hidden" name="rom_files[${index}][console_id]" value="${romEscapeHtml(consoleItem.id)}">
                        <input type="hidden" name="rom_files[${index}][console_name]" value="${romEscapeHtml(storedFile.console_name)}">
                        <div class="input-group mb-2">
                            <input class="form-control js-rom-file-url" name="rom_files[${index}][url]" value="${romEscapeHtml(storedFile.url)}" placeholder="File ROM URL">
                            <button class="btn btn-secondary js-rom-file-upload" type="button" data-type-media="carrot_rom_file" data-accept="${romFileAccept}" title="Upload file ROM" aria-label="Upload file ROM"><i data-lucide="upload" style="width:16px;height:16px"></i></button>
                        </div>
                        <input class="form-control js-rom-file-size" name="rom_files[${index}][file_size]" value="${romEscapeHtml(storedFile.file_size)}" placeholder="Dung lượng, ví dụ 1.4 GB">
                    `;
                    return wrapper;
                };

                const renderRomFileItems = () => {
                    syncRomFileStoreFromDom();
                    const consoles = selectedRomConsoles();
                    romFilesList.innerHTML = '';
                    if (!consoles.length) {
                        romFilesList.innerHTML = '<div class="text-muted small border rounded-2 p-3 bg-light">Chọn Hệ máy để nhập File ROM tương ứng.</div>';
                        syncRomFilesSource();
                        return;
                    }
                    consoles.forEach((consoleItem, index) => romFilesList.appendChild(createRomFileItem(consoleItem, index)));
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                    syncRomFilesSource();
                };

                romFilesList.addEventListener('input', (event) => {
                    if (event.target.matches('.js-rom-file-url, .js-rom-file-size')) {
                        syncRomFilesSource();
                    }
                });
                romFilesList.addEventListener('change', (event) => {
                    if (event.target.matches('.js-rom-file-url, .js-rom-file-size')) {
                        syncRomFilesSource();
                    }
                });
                romFilesList.addEventListener('click', async (event) => {
                    const uploadButton = event.target.closest('.js-rom-file-upload');
                    if (!uploadButton) {
                        return;
                    }
                    let uploadedUrl = '';
                    try {
                        uploadedUrl = await adminOpenUploadDialog(uploadButton);
                    } catch (error) {
                        await Swal.fire({icon: 'warning', title: 'Upload thất bại', text: error.message || 'Không upload được file ROM.'});
                        return;
                    }
                    if (!uploadedUrl) {
                        return;
                    }
                    const item = uploadButton.closest('.js-rom-file-item');
                    const urlInput = item?.querySelector('.js-rom-file-url');
                    if (urlInput) {
                        urlInput.value = uploadedUrl;
                        syncRomFilesSource();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã upload',
                        text: uploadedUrl,
                        timer: 1600,
                        showConfirmButton: false,
                    });
                });

                romConsoleSelect.addEventListener('change', renderRomFileItems);
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(romConsoleSelect).on('select2:select select2:unselect', renderRomFileItems);
                }
                renderRomFileItems();
            }
            </script>
            <?php endif; ?>
            <?php endif; ?>
