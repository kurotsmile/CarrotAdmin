            <?php if ($section === 'api'): ?>
            <?php
            $apiProvider = (string) ($editing['provider'] ?? 'google');
            $apiProviders = [
                'google' => 'Google Authorization',
                'github' => 'GitHub OAuth',
                'twitter_x' => 'Twitter / X OAuth',
                'youtube' => 'YouTube API',
                'supabase' => 'Supabase',
                'custom' => 'Custom API',
            ];
            ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_api_config">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật API' : 'Thêm API mới' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="api_provider">Provider</label>
                                <select class="form-control" id="api_provider" name="provider" required>
                                    <?php foreach ($apiProviders as $providerKey => $providerLabel): ?>
                                        <option value="<?= htmlspecialchars($providerKey) ?>" <?= $apiProvider === $providerKey ? 'selected' : '' ?>><?= htmlspecialchars($providerLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="api_enabled">Status</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input" id="api_enabled" name="enabled" type="checkbox" value="1" <?= !empty($editing['enabled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="api_enabled">Enabled</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="api_name">Name</label>
                            <input class="form-control" id="api_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" placeholder="Google Login Production" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_client_id">Client ID</label>
                            <input class="form-control" id="api_client_id" name="client_id" value="<?= htmlspecialchars($editing['client_id'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_client_secret">Client Secret</label>
                            <input class="form-control" id="api_client_secret" name="client_secret" value="<?= htmlspecialchars($editing['client_secret'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_api_key">API key / anon key</label>
                            <input class="form-control" id="api_api_key" name="api_key" value="<?= htmlspecialchars($editing['api_key'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_project_url">Project URL</label>
                            <input class="form-control" id="api_project_url" name="project_url" value="<?= htmlspecialchars($editing['project_url'] ?? '') ?>" placeholder="https://...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_redirect_uri">Redirect URI</label>
                            <input class="form-control" id="api_redirect_uri" name="redirect_uri" value="<?= htmlspecialchars($editing['redirect_uri'] ?? '') ?>" placeholder="https://home.carrot28.com/oauth-callback.php">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_scopes">Scopes</label>
                            <input class="form-control" id="api_scopes" name="scopes" value="<?= htmlspecialchars($editing['scopes'] ?? '') ?>" placeholder="openid email profile">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_config_json">Config JSON</label>
                            <textarea class="form-control font-monospace" id="api_config_json" name="config_json" rows="4" placeholder='{"tenant":"production"}'><?= htmlspecialchars($editing['config_json'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_note">Note</label>
                            <textarea class="form-control" id="api_note" name="note" rows="2"><?= htmlspecialchars($editing['note'] ?? '') ?></textarea>
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm API' ?></button>
                    </form>
                </div>

                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Danh sách API</h2>
                            <span class="badge text-bg-secondary"><?= number_format(count($apiConfigs)) ?> config</span>
                        </div>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('provider', 'Provider', $apiSort, $apiDir) ?></th>
                                    <th><?= admin_sort_link('name', 'Name', $apiSort, $apiDir) ?></th>
                                    <th><?= admin_sort_link('enabled', 'Status', $apiSort, $apiDir) ?></th>
                                    <th>Key</th>
                                    <th><?= admin_sort_link('updated_at', 'Updated', $apiSort, $apiDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($apiConfigs as $apiConfig): ?>
                                    <tr>
                                        <td><span class="badge text-bg-light"><?= htmlspecialchars($apiConfig['provider'] ?? '') ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($apiConfig['name'] ?? '') ?></strong>
                                            <?php if (!empty($apiConfig['redirect_uri'])): ?>
                                                <div class="muted-text small"><?= htmlspecialchars(admin_excerpt($apiConfig['redirect_uri'], 52)) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= !empty($apiConfig['enabled']) ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= !empty($apiConfig['enabled']) ? 'Enabled' : 'Disabled' ?>
                                            </span>
                                        </td>
                                        <td class="font-monospace small">
                                            <?php
                                            $keyPreview = trim((string) ($apiConfig['client_id'] ?: $apiConfig['api_key'] ?: $apiConfig['project_url'] ?: ''));
                                            echo htmlspecialchars($keyPreview !== '' ? admin_excerpt($keyPreview, 36) : '-');
                                            ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($apiConfig['updated_at'] ?? '') ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=api&edit=<?= (int) $apiConfig['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa API config này?">
                                                    <input type="hidden" name="action" value="delete_api_config">
                                                    <input type="hidden" name="id" value="<?= (int) $apiConfig['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$apiConfigs): ?>
                                    <tr><td colspan="6" class="text-center muted-text py-4">Chưa có API config.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
