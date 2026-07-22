            <?php if ($section === 'pages'): ?>
            <?php
            $pageSearch = trim($_GET['page_q'] ?? '');
            $selectedPageSlug = trim($_GET['slug'] ?? '');
            if ($selectedPageSlug === '' && $editing && !empty($editing['slug'])) {
                $selectedPageSlug = (string) $editing['slug'];
            }

            $pageListPage = max(1, (int) ($_GET['page_page'] ?? 1));
            $pagePerPage = 25;
            $pageTotal = 0;
            $pageTotalPages = 1;
            $pageSlugGroups = [];
            $selectedSlugPages = [];
            $selectedSlugLangs = [];
            $selectedSlugStats = ['page_count' => 0, 'langs' => '', 'latest_update' => null];

            if ($homePdo instanceof PDO) {
                $pageSearchHaving = '';
                $pageSearchParams = [];
                if ($pageSearch !== '') {
                    $pageSearchHaving = ' HAVING slug LIKE :page_slug_q OR searchable_text LIKE :page_text_q OR langs LIKE :page_lang_q';
                    $pageSearchValue = '%' . $pageSearch . '%';
                    $pageSearchParams = [
                        ':page_slug_q' => $pageSearchValue,
                        ':page_text_q' => $pageSearchValue,
                        ':page_lang_q' => $pageSearchValue,
                    ];
                }

                $pageGroupSql = '
                    SELECT slug, COUNT(*) AS page_count, GROUP_CONCAT(DISTINCT lang ORDER BY lang SEPARATOR ", ") AS langs,
                           MAX(updated_at) AS latest_update,
                           CONCAT_WS(" ", GROUP_CONCAT(title SEPARATOR " "), GROUP_CONCAT(seo_title SEPARATOR " "), GROUP_CONCAT(seo_description SEPARATOR " ")) AS searchable_text
                    FROM page
                    GROUP BY slug
                ';
                $pageCountStmt = $homePdo->prepare('SELECT COUNT(*) FROM (' . $pageGroupSql . $pageSearchHaving . ') page_groups');
                $pageCountStmt->execute($pageSearchParams);
                $pageTotal = (int) $pageCountStmt->fetchColumn();
                $pageTotalPages = max(1, (int) ceil($pageTotal / $pagePerPage));
                $pageListPage = min($pageListPage, $pageTotalPages);
                $pageOffset = ($pageListPage - 1) * $pagePerPage;

                $pageGroupStmt = $homePdo->prepare($pageGroupSql . $pageSearchHaving . ' ORDER BY latest_update DESC, slug ASC LIMIT :limit OFFSET :offset');
                foreach ($pageSearchParams as $paramKey => $paramValue) {
                    $pageGroupStmt->bindValue($paramKey, $paramValue);
                }
                $pageGroupStmt->bindValue(':limit', $pagePerPage, PDO::PARAM_INT);
                $pageGroupStmt->bindValue(':offset', $pageOffset, PDO::PARAM_INT);
                $pageGroupStmt->execute();
                $pageSlugGroups = $pageGroupStmt->fetchAll();

                if ($selectedPageSlug !== '') {
                    $selectedStatsStmt = $homePdo->prepare('
                        SELECT COUNT(*) AS page_count, GROUP_CONCAT(DISTINCT lang ORDER BY lang SEPARATOR ", ") AS langs, MAX(updated_at) AS latest_update
                        FROM page
                        WHERE slug = ?
                    ');
                    $selectedStatsStmt->execute([$selectedPageSlug]);
                    $selectedSlugStats = $selectedStatsStmt->fetch() ?: $selectedSlugStats;

                    $selectedPagesStmt = $homePdo->prepare('SELECT * FROM page WHERE slug = ? ORDER BY lang ASC, id DESC');
                    $selectedPagesStmt->execute([$selectedPageSlug]);
                    $selectedSlugPages = $selectedPagesStmt->fetchAll();
                    $selectedSlugLangs = array_values(array_unique(array_filter(array_map(static fn(array $page): string => (string) ($page['lang'] ?? ''), $selectedSlugPages))));
                }
            }
            ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="glass-panel p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Danh sách slug</h2>
                            <form class="d-flex gap-2" method="get">
                                <input type="hidden" name="section" value="pages">
                                <input class="form-control form-control-sm" name="page_q" value="<?= htmlspecialchars($pageSearch) ?>" placeholder="Tìm slug, title, lang">
                                <button class="btn btn-sm btn-secondary" type="submit" title="Search" aria-label="Search">
                                    <i data-lucide="search" style="width:16px;height:16px"></i>
                                </button>
                                <?php if ($pageSearch !== ''): ?>
                                    <a class="btn btn-sm btn-light" href="index.php?section=pages" title="Clear" aria-label="Clear">
                                        <i data-lucide="x" style="width:16px;height:16px"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="muted-text small"><?= number_format($pageTotal) ?> slug<?= $pageSearch !== '' ? ' phù hợp' : '' ?></div>
                            <div class="muted-text small">Trang <?= number_format($pageListPage) ?>/<?= number_format($pageTotalPages) ?></div>
                        </div>
                        <?php
                        $pagePageParams = $_GET;
                        unset($pagePageParams['edit']);
                        $pagePageParams['section'] = 'pages';
                        ?>
                        <?= admin_pagination($pagePageParams, 'page_page', $pageListPage, $pageTotalPages, 'Phân trang slug', 'mb-3') ?>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Slug</th>
                                    <th>Page</th>
                                    <th>Lang</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pageSlugGroups as $group): ?>
                                    <?php $groupSlug = (string) $group['slug']; ?>
                                    <tr class="<?= $groupSlug === $selectedPageSlug ? 'table-success' : '' ?>">
                                        <td>
                                            <a class="fw-bold font-monospace text-decoration-none" href="index.php?section=pages&slug=<?= urlencode($groupSlug) ?>">
                                                <?= htmlspecialchars($groupSlug) ?>
                                            </a>
                                            <?php if (!empty($group['latest_update'])): ?>
                                                <div class="muted-text small">Update: <?= htmlspecialchars((string) $group['latest_update']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge text-bg-secondary"><?= number_format((int) $group['page_count']) ?></span></td>
                                        <td class="small"><?= htmlspecialchars((string) $group['langs']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=pages&slug=<?= urlencode($groupSlug) ?>" title="Quản lý slug" aria-label="Quản lý slug">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa toàn bộ <?= number_format((int) $group['page_count']) ?> page thuộc slug <?= htmlspecialchars($groupSlug) ?>?">
                                                    <input type="hidden" name="action" value="delete_page_group">
                                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($groupSlug) ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa slug" aria-label="Xóa slug">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$pageSlugGroups): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= admin_pagination($pagePageParams, 'page_page', $pageListPage, $pageTotalPages, 'Phân trang slug') ?>
                    </div>
                </div>

                <div class="col-xl-7">
                    <form class="glass-panel p-4 mb-4" method="post">
                        <input type="hidden" name="action" value="save_page">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật page' : 'Thêm page mới' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="page_title">Title</label>
                                <input class="form-control" id="page_title" name="title" value="<?= htmlspecialchars($editing['title'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="page_lang">Lang key</label>
                                <?php $pageLangValue = (string) ($editing['lang'] ?? 'vi'); ?>
                                <select class="form-control js-page-lang-select" id="page_lang" name="lang" required>
                                    <option value="">Chọn lang</option>
                                    <?php
                                    $pageLangFound = $pageLangValue === '';
                                    foreach ($languageOptions as $pageLanguageOption):
                                        $pageLangKey = (string) ($pageLanguageOption['lang_key'] ?? '');
                                        if ($pageLangKey === '') {
                                            continue;
                                        }
                                        $pageLangSelected = $pageLangKey === $pageLangValue;
                                        $pageLangFound = $pageLangFound || $pageLangSelected;
                                        $pageLangDisabled = in_array($pageLangKey, $selectedSlugLangs, true) && !$pageLangSelected;
                                        $pageLangLabelParts = [$pageLangKey];
                                        if (!empty($pageLanguageOption['countries'])) {
                                            $pageLangLabelParts[] = (string) $pageLanguageOption['countries'];
                                        } elseif (!empty($pageLanguageOption['name'])) {
                                            $pageLangLabelParts[] = (string) $pageLanguageOption['name'];
                                        } elseif (!empty($pageLanguageOption['lang_country'])) {
                                            $pageLangLabelParts[] = (string) $pageLanguageOption['lang_country'];
                                        }
                                        if ($pageLangDisabled) {
                                            $pageLangLabelParts[] = 'đã có page';
                                        }
                                    ?>
                                        <option value="<?= htmlspecialchars($pageLangKey) ?>" <?= $pageLangSelected ? 'selected' : '' ?> <?= $pageLangDisabled ? 'disabled' : '' ?>><?= htmlspecialchars(implode(' - ', $pageLangLabelParts)) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!$pageLangFound): ?>
                                        <option value="<?= htmlspecialchars($pageLangValue) ?>" selected><?= htmlspecialchars($pageLangValue . ' - Giá trị hiện tại') ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-12">
                                <label class="form-label" for="page_slug">Slug</label>
                                <?php $pageSlugValue = (string) ($editing['slug'] ?? $selectedPageSlug); ?>
                                <select class="form-control font-monospace js-page-slug-select" id="page_slug" name="slug" required>
                                    <?php if ($pageSlugValue === ''): ?>
                                        <option value=""></option>
                                    <?php endif; ?>
                                    <?php if ($pageSlugValue !== '' && !in_array($pageSlugValue, $pageSlugOptions, true)): ?>
                                        <option value="<?= htmlspecialchars($pageSlugValue) ?>" selected><?= htmlspecialchars($pageSlugValue) ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($pageSlugOptions as $pageSlugOption): ?>
                                        <option value="<?= htmlspecialchars($pageSlugOption) ?>" <?= $pageSlugOption === $pageSlugValue ? 'selected' : '' ?>><?= htmlspecialchars($pageSlugOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <label class="form-label mb-0" for="page_content_html">HTML content</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-primary js-ai-page-request" type="button">
                                        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="wand-sparkles" style="width:16px;height:16px"></i>Yêu cầu AI</span>
                                    </button>
                                    <button class="btn btn-sm btn-secondary d-none js-ai-page-generate" type="button">
                                        <span class="d-inline-flex align-items-center gap-2"><i data-lucide="sparkles" style="width:16px;height:16px"></i>Tạo bằng AI</span>
                                    </button>
                                </div>
                            </div>
                            <div class="simple-editor-toolbar" aria-label="Editor toolbar">
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="bold"><strong>B</strong></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="italic"><em>I</em></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="formatBlock" data-editor-value="h2">H2</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="formatBlock" data-editor-value="p">P</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="insertUnorderedList">List</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="createLink">Link</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-command="removeFormat">Clear</button>
                            </div>
                            <div class="simple-editor-canvas" id="page_content_editor" contenteditable="true"><?= $editing['content_html'] ?? '<p></p>' ?></div>
                            <textarea class="form-control font-monospace d-none" id="page_content_html" name="content_html" required><?= htmlspecialchars($editing['content_html'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="seo_title">SEO title</label>
                            <input class="form-control" id="seo_title" name="seo_title" maxlength="255" value="<?= htmlspecialchars($editing['seo_title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="seo_description">SEO description</label>
                            <textarea class="form-control" id="seo_description" name="seo_description" maxlength="320" rows="2"><?= htmlspecialchars($editing['seo_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="seo_keywords">SEO keywords</label>
                            <input class="form-control" id="seo_keywords" name="seo_keywords" maxlength="500" value="<?= htmlspecialchars($editing['seo_keywords'] ?? '') ?>">
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-4" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm page' ?></button>
                    </form>

                    <?php if ($selectedPageSlug !== ''): ?>
                        <div class="glass-panel p-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Page thuộc slug <span class="font-monospace"><?= htmlspecialchars($selectedPageSlug) ?></span></h2>
                                    <div class="muted-text small"><?= number_format((int) ($selectedSlugStats['page_count'] ?? 0)) ?> page · <?= htmlspecialchars((string) ($selectedSlugStats['langs'] ?? '')) ?></div>
                                </div>
                                <a class="btn btn-sm btn-success fw-bold" href="index.php?section=pages&slug=<?= urlencode($selectedPageSlug) ?>">Thêm lang mới</a>
                            </div>
                            <div class="table-responsive-sm">
                                <table class="table table-striped table-hover table-sm align-middle">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Page</th>
                                        <th>Lang</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($selectedSlugPages as $page): ?>
                                        <tr class="<?= (int) ($editing['id'] ?? 0) === (int) $page['id'] ? 'table-warning' : '' ?>">
                                            <td><?= (int) $page['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($page['title']) ?></strong>
                                                <?php if (!empty($page['seo_title'])): ?>
                                                    <div class="muted-text small"><?= htmlspecialchars($page['seo_title']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($page['lang']) ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                    <a class="btn btn-sm btn-secondary" href="https://heartbeatplay.com/page/<?= urlencode($page['slug']) ?>?lang=<?= urlencode($page['lang']) ?>" target="_blank" rel="noopener noreferrer" title="Xem page Heart Beat Play" aria-label="Xem page Heart Beat Play">
                                                        <i data-lucide="external-link" style="width:16px;height:16px"></i>
                                                    </a>
                                                    <a class="btn btn-sm btn-warning" href="index.php?section=pages&slug=<?= urlencode($selectedPageSlug) ?>&edit=<?= (int) $page['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                        <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                    </a>
                                                    <form class="js-delete" method="post" data-confirm="Xóa page <?= htmlspecialchars($page['title']) ?>?">
                                                        <input type="hidden" name="action" value="delete_page">
                                                        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$selectedSlugPages): ?>
                                        <tr><td colspan="4" class="text-center muted-text py-4">Slug này chưa có page.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
