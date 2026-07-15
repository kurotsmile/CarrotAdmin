<?php

function admin_ensure_page_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          slug VARCHAR(180) NOT NULL,
          lang VARCHAR(12) NOT NULL DEFAULT 'vi',
          title VARCHAR(255) NOT NULL,
          content_html LONGTEXT NOT NULL,
          seo_title VARCHAR(255) NULL,
          seo_description VARCHAR(320) NULL,
          seo_keywords VARCHAR(500) NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_page_slug_lang (slug, lang),
          KEY idx_page_lang (lang),
          FULLTEXT KEY ft_page_seo (title, seo_title, seo_description, seo_keywords)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $indexes = $pdo->query("SHOW INDEX FROM page")->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    foreach (['idx_page_lang_status', 'idx_page_status_priority'] as $oldIndex) {
        if (in_array($oldIndex, $indexNames, true)) {
            $pdo->exec('ALTER TABLE page DROP INDEX ' . $oldIndex);
        }
    }
    if (!in_array('idx_page_lang', $indexNames, true)) {
        $pdo->exec('ALTER TABLE page ADD KEY idx_page_lang (lang)');
    }

    $columns = $pdo->query("SHOW COLUMNS FROM page")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['status', 'priority', 'published_at'] as $oldColumn) {
        if (in_array($oldColumn, $columns, true)) {
            $pdo->exec('ALTER TABLE page DROP COLUMN ' . $oldColumn);
        }
    }
}

function admin_ensure_user_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
          id INTEGER PRIMARY KEY AUTO_INCREMENT,
          address TEXT DEFAULT NULL,
          avatar TEXT DEFAULT NULL,
          created_at TEXT DEFAULT NULL,
          email TEXT DEFAULT NULL,
          lang TEXT DEFAULT NULL,
          name TEXT DEFAULT NULL,
          password TEXT DEFAULT NULL,
          phone TEXT DEFAULT NULL,
          role TEXT DEFAULT NULL,
          sex TEXT DEFAULT NULL,
          status_share TEXT DEFAULT NULL,
          type TEXT DEFAULT NULL,
          birthday TEXT DEFAULT NULL,
          KEY idx_users_email (email(191)),
          KEY idx_users_role (role(64))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['address', 'avatar', 'created_at', 'email', 'lang', 'name', 'password', 'phone', 'role', 'sex', 'status_share', 'type', 'birthday'] as $column) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE users ADD `' . $column . '` TEXT DEFAULT NULL');
        }
    }
}

function admin_ensure_api_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_config (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          provider VARCHAR(64) NOT NULL,
          name VARCHAR(160) NOT NULL,
          enabled TINYINT(1) NOT NULL DEFAULT 0,
          client_id TEXT DEFAULT NULL,
          client_secret TEXT DEFAULT NULL,
          api_key TEXT DEFAULT NULL,
          project_url TEXT DEFAULT NULL,
          redirect_uri TEXT DEFAULT NULL,
          scopes TEXT DEFAULT NULL,
          config_json LONGTEXT DEFAULT NULL,
          note TEXT DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_api_provider_enabled (provider, enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM api_config')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'provider' => 'VARCHAR(64) NOT NULL DEFAULT "custom"',
        'name' => 'VARCHAR(160) NOT NULL DEFAULT "API"',
        'enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'client_id' => 'TEXT DEFAULT NULL',
        'client_secret' => 'TEXT DEFAULT NULL',
        'api_key' => 'TEXT DEFAULT NULL',
        'project_url' => 'TEXT DEFAULT NULL',
        'redirect_uri' => 'TEXT DEFAULT NULL',
        'scopes' => 'TEXT DEFAULT NULL',
        'config_json' => 'LONGTEXT DEFAULT NULL',
        'note' => 'TEXT DEFAULT NULL',
        'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ];

    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE api_config ADD `' . $column . '` ' . $sql);
        }
    }
}

function admin_ensure_app_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app (
          id varchar(255) NOT NULL,
          decription longtext DEFAULT NULL,
          github longtext DEFAULT NULL,
          microsoft_store longtext DEFAULT NULL,
          icon longtext DEFAULT NULL,
          itch longtext DEFAULT NULL,
          exe_file longtext DEFAULT NULL,
          ipa_file longtext DEFAULT NULL,
          deb_file longtext DEFAULT NULL,
          amazon_app_store longtext DEFAULT NULL,
          huawei_store longtext DEFAULT NULL,
          youtube_link longtext DEFAULT NULL,
          google_play longtext DEFAULT NULL,
          dmg_file longtext DEFAULT NULL,
          uptodown longtext DEFAULT NULL,
          simmer longtext DEFAULT NULL,
          type longtext DEFAULT NULL,
          apk_file longtext DEFAULT NULL,
          status longtext DEFAULT NULL,
          sync_status int(11) DEFAULT 0,
          priority int(11) DEFAULT 0,
          price decimal(10,2) NOT NULL DEFAULT 0.00,
          category longtext DEFAULT NULL,
          created_at datetime DEFAULT current_timestamp(),
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM app')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('category', $columns, true)) {
        $pdo->exec('ALTER TABLE app ADD category LONGTEXT DEFAULT NULL AFTER price');
    }
}

function admin_ensure_app_store_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_store (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          slug VARCHAR(120) NOT NULL,
          title VARCHAR(255) NOT NULL,
          description LONGTEXT DEFAULT NULL,
          icon LONGTEXT DEFAULT NULL,
          link LONGTEXT DEFAULT NULL,
          platform VARCHAR(120) DEFAULT NULL,
          sort_order INT NOT NULL DEFAULT 0,
          status VARCHAR(24) NOT NULL DEFAULT 'active',
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_app_store_slug (slug),
          KEY idx_app_store_status_sort (status, sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM app_store')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('platform', $columns, true)) {
        $pdo->exec("ALTER TABLE app_store ADD platform VARCHAR(120) DEFAULT NULL AFTER link");
    }
    if (!in_array('sort_order', $columns, true)) {
        $pdo->exec("ALTER TABLE app_store ADD sort_order INT NOT NULL DEFAULT 0 AFTER platform");
    }
    if (!in_array('status', $columns, true)) {
        $pdo->exec("ALTER TABLE app_store ADD status VARCHAR(24) NOT NULL DEFAULT 'active' AFTER sort_order");
    }
}

function admin_ensure_app_view_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_view (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          app_id VARCHAR(255) NOT NULL,
          view_date DATE NOT NULL,
          ip_address VARBINARY(16) NOT NULL,
          ip_text VARCHAR(45) NOT NULL,
          first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          hits INT UNSIGNED NOT NULL DEFAULT 1,
          user_agent VARCHAR(512) DEFAULT NULL,
          referer VARCHAR(1024) DEFAULT NULL,
          request_path VARCHAR(1024) DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_app_view_daily_ip (app_id, view_date, ip_address),
          KEY idx_app_view_app_id (app_id),
          KEY idx_app_view_app_date (app_id, view_date),
          KEY idx_app_view_date (view_date),
          CONSTRAINT fk_app_view_app
            FOREIGN KEY (app_id) REFERENCES app (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM app_view')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'view_date' => 'DATE NOT NULL AFTER app_id',
        'ip_address' => 'VARBINARY(16) NOT NULL AFTER view_date',
        'ip_text' => 'VARCHAR(45) NOT NULL AFTER ip_address',
        'first_seen_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ip_text',
        'last_seen_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER first_seen_at',
        'hits' => 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER last_seen_at',
        'user_agent' => 'VARCHAR(512) DEFAULT NULL AFTER hits',
        'referer' => 'VARCHAR(1024) DEFAULT NULL AFTER user_agent',
        'request_path' => 'VARCHAR(1024) DEFAULT NULL AFTER referer',
    ];
    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE app_view ADD `' . $column . '` ' . $sql);
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM app_view')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    if (!in_array('uq_app_view_daily_ip', $indexNames, true)) {
        $pdo->exec('ALTER TABLE app_view ADD UNIQUE KEY uq_app_view_daily_ip (app_id, view_date, ip_address)');
    }
    if (!in_array('idx_app_view_app_id', $indexNames, true)) {
        $pdo->exec('ALTER TABLE app_view ADD KEY idx_app_view_app_id (app_id)');
    }
    if (!in_array('idx_app_view_app_date', $indexNames, true)) {
        $pdo->exec('ALTER TABLE app_view ADD KEY idx_app_view_app_date (app_id, view_date)');
    }
}

function admin_ensure_song_view_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_view (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          song_id VARCHAR(255) NOT NULL,
          view_date DATE NOT NULL,
          ip_address VARBINARY(16) NOT NULL,
          ip_text VARCHAR(45) NOT NULL,
          first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          hits INT UNSIGNED NOT NULL DEFAULT 1,
          user_agent VARCHAR(512) DEFAULT NULL,
          referer VARCHAR(1024) DEFAULT NULL,
          request_path VARCHAR(1024) DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_song_view_daily_ip (song_id, view_date, ip_address),
          KEY idx_song_view_song_id (song_id),
          KEY idx_song_view_song_date (song_id, view_date),
          KEY idx_song_view_date (view_date),
          CONSTRAINT fk_song_view_song
            FOREIGN KEY (song_id) REFERENCES song (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM song_view')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'view_date' => 'DATE NOT NULL AFTER song_id',
        'ip_address' => 'VARBINARY(16) NOT NULL AFTER view_date',
        'ip_text' => 'VARCHAR(45) NOT NULL AFTER ip_address',
        'first_seen_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ip_text',
        'last_seen_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER first_seen_at',
        'hits' => 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER last_seen_at',
        'user_agent' => 'VARCHAR(512) DEFAULT NULL AFTER hits',
        'referer' => 'VARCHAR(1024) DEFAULT NULL AFTER user_agent',
        'request_path' => 'VARCHAR(1024) DEFAULT NULL AFTER referer',
    ];
    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE song_view ADD `' . $column . '` ' . $sql);
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM song_view')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    if (!in_array('uq_song_view_daily_ip', $indexNames, true)) {
        $pdo->exec('ALTER TABLE song_view ADD UNIQUE KEY uq_song_view_daily_ip (song_id, view_date, ip_address)');
    }
    if (!in_array('idx_song_view_song_id', $indexNames, true)) {
        $pdo->exec('ALTER TABLE song_view ADD KEY idx_song_view_song_id (song_id)');
    }
    if (!in_array('idx_song_view_song_date', $indexNames, true)) {
        $pdo->exec('ALTER TABLE song_view ADD KEY idx_song_view_song_date (song_id, view_date)');
    }
}

function admin_ensure_song_search_log_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_search_log (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          query VARCHAR(255) NOT NULL,
          normalized_query VARCHAR(255) NOT NULL,
          lang VARCHAR(24) DEFAULT NULL,
          result_count INT UNSIGNED NOT NULL DEFAULT 0,
          ip_address VARBINARY(16) DEFAULT NULL,
          ip_text VARCHAR(45) DEFAULT NULL,
          user_agent VARCHAR(512) DEFAULT NULL,
          referer VARCHAR(1024) DEFAULT NULL,
          request_path VARCHAR(1024) DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_song_search_log_created (created_at),
          KEY idx_song_search_log_query (normalized_query),
          KEY idx_song_search_log_lang_created (lang, created_at),
          KEY idx_song_search_log_ip_created (ip_text, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM song_search_log')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'normalized_query' => 'VARCHAR(255) NOT NULL DEFAULT "" AFTER query',
        'lang' => 'VARCHAR(24) DEFAULT NULL AFTER normalized_query',
        'result_count' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER lang',
        'ip_address' => 'VARBINARY(16) DEFAULT NULL AFTER result_count',
        'ip_text' => 'VARCHAR(45) DEFAULT NULL AFTER ip_address',
        'user_agent' => 'VARCHAR(512) DEFAULT NULL AFTER ip_text',
        'referer' => 'VARCHAR(1024) DEFAULT NULL AFTER user_agent',
        'request_path' => 'VARCHAR(1024) DEFAULT NULL AFTER referer',
        'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE song_search_log ADD `' . $column . '` ' . $sql);
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM song_search_log')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    if (!in_array('idx_song_search_log_created', $indexNames, true)) {
        $pdo->exec('ALTER TABLE song_search_log ADD KEY idx_song_search_log_created (created_at)');
    }
    if (!in_array('idx_song_search_log_query', $indexNames, true)) {
        $pdo->exec('ALTER TABLE song_search_log ADD KEY idx_song_search_log_query (normalized_query)');
    }
    if (!in_array('idx_song_search_log_lang_created', $indexNames, true)) {
        $pdo->exec('ALTER TABLE song_search_log ADD KEY idx_song_search_log_lang_created (lang, created_at)');
    }
}

function admin_ensure_app_category_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_category (
          category_id VARCHAR(120) NOT NULL,
          icon LONGTEXT DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_category_content (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          category_id VARCHAR(120) NOT NULL,
          title VARCHAR(255) NOT NULL,
          description LONGTEXT DEFAULT NULL,
          key_lang VARCHAR(24) NOT NULL DEFAULT 'en',
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_app_category_content_lang (category_id, key_lang),
          KEY idx_app_category_content_lang (key_lang),
          CONSTRAINT fk_app_category_content_category
            FOREIGN KEY (category_id) REFERENCES app_category (category_id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS category_app (
          app_id VARCHAR(255) NOT NULL,
          category_id VARCHAR(120) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (app_id, category_id),
          KEY idx_category_app_category (category_id),
          CONSTRAINT fk_category_app_app
            FOREIGN KEY (app_id) REFERENCES app (id)
            ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT fk_category_app_category
            FOREIGN KEY (category_id) REFERENCES app_category (category_id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $legacyRows = $pdo->query('SELECT id, category FROM app WHERE TRIM(COALESCE(category, "")) <> ""')->fetchAll(PDO::FETCH_ASSOC);
    if ($legacyRows) {
        $categoryInsert = $pdo->prepare('INSERT IGNORE INTO app_category (category_id) VALUES (?)');
        $mappingInsert = $pdo->prepare('INSERT IGNORE INTO category_app (app_id, category_id) VALUES (?, ?)');
        foreach ($legacyRows as $row) {
            $categoryIds = preg_split('/\s*,\s*/', (string) ($row['category'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($categoryIds as $categoryId) {
                $categoryId = trim((string) $categoryId);
                if ($categoryId === '') {
                    continue;
                }
                if (strlen($categoryId) > 120) {
                    $categoryId = substr($categoryId, 0, 120);
                }
                $categoryInsert->execute([$categoryId]);
                $mappingInsert->execute([(string) $row['id'], $categoryId]);
            }
        }
    }
}

function admin_ensure_app_photo_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_photo (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          app_id VARCHAR(255) NOT NULL,
          image_url LONGTEXT NOT NULL,
          display_mode VARCHAR(16) NOT NULL DEFAULT 'vertical',
          sort_order INT NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_app_photo_app_sort (app_id, sort_order, id),
          CONSTRAINT fk_app_photo_app
            FOREIGN KEY (app_id) REFERENCES app (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM app_photo')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('display_mode', $columns, true)) {
        $pdo->exec("ALTER TABLE app_photo ADD display_mode VARCHAR(16) NOT NULL DEFAULT 'vertical' AFTER image_url");
    }
}

function admin_ensure_app_content_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_content (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          app_id VARCHAR(255) NOT NULL,
          lang_key VARCHAR(24) NOT NULL,
          title VARCHAR(255) DEFAULT NULL,
          content_html LONGTEXT NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_app_content_app_lang (app_id, lang_key),
          KEY idx_app_content_lang_key (lang_key),
          CONSTRAINT fk_app_content_app
            FOREIGN KEY (app_id) REFERENCES app (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM app_content')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('title', $columns, true)) {
        $pdo->exec('ALTER TABLE app_content ADD title VARCHAR(255) DEFAULT NULL AFTER lang_key');
    }
}

function admin_ensure_song_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song (
          id VARCHAR(255) NOT NULL COMMENT 'id duy nhất cho bài hát',
          name TEXT DEFAULT NULL COMMENT 'tên bài hát',
          artist TEXT DEFAULT NULL COMMENT 'tên ca sĩ',
          album TEXT DEFAULT NULL COMMENT 'album',
          genre VARCHAR(255) DEFAULT NULL COMMENT 'thể loại',
          lang VARCHAR(16) DEFAULT 'vi' COMMENT 'ngôn ngữ',
          year VARCHAR(32) DEFAULT NULL COMMENT 'năm phát hành',
          date VARCHAR(32) DEFAULT NULL COMMENT 'ngày',
          publishedAt VARCHAR(64) DEFAULT NULL COMMENT 'ngày xuất bản (ISO)',
          link_ytb TEXT DEFAULT NULL COMMENT 'liên kết youtube',
          mp3 TEXT DEFAULT NULL COMMENT 'link file mp3',
          avatar TEXT DEFAULT NULL COMMENT 'ảnh bài hát',
          lyrics LONGTEXT DEFAULT NULL COMMENT 'lời bài hát (HTML)',
          created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM song')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'name' => "TEXT DEFAULT NULL COMMENT 'tên bài hát' AFTER id",
        'artist' => "TEXT DEFAULT NULL COMMENT 'tên ca sĩ' AFTER name",
        'album' => "TEXT DEFAULT NULL COMMENT 'album' AFTER artist",
        'genre' => "VARCHAR(255) DEFAULT NULL COMMENT 'thể loại' AFTER album",
        'lang' => "VARCHAR(16) DEFAULT 'vi' COMMENT 'ngôn ngữ' AFTER genre",
        'year' => "VARCHAR(32) DEFAULT NULL COMMENT 'năm phát hành' AFTER lang",
        'date' => "VARCHAR(32) DEFAULT NULL COMMENT 'ngày' AFTER year",
        'publishedAt' => "VARCHAR(64) DEFAULT NULL COMMENT 'ngày xuất bản (ISO)' AFTER date",
        'link_ytb' => "TEXT DEFAULT NULL COMMENT 'liên kết youtube' AFTER publishedAt",
        'mp3' => "TEXT DEFAULT NULL COMMENT 'link file mp3' AFTER link_ytb",
        'avatar' => "TEXT DEFAULT NULL COMMENT 'ảnh bài hát' AFTER mp3",
        'lyrics' => "LONGTEXT DEFAULT NULL COMMENT 'lời bài hát (HTML)' AFTER avatar",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER lyrics',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    ];

    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE song ADD `' . $column . '` ' . $sql);
        }
    }

    $columns = $pdo->query('SHOW COLUMNS FROM song')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['price', 'status', 'sync_status'] as $oldColumn) {
        if (in_array($oldColumn, $columns, true)) {
            $pdo->exec('ALTER TABLE song DROP COLUMN `' . $oldColumn . '`');
        }
    }
}

function admin_ensure_music_tables(PDO $pdo): void
{
    admin_ensure_song_table($pdo);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_artist (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          name VARCHAR(255) NOT NULL,
          avatar TEXT DEFAULT NULL,
          description TEXT DEFAULT NULL,
          lang_key VARCHAR(24) NOT NULL DEFAULT 'vi',
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_song_artist_name_lang (name, lang_key),
          KEY idx_song_artist_lang (lang_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_artist_map (
          song_id VARCHAR(255) NOT NULL,
          artist_id BIGINT UNSIGNED NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (song_id, artist_id),
          KEY idx_song_artist_map_artist (artist_id),
          CONSTRAINT fk_song_artist_map_song
            FOREIGN KEY (song_id) REFERENCES song (id)
            ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT fk_song_artist_map_artist
            FOREIGN KEY (artist_id) REFERENCES song_artist (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_genre (
          genre_id VARCHAR(120) NOT NULL,
          title VARCHAR(255) DEFAULT NULL,
          avatar TEXT DEFAULT NULL,
          description TEXT DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (genre_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_orders (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          song_id VARCHAR(255) NOT NULL,
          user_id BIGINT UNSIGNED DEFAULT NULL,
          paypal_order_id VARCHAR(128) NOT NULL,
          status VARCHAR(40) NOT NULL DEFAULT 'CREATED',
          amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          currency VARCHAR(8) NOT NULL DEFAULT 'USD',
          payer_email VARCHAR(255) DEFAULT NULL,
          paypal_payload JSON DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          paid_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY uq_song_orders_paypal_order_id (paypal_order_id),
          KEY idx_song_orders_song_id (song_id),
          KEY idx_song_orders_user_id (user_id),
          KEY idx_song_orders_status_created (status, created_at),
          CONSTRAINT fk_song_orders_song
            FOREIGN KEY (song_id) REFERENCES song (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $artistColumns = $pdo->query('SHOW COLUMNS FROM song_artist')->fetchAll(PDO::FETCH_COLUMN);
    $artistColumnSql = [
        'avatar' => 'TEXT DEFAULT NULL AFTER name',
        'description' => 'TEXT DEFAULT NULL AFTER avatar',
        'lang_key' => "VARCHAR(24) NOT NULL DEFAULT 'vi' AFTER description",
    ];
    foreach ($artistColumnSql as $column => $sql) {
        if (!in_array($column, $artistColumns, true)) {
            $pdo->exec('ALTER TABLE song_artist ADD `' . $column . '` ' . $sql);
        }
    }

    $genreColumns = $pdo->query('SHOW COLUMNS FROM song_genre')->fetchAll(PDO::FETCH_COLUMN);
    $genreColumnSql = [
        'avatar' => 'TEXT DEFAULT NULL AFTER title',
        'description' => 'TEXT DEFAULT NULL AFTER avatar',
    ];
    foreach ($genreColumnSql as $column => $sql) {
        if (!in_array($column, $genreColumns, true)) {
            $pdo->exec('ALTER TABLE song_genre ADD `' . $column . '` ' . $sql);
        }
    }

    admin_ensure_song_view_table($pdo);
    admin_ensure_song_search_log_table($pdo);
}

function admin_ensure_app_order_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_orders (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          app_id VARCHAR(255) NOT NULL,
          user_id BIGINT UNSIGNED DEFAULT NULL,
          paypal_order_id VARCHAR(128) NOT NULL,
          status VARCHAR(40) NOT NULL DEFAULT 'CREATED',
          amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          currency VARCHAR(8) NOT NULL DEFAULT 'USD',
          payer_email VARCHAR(255) DEFAULT NULL,
          paypal_payload JSON DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          paid_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY uq_app_orders_paypal_order_id (paypal_order_id),
          KEY idx_app_orders_app_id (app_id),
          KEY idx_app_orders_user_id (user_id),
          KEY idx_app_orders_status_created (status, created_at),
          CONSTRAINT fk_app_orders_app
            FOREIGN KEY (app_id) REFERENCES app (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM app_orders')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'user_id' => 'BIGINT UNSIGNED DEFAULT NULL AFTER app_id',
        'currency' => "VARCHAR(8) NOT NULL DEFAULT 'USD' AFTER amount",
        'payer_email' => 'VARCHAR(255) DEFAULT NULL AFTER currency',
        'paypal_payload' => 'JSON DEFAULT NULL AFTER payer_email',
        'paid_at' => 'TIMESTAMP NULL DEFAULT NULL AFTER created_at',
    ];
    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE app_orders ADD `' . $column . '` ' . $sql);
        }
    }
}

function admin_ensure_ebook_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ebook_categories (
          id VARCHAR(255) NOT NULL,
          name VARCHAR(255) NOT NULL,
          description TEXT DEFAULT NULL,
          created_at VARCHAR(64) NOT NULL DEFAULT '',
          updated_at VARCHAR(64) NOT NULL DEFAULT '',
          PRIMARY KEY (id),
          UNIQUE KEY uniq_ebook_categories_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ebook (
          id VARCHAR(255) NOT NULL,
          name TEXT NOT NULL,
          author TEXT DEFAULT NULL,
          category_id VARCHAR(255) DEFAULT NULL,
          lang VARCHAR(16) DEFAULT 'en',
          price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          currency VARCHAR(16) NOT NULL DEFAULT 'USD',
          is_free TINYINT(1) NOT NULL DEFAULT 0,
          status VARCHAR(32) NOT NULL DEFAULT 'draft',
          cover TEXT DEFAULT NULL,
          preview_file TEXT DEFAULT NULL,
          description LONGTEXT DEFAULT NULL,
          published_at VARCHAR(64) DEFAULT NULL,
          created_at VARCHAR(64) NOT NULL DEFAULT '',
          updated_at VARCHAR(64) NOT NULL DEFAULT '',
          PRIMARY KEY (id),
          KEY idx_ebook_status (status),
          KEY idx_ebook_category_id (category_id),
          KEY idx_ebook_name (name(191)),
          KEY idx_ebook_author (author(191)),
          KEY idx_ebook_updated_at (updated_at),
          CONSTRAINT fk_ebook_category_id
            FOREIGN KEY (category_id) REFERENCES ebook_categories (id)
            ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ebook_store_links (
          id VARCHAR(255) NOT NULL,
          ebook_id VARCHAR(255) NOT NULL,
          store_id VARCHAR(255) NOT NULL DEFAULT '',
          store_name VARCHAR(255) NOT NULL DEFAULT '',
          store_icon VARCHAR(255) DEFAULT '',
          url TEXT NOT NULL,
          is_primary TINYINT(1) NOT NULL DEFAULT 0,
          sort_order INT NOT NULL DEFAULT 0,
          created_at VARCHAR(64) NOT NULL DEFAULT '',
          updated_at VARCHAR(64) NOT NULL DEFAULT '',
          PRIMARY KEY (id),
          UNIQUE KEY uniq_ebook_store_links_ebook_store (ebook_id, store_id),
          KEY idx_ebook_store_links_ebook_id (ebook_id),
          KEY idx_ebook_store_links_store_id (store_id),
          KEY idx_ebook_store_links_sort_order (sort_order),
          CONSTRAINT fk_ebook_store_links_ebook_id
            FOREIGN KEY (ebook_id) REFERENCES ebook (id)
            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ebook_orders (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          ebook_id VARCHAR(255) NOT NULL,
          user_id BIGINT UNSIGNED DEFAULT NULL,
          paypal_order_id VARCHAR(128) NOT NULL,
          status VARCHAR(40) NOT NULL DEFAULT 'CREATED',
          amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          currency VARCHAR(8) NOT NULL DEFAULT 'USD',
          payer_email VARCHAR(255) DEFAULT NULL,
          paypal_payload JSON DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          paid_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY uq_ebook_orders_paypal_order_id (paypal_order_id),
          KEY idx_ebook_orders_ebook_id (ebook_id),
          KEY idx_ebook_orders_user_id (user_id),
          KEY idx_ebook_orders_status_created (status, created_at),
          CONSTRAINT fk_ebook_orders_ebook
            FOREIGN KEY (ebook_id) REFERENCES ebook (id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function admin_ensure_paypal_config_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS paypal_config (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          site VARCHAR(32) NOT NULL,
          enabled TINYINT(1) NOT NULL DEFAULT 0,
          active_mode ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
          sandbox_client_id TEXT DEFAULT NULL,
          sandbox_client_secret TEXT DEFAULT NULL,
          live_client_id TEXT DEFAULT NULL,
          live_client_secret TEXT DEFAULT NULL,
          currency VARCHAR(8) NOT NULL DEFAULT 'USD',
          amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_paypal_config_site (site)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function admin_ensure_ai_support_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ai_support (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          provider VARCHAR(32) NOT NULL DEFAULT 'gemini',
          account_name VARCHAR(120) NOT NULL DEFAULT 'Gemini account',
          enabled TINYINT(1) NOT NULL DEFAULT 0,
          api_key TEXT DEFAULT NULL,
          model VARCHAR(120) NOT NULL DEFAULT 'gemini-3.5-flash',
          endpoint VARCHAR(255) NOT NULL DEFAULT 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
          temperature DECIMAL(4,2) NOT NULL DEFAULT 0.20,
          system_prompt TEXT DEFAULT NULL,
          priority INT NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_ai_support_provider_enabled (provider, enabled, priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM ai_support')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('account_name', $columns, true)) {
        $pdo->exec("ALTER TABLE ai_support ADD account_name VARCHAR(120) NOT NULL DEFAULT 'Gemini account' AFTER provider");
    }
    if (!in_array('priority', $columns, true)) {
        $pdo->exec('ALTER TABLE ai_support ADD priority INT NOT NULL DEFAULT 0 AFTER system_prompt');
    }

    $indexes = $pdo->query('SHOW INDEX FROM ai_support')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    if (in_array('uq_ai_support_provider', $indexNames, true)) {
        $pdo->exec('ALTER TABLE ai_support DROP INDEX uq_ai_support_provider');
    }
    if (!in_array('idx_ai_support_provider_enabled', $indexNames, true)) {
        $pdo->exec('ALTER TABLE ai_support ADD KEY idx_ai_support_provider_enabled (provider, enabled, priority)');
    }
}

function admin_ensure_country_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS country (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          icon LONGTEXT DEFAULT NULL,
          name VARCHAR(120) NOT NULL,
          lang_key VARCHAR(24) NOT NULL,
          lang_country VARCHAR(24) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_country_lang (lang_key, lang_country),
          KEY idx_country_name (name),
          KEY idx_country_lang_country (lang_country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $indexes = $pdo->query("SHOW INDEX FROM country")->fetchAll(PDO::FETCH_ASSOC);
    $hasOldLangKeyIndex = false;
    $hasLangCountryIndex = false;

    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'uq_country_lang_key') {
            $hasOldLangKeyIndex = true;
        }
        if (($index['Key_name'] ?? '') === 'uq_country_lang') {
            $hasLangCountryIndex = true;
        }
    }

    if ($hasOldLangKeyIndex) {
        $pdo->exec('ALTER TABLE country DROP INDEX uq_country_lang_key');
    }

    if (!$hasLangCountryIndex) {
        $pdo->exec('ALTER TABLE country ADD UNIQUE KEY uq_country_lang (lang_key, lang_country)');
    }
}

function admin_ensure_text_label_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS text_label (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          `key` VARCHAR(120) NOT NULL,
          lang_key VARCHAR(24) NOT NULL,
          value TEXT NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_text_label_key_lang (`key`, lang_key),
          KEY idx_text_label_lang_key (lang_key),
          KEY idx_text_label_key (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function admin_ensure_bank_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bank (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          name VARCHAR(50) NOT NULL,
          avatar LONGTEXT NOT NULL,
          banner LONGTEXT NOT NULL,
          qr LONGTEXT NOT NULL,
          account_name VARCHAR(20) NOT NULL,
          account_number VARCHAR(50) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_bank_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM bank')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'name' => 'VARCHAR(50) NOT NULL',
        'avatar' => 'LONGTEXT NOT NULL',
        'banner' => 'LONGTEXT NOT NULL',
        'qr' => 'LONGTEXT NOT NULL',
        'account_name' => 'VARCHAR(20) NOT NULL',
        'account_number' => 'VARCHAR(50) NOT NULL',
    ];

    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE bank ADD `' . $column . '` ' . $sql);
        }
    }
}

function admin_ensure_sites_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sites (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          name VARCHAR(120) NOT NULL,
          url VARCHAR(255) NOT NULL,
          description LONGTEXT DEFAULT NULL,
          logo LONGTEXT DEFAULT NULL,
          sort_order INT NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_sites_url (url),
          KEY idx_sites_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM sites')->fetchAll(PDO::FETCH_COLUMN);
    $columnSql = [
        'name' => 'VARCHAR(120) NOT NULL',
        'url' => 'VARCHAR(255) NOT NULL',
        'description' => 'LONGTEXT DEFAULT NULL',
        'logo' => 'LONGTEXT DEFAULT NULL',
        'sort_order' => 'INT NOT NULL DEFAULT 0',
    ];

    foreach ($columnSql as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE sites ADD `' . $column . '` ' . $sql);
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM sites')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    if (!in_array('uq_sites_url', $indexNames, true)) {
        $pdo->exec('ALTER TABLE sites ADD UNIQUE KEY uq_sites_url (url)');
    }
    if (!in_array('idx_sites_sort_order', $indexNames, true)) {
        $pdo->exec('ALTER TABLE sites ADD KEY idx_sites_sort_order (sort_order)');
    }
}

function admin_ensure_visit_daily_ip_table(PDO $pdo, string $defaultSite = 'web'): void
{
    $defaultSite = preg_replace('/[^a-z0-9_-]/i', '', $defaultSite) ?: 'web';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visit_daily_ip (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          site VARCHAR(32) NOT NULL DEFAULT '{$defaultSite}',
          visit_date DATE NOT NULL,
          ip_address VARBINARY(16) NOT NULL,
          ip_text VARCHAR(45) NOT NULL,
          first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          hits INT UNSIGNED NOT NULL DEFAULT 1,
          user_agent VARCHAR(512) DEFAULT NULL,
          referer VARCHAR(1024) DEFAULT NULL,
          request_path VARCHAR(1024) DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_visit_daily_ip (site, visit_date, ip_address),
          KEY idx_visit_site_date (site, visit_date),
          KEY idx_visit_date (visit_date),
          KEY idx_visit_last_seen_at (last_seen_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM visit_daily_ip')->fetchAll(PDO::FETCH_ASSOC);
    $hasSite = false;
    foreach ($columns as $column) {
        if (($column['Field'] ?? '') === 'site') {
            $hasSite = true;
            break;
        }
    }

    if (!$hasSite) {
        $pdo->exec("ALTER TABLE visit_daily_ip ADD site VARCHAR(32) NOT NULL DEFAULT '{$defaultSite}' AFTER id");
    }

    $indexes = $pdo->query('SHOW INDEX FROM visit_daily_ip')->fetchAll(PDO::FETCH_ASSOC);
    $uniqueColumns = [];
    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'uq_visit_daily_ip') {
            $uniqueColumns[(int) ($index['Seq_in_index'] ?? 0)] = $index['Column_name'] ?? '';
        }
    }
    ksort($uniqueColumns);

    if (array_values($uniqueColumns) !== ['site', 'visit_date', 'ip_address']) {
        if ($uniqueColumns) {
            $pdo->exec('ALTER TABLE visit_daily_ip DROP INDEX uq_visit_daily_ip');
        }
        $pdo->exec('ALTER TABLE visit_daily_ip ADD UNIQUE KEY uq_visit_daily_ip (site, visit_date, ip_address)');
    }

    $hasSiteDateIndex = false;
    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'idx_visit_site_date') {
            $hasSiteDateIndex = true;
            break;
        }
    }
    if (!$hasSiteDateIndex) {
        $pdo->exec('ALTER TABLE visit_daily_ip ADD KEY idx_visit_site_date (site, visit_date)');
    }
}

function admin_country_seed_rows(): array
{
    return [
        ['Vietnam', 'vi', 'VN'],
        ['United States', 'en', 'US'],
        ['United Kingdom', 'en', 'GB'],
        ['Australia', 'en', 'AU'],
        ['Canada', 'en', 'CA'],
        ['India', 'hi', 'IN'],
        ['Japan', 'ja', 'JP'],
        ['South Korea', 'ko', 'KR'],
        ['China', 'zh', 'CN'],
        ['Taiwan', 'zh', 'TW'],
        ['Hong Kong', 'zh', 'HK'],
        ['Singapore', 'en', 'SG'],
        ['Malaysia', 'ms', 'MY'],
        ['Thailand', 'th', 'TH'],
        ['Indonesia', 'id', 'ID'],
        ['Philippines', 'en', 'PH'],
        ['Cambodia', 'km', 'KH'],
        ['Laos', 'lo', 'LA'],
        ['Myanmar', 'my', 'MM'],
        ['Brunei', 'ms', 'BN'],
        ['France', 'fr', 'FR'],
        ['Germany', 'de', 'DE'],
        ['Italy', 'it', 'IT'],
        ['Spain', 'es', 'ES'],
        ['Portugal', 'pt', 'PT'],
        ['Netherlands', 'nl', 'NL'],
        ['Belgium', 'nl', 'BE'],
        ['Switzerland', 'de', 'CH'],
        ['Austria', 'de', 'AT'],
        ['Sweden', 'sv', 'SE'],
        ['Norway', 'no', 'NO'],
        ['Denmark', 'da', 'DK'],
        ['Finland', 'fi', 'FI'],
        ['Ireland', 'en', 'IE'],
        ['Poland', 'pl', 'PL'],
        ['Czech Republic', 'cs', 'CZ'],
        ['Hungary', 'hu', 'HU'],
        ['Romania', 'ro', 'RO'],
        ['Greece', 'el', 'GR'],
        ['Turkey', 'tr', 'TR'],
        ['Ukraine', 'uk', 'UA'],
        ['Russia', 'ru', 'RU'],
        ['Brazil', 'pt', 'BR'],
        ['Mexico', 'es', 'MX'],
        ['Argentina', 'es', 'AR'],
        ['Chile', 'es', 'CL'],
        ['Colombia', 'es', 'CO'],
        ['Peru', 'es', 'PE'],
        ['South Africa', 'en', 'ZA'],
        ['Egypt', 'ar', 'EG'],
        ['Saudi Arabia', 'ar', 'SA'],
        ['United Arab Emirates', 'ar', 'AE'],
        ['Israel', 'he', 'IL'],
        ['Pakistan', 'ur', 'PK'],
        ['Bangladesh', 'bn', 'BD'],
        ['Sri Lanka', 'si', 'LK'],
        ['New Zealand', 'en', 'NZ'],
    ];
}

function admin_seed_country_table(PDO $pdo): int
{
    $stmt = $pdo->prepare('
        INSERT INTO country (icon, name, lang_key, lang_country)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          icon = VALUES(icon),
          name = VALUES(name),
          updated_at = CURRENT_TIMESTAMP
    ');

    $count = 0;
    foreach (admin_country_seed_rows() as [$name, $langKey, $langCountry]) {
        $icon = 'https://flagcdn.com/w80/' . strtolower($langCountry) . '.png';
        $stmt->execute([$icon, $name, $langKey, $langCountry]);
        $count++;
    }

    return $count;
}
