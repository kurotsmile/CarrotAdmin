            <?php if ($section === 'ai_support'): ?>
            <?php
            $aiEditing = [
                'enabled' => 0,
                'api_key' => '',
                'model' => 'gemini-3.5-flash',
                'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
                'temperature' => '0.20',
                'system_prompt' => 'You are a precise translation engine for a website CMS. Translate from English to the target language. Preserve HTML tags, attributes, URLs, whitespace intent, entities, and placeholders. Return only the translated content without markdown fences or explanations.',
            ];

            if ($pdo instanceof PDO) {
                admin_ensure_ai_support_table($pdo);
                $aiRow = admin_fetch_ai_support_config($pdo);
                if ($aiRow) {
                    $aiEditing = array_merge($aiEditing, $aiRow);
                }
            }
            ?>

            <div class="row g-4">
                <div class="col-xl-6">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_ai_support_config">
                        <h2 class="h5 mb-3">Gemini Translate</h2>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="ai_enabled">Enabled</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" id="ai_enabled" name="enabled" type="checkbox" value="1" <?= !empty($aiEditing['enabled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_enabled">Bật AI</label>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="ai_model">Model</label>
                                <input class="form-control" id="ai_model" name="model" value="<?= htmlspecialchars($aiEditing['model'] ?? 'gemini-3.5-flash') ?>">
                            </div>
                            <div class="col-md-3">
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

                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu AI config</button>
                    </form>
                </div>

                <div class="col-xl-6">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Trạng thái cấu hình</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <tbody>
                                <tr>
                                    <th>Provider</th>
                                    <td>Gemini</td>
                                </tr>
                                <tr>
                                    <th>Enabled</th>
                                    <td><?= !empty($aiEditing['enabled']) ? '<span class="badge text-bg-success">On</span>' : '<span class="badge text-bg-secondary">Off</span>' ?></td>
                                </tr>
                                <tr>
                                    <th>Model</th>
                                    <td class="font-monospace small"><?= htmlspecialchars($aiEditing['model'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>API Key</th>
                                    <td class="font-monospace small"><?= htmlspecialchars(admin_mask_secret($aiEditing['api_key'] ?? '')) ?></td>
                                </tr>
                                <tr>
                                    <th>Endpoint</th>
                                    <td class="font-monospace small"><?= htmlspecialchars($aiEditing['endpoint'] ?? '') ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
