            <?php if ($section === 'pages'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
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
                                    <?= admin_language_select_options($languageOptions, $pageLangValue) ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-12">
                                <label class="form-label" for="page_slug">Slug</label>
                                <?php $pageSlugValue = (string) ($editing['slug'] ?? ''); ?>
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
                            <label class="form-label" for="page_content_html">HTML content</label>
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
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách page</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $pageSort, $pageDir) ?></th>
                                    <th><?= admin_sort_link('title', 'Page', $pageSort, $pageDir) ?></th>
                                    <th><?= admin_sort_link('lang', 'Lang', $pageSort, $pageDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pages as $page): ?>
                                    <tr>
                                        <td><?= (int) $page['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($page['title']) ?></strong>
                                            <div class="muted-text small">
                                                <?= htmlspecialchars($page['slug']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($page['lang']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-secondary" href="https://home.carrot28.com/index.php?page=<?= urlencode($page['slug']) ?>&lang=<?= urlencode($page['lang']) ?>" target="_blank" rel="noopener noreferrer" title="Xem page" aria-label="Xem page">
                                                    <i data-lucide="external-link" style="width:16px;height:16px"></i>
                                                </a>
                                                <a class="btn btn-sm btn-warning" href="index.php?section=pages&edit=<?= (int) $page['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa page này?">
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
                                <?php if (!$pages): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
