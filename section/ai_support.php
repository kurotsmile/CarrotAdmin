            <?php if ($section === 'ai_support'): ?>
            <?php
            $aiRows = [];
            $aiEditId = (int) ($_GET['edit'] ?? 0);
            $aiEditing = [
                'id' => 0,
                'account_name' => 'Gemini account',
                'enabled' => 1,
                'api_key' => '',
                'model' => 'gemini-3.5-flash',
                'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
                'temperature' => '0.20',
                'system_prompt' => 'You are a precise translation engine for a website CMS. Translate from English to the target language. Preserve HTML tags, attributes, URLs, whitespace intent, entities, and placeholders. Return only the translated content without markdown fences or explanations.',
                'priority' => 0,
            ];

            if ($pdo instanceof PDO) {
                admin_ensure_ai_support_table($pdo);
                $aiRows = admin_fetch_ai_support_configs($pdo);
                if ($aiEditId > 0) {
                    foreach ($aiRows as $aiRow) {
                        if ((int) ($aiRow['id'] ?? 0) === $aiEditId) {
                            $aiEditing = array_merge($aiEditing, $aiRow);
                            break;
                        }
                    }
                }
            }
            ?>

            <div class="row g-4">
                <div class="col-xl-6">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_ai_support_config">
                        <input type="hidden" name="id" value="<?= (int) ($aiEditing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= !empty($aiEditing['id']) ? 'Cập nhật tài khoản Gemini' : 'Thêm tài khoản Gemini' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="ai_account_name">Tên tài khoản</label>
                                <input class="form-control" id="ai_account_name" name="account_name" maxlength="120" value="<?= htmlspecialchars($aiEditing['account_name'] ?? 'Gemini account') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="ai_priority">Priority</label>
                                <input class="form-control" id="ai_priority" name="priority" type="number" step="1" value="<?= htmlspecialchars((string) ($aiEditing['priority'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="ai_enabled">Enabled</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" id="ai_enabled" name="enabled" type="checkbox" value="1" <?= !empty($aiEditing['enabled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_enabled">Bật tài khoản</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-8">
                                <label class="form-label" for="ai_model">Model</label>
                                <input class="form-control" id="ai_model" name="model" value="<?= htmlspecialchars($aiEditing['model'] ?? 'gemini-3.5-flash') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="ai_temperature">Temperature</label>
                                <input class="form-control" id="ai_temperature" name="temperature" type="number" min="0" max="2" step="0.01" value="<?= htmlspecialchars((string) ($aiEditing['temperature'] ?? '0.20')) ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="ai_api_key">Gemini API Key</label>
                            <textarea class="form-control font-monospace small" id="ai_api_key" name="api_key" rows="2"><?= htmlspecialchars($aiEditing['api_key'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ai_endpoint">Endpoint</label>
                            <input class="form-control font-monospace small" id="ai_endpoint" name="endpoint" value="<?= htmlspecialchars($aiEditing['endpoint'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ai_system_prompt">System prompt</label>
                            <textarea class="form-control" id="ai_system_prompt" name="system_prompt" rows="6"><?= htmlspecialchars($aiEditing['system_prompt'] ?? '') ?></textarea>
                        </div>

                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu AI account</button>
                        <?php if (!empty($aiEditing['id'])): ?>
                            <a class="btn btn-light fw-bold w-100 mt-2" href="index.php?section=ai_support">Thêm tài khoản mới</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="col-xl-6">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách tài khoản AI</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Tài khoản</th>
                                    <th>Model</th>
                                    <th>API Key</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($aiRows as $aiRow): ?>
                                    <tr>
                                        <td><?= (int) ($aiRow['priority'] ?? 0) ?></td>
                                        <td>
                                            <a class="fw-bold" href="index.php?section=ai_support&edit=<?= (int) $aiRow['id'] ?>"><?= htmlspecialchars($aiRow['account_name'] ?? 'Gemini account') ?></a>
                                            <div><?= !empty($aiRow['enabled']) ? '<span class="badge text-bg-success">On</span>' : '<span class="badge text-bg-secondary">Off</span>' ?></div>
                                        </td>
                                        <td class="font-monospace small"><?= htmlspecialchars($aiRow['model'] ?? '') ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars(admin_mask_secret($aiRow['api_key'] ?? '')) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=ai_support&edit=<?= (int) $aiRow['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa tài khoản AI này?">
                                                    <input type="hidden" name="action" value="delete_ai_support_config">
                                                    <input type="hidden" name="id" value="<?= (int) $aiRow['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$aiRows): ?>
                                    <tr><td colspan="5" class="text-center muted-text py-4">Chưa có tài khoản AI.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
