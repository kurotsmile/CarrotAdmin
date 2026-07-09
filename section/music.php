            <?php if ($section === 'music'): ?>
            <?php
            $selectedSongArtistIds = $editing && $musicTab === 'songs' ? admin_fetch_song_artist_ids($pdo, (string) ($editing['id'] ?? '')) : [];
            $editingGenreId = (string) ($editing['genre_id'] ?? '');
            ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'songs' ? 'active' : '' ?>" href="index.php?section=music&tab=songs">Bài hát</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'artists' ? 'active' : '' ?>" href="index.php?section=music&tab=artists">Nghệ sĩ</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'genres' ? 'active' : '' ?>" href="index.php?section=music&tab=genres">Thể loại</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'orders' ? 'active' : '' ?>" href="index.php?section=music&tab=orders">Đơn đặt hàng</a></li>
            </ul>

            <?php if ($musicTab === 'songs'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_song">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật bài hát' : 'Thêm bài hát' ?></h2>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="song_id">ID</label>
                                <input class="form-control" id="song_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="song_name">Tên bài hát</label>
                                <input class="form-control" id="song_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-7">
                                <label class="form-label" for="song_artist">Ca sĩ text</label>
                                <input class="form-control" id="song_artist" name="artist" value="<?= htmlspecialchars($editing['artist'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="song_lang">Lang</label>
                                <select class="form-control js-country-select" id="song_lang" name="lang">
                                    <?= admin_language_select_options($languageOptions, (string) ($editing['lang'] ?? 'vi')) ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="song_artist_ids">Nghệ sĩ quản lý</label>
                            <select class="form-control js-music-artist-select" id="song_artist_ids" name="artist_ids[]" multiple>
                                <?php foreach ($songArtists as $artistRow): ?>
                                    <option value="<?= (int) $artistRow['id'] ?>" <?= in_array((int) $artistRow['id'], $selectedSongArtistIds, true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($artistRow['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="song_album">Album</label>
                                <input class="form-control" id="song_album" name="album" value="<?= htmlspecialchars($editing['album'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="song_genre">Thể loại</label>
                                <select class="form-control js-music-genre-select" id="song_genre" name="genre">
                                    <option value="">Chọn thể loại</option>
                                    <?php foreach ($songGenres as $genreRow): ?>
                                        <option value="<?= htmlspecialchars($genreRow['genre_id']) ?>" <?= (string) ($editing['genre'] ?? '') === (string) $genreRow['genre_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($genreRow['title'] ?: $genreRow['genre_id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-4">
                                <label class="form-label" for="song_year">Năm</label>
                                <input class="form-control" id="song_year" name="year" value="<?= htmlspecialchars($editing['year'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="song_date">Ngày</label>
                                <input class="form-control" id="song_date" name="date" value="<?= htmlspecialchars($editing['date'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="song_publishedAt">PublishedAt</label>
                                <input class="form-control" id="song_publishedAt" name="publishedAt" value="<?= htmlspecialchars($editing['publishedAt'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="song_link_ytb">YouTube</label>
                            <div class="input-group">
                                <input class="form-control" id="song_link_ytb" name="link_ytb" value="<?= htmlspecialchars($editing['link_ytb'] ?? '') ?>">
                                <button class="btn btn-secondary" id="song_load_youtube" type="button" title="Load data từ YouTube">
                                    <i data-lucide="refresh-cw" style="width:16px;height:16px"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="song_avatar">Avatar</label>
                            <div class="input-group">
                                <input class="form-control" id="song_avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="song_avatar" data-type-media="song_avatar" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="song_mp3">MP3</label>
                            <div class="input-group">
                                <input class="form-control" id="song_mp3" name="mp3" value="<?= htmlspecialchars($editing['mp3'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="song_mp3" data-type-media="song_mp3" data-mode="replace" data-accept="audio/mpeg,.mp3">Upload</button>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="song_lyrics">Lyrics HTML</label>
                            <textarea class="form-control" id="song_lyrics" name="lyrics" rows="8"><?= htmlspecialchars($editing['lyrics'] ?? '') ?></textarea>
                        </div>

                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu bài hát</button>
                    </form>
                </div>
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Danh sách bài hát</h2>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="muted-text small"><?= number_format($songTotal) ?> bài hát</div>
                            <div class="muted-text small">Trang <?= number_format($songPage) ?>/<?= number_format($songTotalPages) ?></div>
                        </div>
                        <?php
                        $songPageParams = $_GET;
                        unset($songPageParams['edit']);
                        $songPageParams['section'] = 'music';
                        $songPageParams['tab'] = 'songs';
                        ?>
                        <?= admin_pagination($songPageParams, 'song_page', $songPage, $songTotalPages, 'Phân trang bài hát', 'mb-3') ?>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>Bài hát</th><th>Nghệ sĩ</th><th>Thể loại</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($songs as $song): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($song['avatar'])): ?><img src="<?= htmlspecialchars($song['avatar']) ?>" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:8px"><?php endif; ?>
                                                <div><strong><?= htmlspecialchars($song['name'] ?? $song['id']) ?></strong><div class="small text-muted"><?= htmlspecialchars($song['id']) ?></div></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($song['artist_names'] ?: ($song['artist'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($song['genre'] ?? '') ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-warning" href="index.php?section=music&tab=songs&edit=<?= urlencode($song['id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
                                            <form class="d-inline js-confirm-delete" method="post">
                                                <input type="hidden" name="action" value="delete_song">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($song['id']) ?>">
                                                <button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$songs): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có bài hát.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= admin_pagination($songPageParams, 'song_page', $songPage, $songTotalPages, 'Phân trang bài hát') ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($musicTab === 'artists'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_song_artist">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật nghệ sĩ' : 'Thêm nghệ sĩ' ?></h2>
                        <div class="mb-3">
                            <label class="form-label" for="artist_name">Name</label>
                            <input class="form-control" id="artist_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="artist_avatar">Avatar</label>
                            <div class="input-group">
                                <input class="form-control" id="artist_avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="artist_avatar" data-type-media="artist_avatar" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="artist_description">Mô tả</label>
                            <div class="simple-editor-toolbar" role="toolbar" aria-label="Editor toolbar">
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="bold"><strong>B</strong></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="italic"><em>I</em></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="formatBlock" data-editor-value="h2">H2</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="formatBlock" data-editor-value="p">P</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="insertUnorderedList">List</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="createLink">Link</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_artist_description" data-editor-command="removeFormat">Clear</button>
                            </div>
                            <div class="simple-editor-canvas" id="artist_description_editor" contenteditable="true" spellcheck="true" style="min-height:260px;margin-top:8px;padding:5px;border:1px solid rgba(0,0,0,.15)"><?= ($editing['description'] ?? '') ?></div>
                            <textarea class="form-control font-monospace d-none" id="artist_description" name="description" rows="6"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="artist_lang_key">Lang key</label>
                            <select class="form-control js-country-select" id="artist_lang_key" name="lang_key">
                                <?= admin_language_select_options($languageOptions, (string) ($editing['lang_key'] ?? 'vi')) ?>
                            </select>
                        </div>
                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu nghệ sĩ</button>
                    </form>
                </div>
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Nghệ sĩ</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>Name</th><th>Lang</th><th>Mô tả</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($songArtists as $artistRow): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($artistRow['avatar'])): ?><img src="<?= htmlspecialchars($artistRow['avatar']) ?>" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:50%"><?php endif; ?>
                                                <strong><?= htmlspecialchars($artistRow['name']) ?></strong>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($artistRow['lang_key'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(admin_excerpt(strip_tags($artistRow['description']) ?? '', 90)) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-warning" href="index.php?section=music&tab=artists&edit=<?= (int) $artistRow['id'] ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
                                            <form class="d-inline js-confirm-delete" method="post">
                                                <input type="hidden" name="action" value="delete_song_artist">
                                                <input type="hidden" name="id" value="<?= (int) $artistRow['id'] ?>">
                                                <button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$songArtists): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có nghệ sĩ.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($musicTab === 'genres'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_song_genre">
                        <input type="hidden" name="original_genre_id" value="<?= htmlspecialchars($editingGenreId) ?>">
                        <h2 class="h5 mb-3"><?= $editing ? 'Cập nhật thể loại' : 'Thêm thể loại' ?></h2>
                        <div class="mb-3">
                            <label class="form-label" for="genre_id">Genre ID</label>
                            <input class="form-control" id="genre_id" name="genre_id" value="<?= htmlspecialchars($editing['genre_id'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="genre_title">Title</label>
                            <input class="form-control" id="genre_title" name="title" value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="genre_description">Description</label>
                            <div class="simple-editor-toolbar" role="toolbar" aria-label="Editor toolbar">
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="bold"><strong>B</strong></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="italic"><em>I</em></button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="formatBlock" data-editor-value="h2">H2</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="formatBlock" data-editor-value="p">P</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="insertUnorderedList">List</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="createLink">Link</button>
                                <button class="btn btn-sm btn-light" type="button" data-editor-target="music_genre_description" data-editor-command="removeFormat">Clear</button>
                            </div>
                            <div class="simple-editor-canvas" id="genre_description_editor" contenteditable="true" spellcheck="true" style="min-height:260px;margin-top:8px;padding:5px;border:1px solid rgba(0,0,0,.15)"><?= ($editing['description'] ?? '') ?></div>
                            <textarea class="form-control font-monospace d-none" id="genre_description" name="description" rows="5"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-success fw-bold w-100" type="submit">Lưu thể loại</button>
                    </form>
                </div>
                <div class="col-xl-7">
                    <div class="glass-panel p-4">
                        <h2 class="h5 mb-3">Thể loại</h2>
                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>Genre</th><th>Mô tả</th><th>Số bài</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($songGenres as $genreRow): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($genreRow['title'] ?: $genreRow['genre_id']) ?></strong><div class="small text-muted"><?= htmlspecialchars($genreRow['genre_id']) ?></div></td>
                                        <td><?= htmlspecialchars(admin_excerpt($genreRow['description'] ?? '', 100)) ?></td>
                                        <td><?= number_format((int) ($genreRow['song_count'] ?? 0)) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-warning" href="index.php?section=music&tab=genres&edit=<?= urlencode($genreRow['genre_id']) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
                                            <form class="d-inline js-confirm-delete" method="post">
                                                <input type="hidden" name="action" value="delete_song_genre">
                                                <input type="hidden" name="genre_id" value="<?= htmlspecialchars($genreRow['genre_id']) ?>">
                                                <button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$songGenres): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có thể loại.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($musicTab === 'orders'): ?>
            <div class="glass-panel p-4">
                <h2 class="h5 mb-3">Đơn đặt hàng nhạc</h2>
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead><tr><th>Song</th><th>PayPal</th><th>User</th><th>Status</th><th>Amount</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($songOrders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['song_name'] ?? $order['song_id']) ?></strong><div class="small text-muted"><?= htmlspecialchars($order['song_id']) ?></div></td>
                                <td class="font-monospace small"><?= htmlspecialchars($order['paypal_order_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars(trim(($order['user_name'] ?? '') . ' ' . ($order['user_email'] ?? ''))) ?></td>
                                <td><span class="badge text-bg-<?= ($order['status'] ?? '') === 'COMPLETED' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($order['status'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars(number_format((float) ($order['amount'] ?? 0), 2) . ' ' . ($order['currency'] ?? 'USD')) ?></td>
                                <td><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
                                <td class="text-end">
                                    <form class="d-inline js-confirm-delete" method="post">
                                        <input type="hidden" name="action" value="delete_song_order">
                                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$songOrders): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có đơn hàng.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <script>
            const youtubeButton = document.getElementById('song_load_youtube');
            if (youtubeButton) {
                youtubeButton.addEventListener('click', async () => {
                    const linkInput = document.getElementById('song_link_ytb');
                    const formData = new FormData();
                    formData.append('action', 'ajax_youtube_song');
                    formData.append('url', linkInput ? linkInput.value : '');
                    try {
                        youtubeButton.disabled = true;
                        const response = await fetch('index.php?section=music', {method: 'POST', body: formData});
                        const payload = await response.json();
                        if (!response.ok || payload.status !== 'success') {
                            throw new Error(payload.message || 'Không load được YouTube.');
                        }
                        const fields = ['name', 'artist', 'publishedAt', 'avatar', 'lyrics'];
                        fields.forEach((field) => {
                            const input = document.getElementById('song_' + field);
                            if (input && payload[field]) input.value = payload[field];
                        });
                        Swal.fire({icon: 'success', title: 'Đã lấy dữ liệu YouTube', timer: 1300, showConfirmButton: false});
                    } catch (error) {
                        Swal.fire({icon: 'error', title: 'YouTube API', text: error.message});
                    } finally {
                        youtubeButton.disabled = false;
                    }
                });
            }
            </script>
            <?php endif; ?>
