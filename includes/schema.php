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
          status ENUM('public','draft','trash') NOT NULL DEFAULT 'draft',
          priority INT NOT NULL DEFAULT 0,
          published_at DATETIME NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uq_page_slug_lang (slug, lang),
          KEY idx_page_lang_status (lang, status),
          KEY idx_page_status_priority (status, priority),
          FULLTEXT KEY ft_page_seo (title, seo_title, seo_description, seo_keywords)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
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
          category longtext DEFAULT NULL,
          created_at datetime DEFAULT current_timestamp(),
          PRIMARY KEY (id)
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

function admin_ensure_visit_daily_ip_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visit_daily_ip (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
          UNIQUE KEY uq_visit_daily_ip (visit_date, ip_address),
          KEY idx_visit_date (visit_date),
          KEY idx_visit_last_seen_at (last_seen_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
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
