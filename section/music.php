            <?php if ($section === 'music'): ?>
            <?php
            $selectedSongArtistIds = $editing && $musicTab === 'songs' ? admin_fetch_song_artist_ids($pdo, (string) ($editing['id'] ?? '')) : [];
            $selectedSongGenreIds = $editing && $musicTab === 'songs'
                ? array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', (string) ($editing['genre'] ?? '')) ?: [])))
                : [];
            $editingGenreId = (string) ($editing['genre_id'] ?? '');
            $musicPublicBase = 'https://heartbeatplay.com';
            $musicAdminSlug = static function ($value): string {
                $value = trim(rawurldecode((string) $value));
                $value = str_replace(['_', '+'], '-', $value);
                if (function_exists('iconv')) {
                    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
                    if (is_string($ascii) && trim($ascii) !== '') {
                        $value = $ascii;
                    }
                }
                $value = strtolower($value);
                $value = preg_replace('/[^a-z0-9]+/', '-', $value);
                $value = trim((string) $value, '-');
                return $value !== '' ? $value : 'music';
            };
            $musicPublicSongUrl = static fn($songId): string => $musicPublicBase . '/' . rawurlencode($musicAdminSlug($songId));
            $musicPublicArtistUrl = static fn($artistId, $artistName = ''): string => $musicPublicBase . '/artist/' . rawurlencode(trim((string) $artistName) !== '' ? $musicAdminSlug($artistName) : (string) $artistId);
            $musicPublicGenreUrl = static fn($genreId, $genreTitle = ''): string => $musicPublicBase . '/genre/' . rawurlencode(trim((string) $genreTitle) !== '' ? $musicAdminSlug($genreTitle) : $musicAdminSlug($genreId));
            ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'songs' ? 'active' : '' ?>" href="index.php?section=music&tab=songs">Bài hát</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'artists' ? 'active' : '' ?>" href="index.php?section=music&tab=artists">Nghệ sĩ</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'genres' ? 'active' : '' ?>" href="index.php?section=music&tab=genres">Thể loại</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'orders' ? 'active' : '' ?>" href="index.php?section=music&tab=orders">Đơn đặt hàng</a></li>
                <li class="nav-item"><a class="nav-link <?= $musicTab === 'search_log' ? 'active' : '' ?>" href="index.php?section=music&tab=search_log">Lịch sử tìm kiếm</a></li>
            </ul>

            <?php if ($musicTab === 'songs'): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <form class="glass-panel p-4" method="post">
                        <input type="hidden" name="action" value="save_song">
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h2 class="h5 mb-0"><?= $editing ? 'Cập nhật bài hát' : 'Thêm bài hát' ?></h2>
                            <?php if (!$editing): ?>
                                <button class="btn btn-sm btn-primary" id="song_ai_random" type="button">
                                    <span class="d-inline-flex align-items-center gap-2"><i data-lucide="sparkles" style="width:16px;height:16px"></i>Thêm bằng AI</span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="song_id">ID</label>
                                <div class="input-group">
                                    <input class="form-control" id="song_id" name="id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>" required>
                                    <button class="btn btn-outline-secondary js-copy-field" type="button" data-copy-target="#song_id" title="Copy ID" aria-label="Copy ID">
                                        <i data-lucide="copy" style="width:16px;height:16px"></i>
                                    </button>
                                    <button class="btn btn-secondary" id="song_generate_id" type="button" title="Tạo ID từ tên bài hát và ca sĩ">
                                        <i data-lucide="wand-sparkles" style="width:16px;height:16px"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="song_name">Tên bài hát</label>
                                <div class="input-group">
                                    <input class="form-control" id="song_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                                    <button class="btn btn-secondary" id="song_title_case" type="button" title="Định dạng tên bài hát: in thường và viết hoa chữ cái đầu mỗi từ" aria-label="Định dạng tên bài hát">
                                        <i data-lucide="case-lower" style="width:16px;height:16px"></i>
                                    </button>
                                </div>
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
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label mb-0" for="song_artist_ids">Nghệ sĩ quản lý</label>
                                <button class="btn btn-sm btn-outline-success js-quick-add-song-artist" type="button" title="Thêm nhanh nghệ sĩ" aria-label="Thêm nhanh nghệ sĩ">
                                    <i data-lucide="plus" style="width:16px;height:16px"></i>
                                </button>
                            </div>
                            <select class="form-control js-music-artist-select" id="song_artist_ids" name="artist_ids[]" multiple>
                                <?php foreach ($songArtistOptions as $artistRow): ?>
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
                                <select class="form-control js-music-genre-select" id="song_genre" name="genre[]" multiple>
	                                    <?php foreach ($songGenres as $genreRow): ?>
	                                        <option value="<?= htmlspecialchars($genreRow['genre_id']) ?>" <?= in_array((string) $genreRow['genre_id'], $selectedSongGenreIds, true) ? 'selected' : '' ?>>
	                                            <?= htmlspecialchars($genreRow['title'] ?: $genreRow['genre_id']) ?>
	                                        </option>
	                                    <?php endforeach; ?>
                                        <?php
                                        $knownSongGenreIds = array_map(static fn(array $genreRow): string => (string) ($genreRow['genre_id'] ?? ''), $songGenres);
                                        foreach (array_diff($selectedSongGenreIds, $knownSongGenreIds) as $missingGenreId):
                                        ?>
                                            <option value="<?= htmlspecialchars($missingGenreId) ?>" selected><?= htmlspecialchars($missingGenreId) ?></option>
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
                                <button class="btn btn-outline-secondary js-copy-field" type="button" data-copy-target="#song_link_ytb" title="Copy YouTube" aria-label="Copy YouTube">
                                    <i data-lucide="copy" style="width:16px;height:16px"></i>
                                </button>
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
                            <form class="row g-2 align-items-end mb-3" method="get">
                                <input type="hidden" name="section" value="music">
                                <input type="hidden" name="tab" value="songs">
                                <div class="col-md-7">
                                    <label class="form-label" for="song_q">Search</label>
                                    <input class="form-control" id="song_q" name="song_q" value="<?= htmlspecialchars($songSearch) ?>" placeholder="Tên bài hát hoặc nghệ sĩ">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="song_lang_filter">Quốc gia</label>
                                    <select class="form-control js-country-filter-select" id="song_lang_filter" name="song_lang">
                                        <option value="">Tất cả</option>
                                        <?= admin_language_select_options($languageOptions, $songLangFilter) ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-secondary" type="submit">Lọc</button>
                                </div>
                            </form>
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
                            <table class="table table-striped table-hover table-sm align-middle music-song-table">
                                <thead><tr><th>Bài hát</th><th>Nghệ sĩ</th><th>Thể loại</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($songs as $song): ?>
	                                    <?php
	                                    $songEditParams = $songPageParams;
	                                    $songEditParams['edit'] = (string) $song['id'];
                                        $songArtistNames = array_values(array_filter(array_map('trim', explode(',', (string) ($song['artist_names'] ?? '')))));
                                        $songArtistIds = array_values(array_filter(array_map('trim', explode(',', (string) ($song['artist_ids'] ?? '')))));
                                        $songGenreLabels = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', (string) ($song['genre'] ?? '')) ?: [])));
	                                    ?>
                                    <tr>
                                        <td class="music-song-cell">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($song['avatar'])): ?>
                                                    <a href="<?= htmlspecialchars($musicPublicSongUrl($song['id'])) ?>" target="_blank">
                                                        <img src="<?= htmlspecialchars($song['avatar']) ?>" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:8px">
                                                    </a>
                                                <?php endif; ?>
                                                <div class="music-song-text">
                                                    <strong class="music-song-name" title="<?= htmlspecialchars($song['name'] ?? $song['id']) ?>"><?= htmlspecialchars($song['name'] ?? $song['id']) ?></strong>
                                                    <div class="small text-muted music-song-id" title="<?= htmlspecialchars($song['id']) ?>"><?= htmlspecialchars($song['id']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($songArtistNames && $songArtistIds): ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($songArtistNames as $artistIndex => $artistName): ?>
                                                        <?php $linkedArtistId = (int) ($songArtistIds[$artistIndex] ?? 0); ?>
                                                        <?php if ($linkedArtistId > 0): ?>
                                                            <div class="btn-group" role="group">
                                                                <a class="btn btn-sm btn-info border d-flex align-items-center justify-content-cente text-nowrap" type="button" href="<?= htmlspecialchars($musicPublicArtistUrl($linkedArtistId, $artistName)) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($artistName) ?></a>
                                                                <a class="btn btn-sm btn-secondary border d-flex align-items-center justify-content-cente" type="button" target="_blank" href="https://www.google.com/search?tbm=vid&q=<?= htmlspecialchars($artistName) ?>"><i data-lucide="user-round-search" style="width:15px;height:15px"></i></a>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge text-bg-light border"><?= htmlspecialchars($artistName) ?></span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <?= htmlspecialchars($song['artist'] ?? '') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php foreach ($songGenreLabels as $genreLabel): ?>
                                                <span class="badge text-bg-secondary"><?= htmlspecialchars($genreLabel) ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-warning" href="index.php?<?= htmlspecialchars(http_build_query($songEditParams)) ?>"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
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
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <label class="form-label mb-0" for="artist_description">Mô tả</label>
                                <button class="btn btn-sm btn-primary js-ai-song-artist-request" type="button">
                                    <span class="d-inline-flex align-items-center gap-2"><i data-lucide="wand-sparkles" style="width:16px;height:16px"></i>Yêu cầu AI</span>
                                </button>
                            </div>
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
                            <form class="row g-2 align-items-end mb-3" method="get">
                                <input type="hidden" name="section" value="music">
                                <input type="hidden" name="tab" value="artists">
                                <div class="col-md-7">
                                    <label class="form-label" for="artist_q">Search</label>
                                    <input class="form-control" id="artist_q" name="artist_q" value="<?= htmlspecialchars($artistSearch) ?>" placeholder="Tên nghệ sĩ">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="artist_lang_filter">Quốc gia</label>
                                    <select class="form-control js-country-filter-select" id="artist_lang_filter" name="artist_lang">
                                        <option value="">Tất cả</option>
                                        <?= admin_language_select_options($languageOptions, $artistLangFilter) ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-secondary" type="submit">Lọc</button>
                                </div>
                            </form>
	                        <div class="table-responsive-sm">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead><tr><th>Name</th><th>Lang</th><th>Mô tả</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($songArtists as $artistRow): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($artistRow['avatar'])): ?>
                                                    <a href="<?= htmlspecialchars($musicPublicArtistUrl($artistRow['id'], $artistRow['name'])) ?>" target="_blank">
                                                        <img src="<?= htmlspecialchars($artistRow['avatar']) ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:50%">
                                                    </a>
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($artistRow['name']) ?></strong>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($artistRow['lang_key'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(admin_excerpt(strip_tags($artistRow['description']) ?? '', 35)) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-dark" target="_blank" href="https://www.google.com/search?tbm=vid&q=<?= $artistRow['name'] ?>"><i data-lucide="user-round-search" style="width:15px;height:15px"></i></a>
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
                            <label class="form-label" for="genre_avatar">Avatar</label>
                            <div class="input-group">
                                <input class="form-control" id="genre_avatar" name="avatar" value="<?= htmlspecialchars($editing['avatar'] ?? '') ?>">
                                <button class="btn btn-secondary js-upload" type="button" data-target="genre_avatar" data-type-media="genre_avatar" data-mode="replace" data-accept="image/*">Upload</button>
                            </div>
                            <?php if (!empty($editing['avatar'])): ?>
                                <img class="mt-2 rounded-2 object-fit-cover" src="<?= htmlspecialchars($editing['avatar']) ?>" alt="" width="76" height="76">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <label class="form-label mb-0" for="genre_description">Description</label>
                                <button class="btn btn-sm btn-primary js-ai-song-genre-request" type="button">
                                    <span class="d-inline-flex align-items-center gap-2"><i data-lucide="wand-sparkles" style="width:16px;height:16px"></i>Yêu cầu AI</span>
                                </button>
                            </div>
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
                                <thead><tr><th>Genre</th><th>Avatar</th><th>Mô tả</th><th>Số bài</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($songGenres as $genreRow): ?>
                                    <tr>
                                        <td>
                                            <a class="fw-bold text-decoration-none" href="<?= htmlspecialchars($musicPublicGenreUrl($genreRow['genre_id'], $genreRow['title'] ?: $genreRow['genre_id'])) ?>" target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars($genreRow['title'] ?: $genreRow['genre_id']) ?>
                                            </a>
                                            <div class="small text-muted"><?= htmlspecialchars($genreRow['genre_id']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($genreRow['avatar'])): ?>
                                                <img src="<?= htmlspecialchars($genreRow['avatar']) ?>" alt="" width="54" height="54" class="rounded-2 object-fit-cover">
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(admin_excerpt(strip_tags($genreRow['description']) ?? '', 50)) ?></td>
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
                                <?php if (!$songGenres): ?><tr><td colspan="5" class="text-center text-muted py-4">Chưa có thể loại.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($musicTab === 'orders'): ?>
            <div class="glass-panel p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h5 mb-0">Đơn đặt hàng nhạc</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge text-bg-secondary"><?= number_format(count($songOrders)) ?> đơn</span>
                        <form class="js-delete d-inline-flex" method="post" data-confirm="Xóa tất cả đơn nhạc thanh toán không thành công? Các đơn COMPLETED sẽ được giữ lại.">
                            <input type="hidden" name="action" value="delete_failed_song_orders">
                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                Xóa đơn lỗi
                            </button>
                        </form>
                    </div>
                </div>
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

            <?php if ($musicTab === 'search_log'): ?>
            <div class="glass-panel p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Lịch sử tìm kiếm bài hát</h2>
                        <div class="muted-text small">Ghi lại lượt tìm kiếm từ thanh search header Heart Beat Play.</div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge text-bg-secondary"><?= number_format((int) ($songSearchLogStats['total_rows'] ?? 0)) ?> lượt</span>
                        <span class="badge text-bg-light border text-dark"><?= number_format((int) ($songSearchLogStats['unique_queries'] ?? 0)) ?> từ khóa</span>
                        <span class="badge text-bg-light border text-dark"><?= number_format((int) ($songSearchLogStats['unique_ips'] ?? 0)) ?> IP</span>
                        <form class="js-delete d-inline-flex" method="post" data-confirm="Xóa toàn bộ lịch sử tìm kiếm bài hát?">
                            <input type="hidden" name="action" value="clear_song_search_log">
                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                Xóa tất cả
                            </button>
                        </form>
                    </div>
                </div>
                <form class="row g-2 align-items-end mb-3" method="get">
                    <input type="hidden" name="section" value="music">
                    <input type="hidden" name="tab" value="search_log">
                    <div class="col-md-10">
                        <label class="form-label" for="search_log_q">Search</label>
                        <input class="form-control" id="search_log_q" name="search_log_q" value="<?= htmlspecialchars($songSearchLogQuery) ?>" placeholder="Từ khóa, lang, IP hoặc URL">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-secondary" type="submit">Lọc</button>
                    </div>
                </form>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="muted-text small"><?= number_format($songSearchLogTotal) ?> log phù hợp</div>
                    <div class="muted-text small">Trang <?= number_format($songSearchLogPage) ?>/<?= number_format($songSearchLogTotalPages) ?></div>
                </div>
                <?php
                $songSearchLogPageParams = $_GET;
                $songSearchLogPageParams['section'] = 'music';
                $songSearchLogPageParams['tab'] = 'search_log';
                ?>
                <?= admin_pagination($songSearchLogPageParams, 'search_log_page', $songSearchLogPage, $songSearchLogTotalPages, 'Phân trang lịch sử tìm kiếm', 'mb-3') ?>
                <div class="table-responsive-sm">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead><tr><th>Từ khóa</th><th>Lang</th><th>Kết quả</th><th>IP</th><th>Thiết bị</th><th>Thời gian</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($songSearchLogs as $log): ?>
                            <tr>
                                <td>
                                    <a class="fw-bold text-decoration-none" href="<?= htmlspecialchars($musicPublicBase . '/?q=' . urlencode($log['query'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= htmlspecialchars($log['query'] ?? '') ?>
                                    </a>
                                    <div class="small text-muted text-truncate" style="max-width:360px" title="<?= htmlspecialchars($log['request_path'] ?? '') ?>"><?= htmlspecialchars($log['request_path'] ?? '') ?></div>
                                </td>
                                <td><?= htmlspecialchars($log['lang'] ?? '') ?></td>
                                <td><?= number_format((int) ($log['result_count'] ?? 0)) ?></td>
                                <td class="font-monospace small"><?= htmlspecialchars($log['ip_text'] ?? '') ?></td>
                                <td class="small text-muted text-truncate" style="max-width:260px" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>"><?= htmlspecialchars($log['user_agent'] ?? '') ?></td>
                                <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                                <td class="text-end">
                                    <form class="d-inline js-confirm-delete" method="post">
                                        <input type="hidden" name="action" value="delete_song_search_log">
                                        <input type="hidden" name="id" value="<?= (int) $log['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$songSearchLogs): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có lịch sử tìm kiếm.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?= admin_pagination($songSearchLogPageParams, 'search_log_page', $songSearchLogPage, $songSearchLogTotalPages, 'Phân trang lịch sử tìm kiếm') ?>
            </div>
            <?php endif; ?>

            <script>
            const songSlugify = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[đĐ]/g, 'd')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');
            const songEscapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

            const songGenerateIdButton = document.getElementById('song_generate_id');
            if (songGenerateIdButton) {
                songGenerateIdButton.addEventListener('click', () => {
                    const idInput = document.getElementById('song_id');
                    const nameInput = document.getElementById('song_name');
                    const artistInput = document.getElementById('song_artist');
                    const slug = songSlugify([nameInput?.value || '', artistInput?.value || ''].filter(Boolean).join('-'));
                    if (idInput && slug) idInput.value = slug;
                });
            }

            const songTitleCaseButton = document.getElementById('song_title_case');
            if (songTitleCaseButton) {
                songTitleCaseButton.addEventListener('click', () => {
                    const nameInput = document.getElementById('song_name');
                    if (!nameInput) return;
                    nameInput.value = String(nameInput.value || '')
                        .toLocaleLowerCase('vi-VN')
                        .replace(/(^|\s)(\S)/gu, (match, prefix, firstChar) => prefix + firstChar.toLocaleUpperCase('vi-VN'));
                    nameInput.focus();
                });
            }

            const songFillFromPayload = (payload) => {
                const fieldMap = {
                    id: 'song_id',
                    name: 'song_name',
                    artist: 'song_artist',
                    album: 'song_album',
                    lang: 'song_lang',
                    year: 'song_year',
                    date: 'song_date',
                    publishedAt: 'song_publishedAt',
                    link_ytb: 'song_link_ytb',
                    mp3: 'song_mp3',
                    avatar: 'song_avatar',
                    lyrics: 'song_lyrics',
                };
                Object.entries(fieldMap).forEach(([key, id]) => {
                    const input = document.getElementById(id);
                    if (input && typeof payload[key] !== 'undefined' && String(payload[key]).trim() !== '') {
                        input.value = payload[key];
                        input.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                });

                const genreSelect = document.getElementById('song_genre');
                if (genreSelect && payload.genre) {
                    const genreValue = String(payload.genre).trim();
                    let option = Array.from(genreSelect.options).find((item) => item.value === genreValue);
                    if (!option) {
                        option = new Option(genreValue, genreValue, true, true);
                        genreSelect.add(option);
                    }
                    option.selected = true;
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery(genreSelect).trigger('change');
                    } else {
                        genreSelect.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                }

                const artistSelect = document.getElementById('song_artist_ids');
                if (artistSelect && payload.artist_option && payload.artist_option.id) {
                    const artistId = String(payload.artist_option.id);
                    const artistName = String(payload.artist_option.name || payload.artist || artistId);
                    let option = Array.from(artistSelect.options).find((item) => item.value === artistId);
                    if (!option) {
                        option = new Option(artistName, artistId, true, true);
                        artistSelect.add(option);
                    }
                    option.selected = true;
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery(artistSelect).trigger('change');
                    } else {
                        artistSelect.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                }
            };

            const aiRandomSongButton = document.getElementById('song_ai_random');
            if (aiRandomSongButton) {
                aiRandomSongButton.addEventListener('click', async () => {
                    const langInput = document.getElementById('song_lang');
                    const formData = new FormData();
                    formData.append('action', 'ajax_ai_random_song');
                    formData.append('lang', langInput && langInput.value ? langInput.value : 'vi');

                    const originalHtml = aiRandomSongButton.innerHTML;
                    try {
                        aiRandomSongButton.disabled = true;
                        aiRandomSongButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> AI đang tìm';
                        Swal.fire({
                            title: 'AI đang tìm bài hát',
                            text: 'AI đang nghĩ keyword, lấy 20 kết quả YouTube, random MV, kiểm tra trùng link và lấy lyrics ngoài web...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading(),
                        });

                        const response = await fetch('index.php?section=music&tab=songs', {
                            method: 'POST',
                            body: formData,
                        });
                        const payload = await response.json();
                        if (!response.ok || payload.status !== 'success') {
                            throw new Error(payload.message || 'AI chưa tìm được bài hát phù hợp.');
                        }

                        songFillFromPayload(payload);
                        await Swal.fire({
                            icon: 'success',
                            title: 'Đã điền bài hát bằng AI',
                            html: `<div class="text-start"><strong>${songEscapeHtml(payload.name)}</strong><br><span>${songEscapeHtml(payload.artist)}</span><br><small>${songEscapeHtml(payload.youtube_title || payload.link_ytb || '')}</small></div>`,
                            confirmButtonText: 'Kiểm tra form',
                        });
                    } catch (error) {
                        await Swal.fire({
                            icon: 'warning',
                            title: 'AI chưa thêm được bài hát',
                            text: error.message,
                        });
                    } finally {
                        aiRandomSongButton.disabled = false;
                        aiRandomSongButton.innerHTML = originalHtml;
                    }
                });
            }

            const youtubeButton = document.getElementById('song_load_youtube');
            if (youtubeButton) {
                youtubeButton.addEventListener('click', async () => {
                    const linkInput = document.getElementById('song_link_ytb');
                    const currentIdInput = document.querySelector('input[name="original_id"]');
                    const formData = new FormData();
                    formData.append('action', 'ajax_youtube_song');
                    formData.append('url', linkInput ? linkInput.value : '');
                    formData.append('current_id', currentIdInput ? currentIdInput.value : '');
                    try {
                        youtubeButton.disabled = true;
                        const response = await fetch('index.php?section=music', {method: 'POST', body: formData});
                        const payload = await response.json();
                        if (!response.ok || payload.status !== 'success') {
                            const error = new Error(payload.message || 'Không load được YouTube.');
                            error.status = response.status;
                            throw error;
                        }
                        if ((!payload.year || !payload.date) && payload.publishedAt) {
                            const dateMatch = String(payload.publishedAt).match(/^(\d{4})-(\d{2})-(\d{2})/);
                            if (dateMatch) {
                                payload.year = payload.year || dateMatch[1];
                                payload.date = payload.date || `${dateMatch[1]}-${dateMatch[2]}-${dateMatch[3]}`;
                            }
                        }
                        payload.album = payload.album || payload.artist;
                        songFillFromPayload(payload);
                        Swal.fire({icon: 'success', title: 'Đã lấy dữ liệu YouTube', timer: 1300, showConfirmButton: false});
                    } catch (error) {
                        Swal.fire({
                            icon: error.status === 409 ? 'warning' : 'error',
                            title: error.status === 409 ? 'Link YouTube đã tồn tại' : 'YouTube API',
                            text: error.message
                        });
                    } finally {
                        youtubeButton.disabled = false;
                    }
                });
            }
            </script>
            <?php endif; ?>
