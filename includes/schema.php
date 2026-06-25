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
          enabled TINYINT(1) NOT NULL DEFAULT 0,
          api_key TEXT DEFAULT NULL,
          model VARCHAR(120) NOT NULL DEFAULT 'gemini-2.5-flash',
          endpoint VARCHAR(255) NOT NULL DEFAULT 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
          temperature DECIMAL(4,2) NOT NULL DEFAULT 0.20,
          system_prompt TEXT DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_ai_support_provider (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
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
