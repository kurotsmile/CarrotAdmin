            <?php if ($section === 'country'): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $countryTab === 'countries' ? 'active' : '' ?>" href="index.php?section=country&tab=countries">Quốc gia</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $countryTab === 'labels' ? 'active' : '' ?>" href="index.php?section=country&tab=labels">Nhãn đa ngôn ngữ</a>
                </li>
            </ul>
            <?php if ($countryTab === 'countries'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_country">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật country' : 'Thêm country mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="country_name">Name</label>
                            <input class="form-control" id="country_name" name="name" maxlength="120" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="country_icon">Icon URL</label>
                            <div class="input-group">
                                <input class="form-control" id="country_icon" name="icon" value="<?= htmlspecialchars($editing['icon'] ?? '') ?>" required>
                                <button class="btn btn-secondary js-upload" type="button" data-target="country_icon" data-type-media="country" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="country_lang_key">Lang key</label>
                                <input class="form-control" id="country_lang_key" name="lang_key" maxlength="24" placeholder="vi" value="<?= htmlspecialchars($editing['lang_key'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="country_lang_country">Lang country</label>
                                <input class="form-control text-uppercase" id="country_lang_country" name="lang_country" maxlength="24" placeholder="VN" value="<?= htmlspecialchars($editing['lang_country'] ?? '') ?>" required>
                            </div>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100 mt-3" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm country' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách country</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $countrySort, $countryDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Country', $countrySort, $countryDir) ?></th>
                                    <th><?= admin_sort_link('lang_key', 'Lang key', $countrySort, $countryDir) ?></th>
                                    <th><?= admin_sort_link('lang_country', 'Lang country', $countrySort, $countryDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($countries as $country): ?>
                                    <tr>
                                        <td><?= (int) $country['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($country['icon'])): ?>
                                                    <img src="<?= htmlspecialchars($country['icon']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($country['name']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="font-monospace small"><?= htmlspecialchars($country['lang_key']) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars($country['lang_country']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=country&edit=<?= (int) $country['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa country này?">
                                                    <input type="hidden" name="action" value="delete_country">
                                                    <input type="hidden" name="id" value="<?= (int) $country['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$countries): ?>
                                    <tr><td colspan="5" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($countryTab === 'labels'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="glass-panel p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Danh sách nhãn đa ngôn ngữ</h2>
                            <form class="d-flex gap-2" method="get">
                                <input type="hidden" name="section" value="country">
                                <input type="hidden" name="tab" value="labels">
                                <input class="form-control form-control-sm" name="label_q" value="<?= htmlspecialchars($textLabelSearch) ?>" placeholder="Search key">
                                <button class="btn btn-sm btn-secondary" type="submit" title="Search" aria-label="Search">
                                    <i data-lucide="search" style="width:16px;height:16px"></i>
                                </button>
                                <?php if ($textLabelSearch !== ''): ?>
                                    <a class="btn btn-sm btn-light" href="index.php?section=country&tab=labels" title="Clear" aria-label="Clear">
                                        <i data-lucide="x" style="width:16px;height:16px"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="muted-text small">
                                <?= number_format($textLabelTotal) ?> key
                                <?php if ($textLabelSearch !== ''): ?>
                                    cho từ khóa "<?= htmlspecialchars($textLabelSearch) ?>"
                                <?php endif; ?>
                            </div>
                            <div class="muted-text small">Trang <?= number_format($textLabelPage) ?>/<?= number_format($textLabelTotalPages) ?></div>
                        </div>
                        <?php
                        $labelPageParams = $_GET;
                        unset($labelPageParams['edit']);
                        $labelPageParams['section'] = 'country';
                        $labelPageParams['tab'] = 'labels';
                        ?>
                        <?= admin_pagination($labelPageParams, 'label_page', $textLabelPage, $textLabelTotalPages, 'Phân trang nhãn', 'mb-3') ?>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Value</th>
                                    <th>Lang</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($textLabels as $label): ?>
                                    <?php $labelGroupKey = (string) $label['key']; ?>
                                    <tr class="<?= $labelGroupKey === $selectedLabelKey ? 'table-success' : '' ?>">
                                        <td>
                                            <a class="fw-bold font-monospace text-decoration-none" href="index.php?section=country&tab=labels&label_key=<?= urlencode($labelGroupKey) ?>">
                                                <?= htmlspecialchars($labelGroupKey) ?>
                                            </a>
                                            <?php if (!empty($label['latest_update'])): ?>
                                                <div class="muted-text small">Update: <?= htmlspecialchars((string) $label['latest_update']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge text-bg-secondary"><?= number_format((int) $label['label_count']) ?></span></td>
                                        <td class="small"><?= htmlspecialchars((string) $label['langs']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <button class="btn btn-sm btn-secondary js-label-translations" type="button" data-label-key="<?= htmlspecialchars($labelGroupKey) ?>" title="Dịch nhanh theo country" aria-label="Dịch nhanh theo country">
                                                    <i data-lucide="table-2" style="width:16px;height:16px"></i>
                                                </button>
                                                <a class="btn btn-sm btn-warning" href="index.php?section=country&tab=labels&label_key=<?= urlencode($labelGroupKey) ?>" title="Quản lý key" aria-label="Quản lý key">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa toàn bộ <?= number_format((int) $label['label_count']) ?> value thuộc key <?= htmlspecialchars($labelGroupKey) ?>?">
                                                    <input type="hidden" name="action" value="delete_text_label_group">
                                                    <input type="hidden" name="key" value="<?= htmlspecialchars($labelGroupKey) ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa key" aria-label="Xóa key">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$textLabels): ?>
                                    <tr><td colspan="4" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= admin_pagination($labelPageParams, 'label_page', $textLabelPage, $textLabelTotalPages, 'Phân trang nhãn') ?>
                    </div>
                </div>

                <div class="col-xl-7">
                    <form class="glass-panel p-4 mb-4" method="post">
                        <input type="hidden" name="action" value="save_text_label">
                        <input type="hidden" name="id" value="<?= (int) ($editingLabel['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editingLabel ? 'Cập nhật nhãn' : 'Thêm nhãn mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="label_key">Key</label>
                            <?php $labelKeyValue = (string) ($editingLabel['key'] ?? $selectedLabelKey); ?>
                            <select class="form-control font-monospace js-label-key-select" id="label_key" name="key" required>
                                <?php if ($labelKeyValue === ''): ?>
                                    <option value=""></option>
                                <?php endif; ?>
                                <?php if ($labelKeyValue !== '' && !in_array($labelKeyValue, $labelKeyOptions, true)): ?>
                                    <option value="<?= htmlspecialchars($labelKeyValue) ?>" selected><?= htmlspecialchars($labelKeyValue) ?></option>
                                <?php endif; ?>
                                <?php foreach ($labelKeyOptions as $labelKeyOption): ?>
                                    <option value="<?= htmlspecialchars($labelKeyOption) ?>" <?= $labelKeyOption === $labelKeyValue ? 'selected' : '' ?>><?= htmlspecialchars($labelKeyOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="label_lang_key">Lang key</label>
                            <?php $labelLangValue = (string) ($editingLabel['lang_key'] ?? 'vi'); ?>
                            <select class="form-control js-label-lang-select" id="label_lang_key" name="lang_key" required>
                                <option value="">Chọn lang</option>
                                <?php
                                $labelLangFound = $labelLangValue === '';
                                foreach ($languageOptions as $labelLanguageOption):
                                    $labelLangKey = (string) ($labelLanguageOption['lang_key'] ?? '');
                                    if ($labelLangKey === '') {
                                        continue;
                                    }
                                    $labelLangSelected = $labelLangKey === $labelLangValue;
                                    $labelLangFound = $labelLangFound || $labelLangSelected;
                                    $labelLangDisabled = in_array($labelLangKey, $selectedLabelLangs, true) && !$labelLangSelected;
                                    $labelLangParts = [$labelLangKey];
                                    if (!empty($labelLanguageOption['countries'])) {
                                        $labelLangParts[] = (string) $labelLanguageOption['countries'];
                                    } elseif (!empty($labelLanguageOption['name'])) {
                                        $labelLangParts[] = (string) $labelLanguageOption['name'];
                                    } elseif (!empty($labelLanguageOption['lang_country'])) {
                                        $labelLangParts[] = (string) $labelLanguageOption['lang_country'];
                                    }
                                    if ($labelLangDisabled) {
                                        $labelLangParts[] = 'đã có value';
                                    }
                                ?>
                                    <option value="<?= htmlspecialchars($labelLangKey) ?>" <?= $labelLangSelected ? 'selected' : '' ?> <?= $labelLangDisabled ? 'disabled' : '' ?>><?= htmlspecialchars(implode(' - ', $labelLangParts)) ?></option>
                                <?php endforeach; ?>
                                <?php if (!$labelLangFound): ?>
                                    <option value="<?= htmlspecialchars($labelLangValue) ?>" selected><?= htmlspecialchars($labelLangValue . ' - Giá trị hiện tại') ?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <label class="form-label mb-0" for="label_value">Value default</label>
                                <button class="btn btn-sm btn-secondary d-none js-ai-label-generate" type="button">
                                    <span class="d-inline-flex align-items-center gap-2"><i data-lucide="sparkles" style="width:16px;height:16px"></i>Tạo bằng AI</span>
                                </button>
                            </div>
                            <textarea class="form-control" id="label_value" name="value" rows="4" required><?= htmlspecialchars($editingLabel['value'] ?? '') ?></textarea>
                        </div>

                        <button class="btn <?= $editingLabel ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editingLabel ? 'Lưu cập nhật' : 'Thêm nhãn' ?></button>
                    </form>

                    <?php if ($selectedLabelKey !== ''): ?>
                        <div class="glass-panel p-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Value thuộc key <span class="font-monospace"><?= htmlspecialchars($selectedLabelKey) ?></span></h2>
                                    <div class="muted-text small"><?= number_format((int) ($selectedLabelStats['label_count'] ?? 0)) ?> value · <?= htmlspecialchars((string) ($selectedLabelStats['langs'] ?? '')) ?></div>
                                </div>
                                <a class="btn btn-sm btn-success fw-bold" href="index.php?section=country&tab=labels&label_key=<?= urlencode($selectedLabelKey) ?>">Thêm lang mới</a>
                            </div>
                            <div class="table-responsive-sm">
                                <table class="table table-striped table-hover table-sm align-middle">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Lang key</th>
                                        <th>Value</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($selectedLabelRows as $labelRow): ?>
                                        <tr class="<?= (int) ($editingLabel['id'] ?? 0) === (int) $labelRow['id'] ? 'table-warning' : '' ?>">
                                            <td><?= (int) $labelRow['id'] ?></td>
                                            <td class="font-monospace small"><?= htmlspecialchars($labelRow['lang_key']) ?></td>
                                            <td><?= htmlspecialchars(admin_excerpt($labelRow['value'], 120)) ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                    <a class="btn btn-sm btn-warning" href="index.php?section=country&tab=labels&label_key=<?= urlencode($selectedLabelKey) ?>&edit=<?= (int) $labelRow['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                        <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                    </a>
                                                    <form class="js-delete" method="post" data-confirm="Xóa value <?= htmlspecialchars($labelRow['lang_key']) ?> của key này?">
                                                        <input type="hidden" name="action" value="delete_text_label">
                                                        <input type="hidden" name="id" value="<?= (int) $labelRow['id'] ?>">
                                                        <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$selectedLabelRows): ?>
                                        <tr><td colspan="4" class="text-center muted-text py-4">Key này chưa có value.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
