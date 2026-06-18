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
          UNIQUE KEY uq_country_lang_key (lang_key),
          KEY idx_country_name (name),
          KEY idx_country_lang_country (lang_country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
