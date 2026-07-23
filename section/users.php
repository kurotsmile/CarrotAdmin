            <?php if ($section === 'users'): ?>
            <div class="row g-4">
                <div class="col-xl-4">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật user' : 'Thêm user mới' ?></h2>

                        <div class="mb-3">
                            <label class="form-label" for="user_name">Name</label>
                            <input class="form-control" id="user_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="user_email">Email</label>
                            <input class="form-control" id="user_email" name="email" type="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="user_password">Password</label>
                            <input class="form-control" id="user_password" name="password" value="<?= htmlspecialchars($editing['password'] ?? '') ?>" autocomplete="off">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="user_phone">Phone</label>
                                <input class="form-control" id="user_phone" name="phone" value="<?= htmlspecialchars($editing['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="user_birthday">Birthday</label>
                                <input class="form-control" id="user_birthday" name="birthday" type="date" value="<?= htmlspecialchars($editing['birthday'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="user_avatar">Avatar URL</label>
                            <input class="form-control" id="user_avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="user_address">Address</label>
                            <textarea class="form-control" id="user_address" name="address" rows="2"><?= htmlspecialchars($editing['address'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="user_lang">Lang</label>
                                <input class="form-control" id="user_lang" name="lang" value="<?= htmlspecialchars($editing['lang'] ?? 'vi') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="user_role">Role</label>
                                <select class="form-control" id="user_role" name="role">
                                    <?php $userRole = (string) ($editing['role'] ?? 'user'); ?>
                                    <?php foreach (['user', 'admin', 'staff'] as $roleOption): ?>
                                        <option value="<?= htmlspecialchars($roleOption) ?>" <?= $userRole === $roleOption ? 'selected' : '' ?>><?= htmlspecialchars($roleOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-4">
                                <label class="form-label" for="user_sex">Sex</label>
                                <input class="form-control" id="user_sex" name="sex" value="<?= htmlspecialchars($editing['sex'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="user_status_share">Share</label>
                                <input class="form-control" id="user_status_share" name="status_share" value="<?= htmlspecialchars($editing['status_share'] ?? 'private') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="user_type">Type</label>
                                <input class="form-control" id="user_type" name="type" value="<?= htmlspecialchars($editing['type'] ?? 'normal') ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="user_created_at">Created at</label>
                            <input class="form-control" id="user_created_at" name="created_at" value="<?= htmlspecialchars($editing['created_at'] ?? '') ?>" placeholder="Tự tạo nếu để trống">
                        </div>

                        <button class="btn <?= $editing ? 'btn-warning' : 'btn-success' ?> fw-bold w-100" type="submit"><?= $editing ? 'Lưu cập nhật' : 'Thêm user' ?></button>
                    </form>
                </div>

                <div class="col-xl-8">
                    <div class="glass-panel p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h2 class="h5 mb-0">Danh sách user</h2>
                            <span class="badge text-bg-secondary"><?= number_format(count($users)) ?> user</span>
                        </div>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                <tr>
                                    <th><?= admin_sort_link('id', 'ID', $userSort, $userDir) ?></th>
                                    <th><?= admin_sort_link('name', 'User', $userSort, $userDir) ?></th>
                                    <th><?= admin_sort_link('email', 'Email', $userSort, $userDir) ?></th>
                                    <th><?= admin_sort_link('role', 'Role', $userSort, $userDir) ?></th>
                                    <th><?= admin_sort_link('type', 'Type', $userSort, $userDir) ?></th>
                                    <th><?= admin_sort_link('created_at', 'Created', $userSort, $userDir) ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= (int) $user['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($user['avatar'])): ?>
                                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="" width="42" height="42" class="rounded-2 object-fit-cover">
                                                <?php else: ?>
                                                    <span class="dashboard-card-icon"><i data-lucide="user"></i></span>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($user['name'] ?? '') ?></strong>
                                                    <div class="muted-text small"><?= htmlspecialchars($user['phone'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                        <td><span class="badge text-bg-light"><?= htmlspecialchars($user['role'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($user['type'] ?? '') ?></td>
                                        <td class="small"><?= htmlspecialchars($user['created_at'] ?? '') ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                                <a class="btn btn-sm btn-warning" href="index.php?section=users&edit=<?= (int) $user['id'] ?>" title="Cập nhật" aria-label="Cập nhật">
                                                    <i data-lucide="pencil" style="width:16px;height:16px"></i>
                                                </a>
                                                <form class="js-delete" method="post" data-confirm="Xóa user này?">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" aria-label="Xóa">
                                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$users): ?>
                                    <tr><td colspan="7" class="text-center muted-text py-4">Chưa có dữ liệu.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
