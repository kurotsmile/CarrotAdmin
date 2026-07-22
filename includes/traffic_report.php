<?php

function admin_traffic_report_empty_hours(): array
{
    return array_fill(0, 24, 0);
}

function admin_traffic_report_sites(PDO $pdo, string $date): array
{
    $sites = [];
    foreach (['visit_daily_ip', 'visit_hourly_ip'] as $table) {
        try {
            $stmt = $pdo->prepare("SELECT DISTINCT site FROM {$table} WHERE visit_date = ?");
            $stmt->execute([$date]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $site) {
                $site = trim((string) $site);
                if ($site !== '') {
                    $sites[$site] = true;
                }
            }
        } catch (Throwable $e) {
        }
    }
    ksort($sites);
    return array_keys($sites);
}

function admin_traffic_report_aggregate_day(PDO $pdo, string $date, array $sites = [], bool $deleteRaw = true): array
{
    admin_ensure_visit_daily_ip_table($pdo, 'web');
    admin_ensure_visit_hourly_ip_table($pdo, 'web');
    admin_ensure_visit_traffic_report_table($pdo);

    $today = date('Y-m-d');
    if ($date >= $today) {
        return ['date' => $date, 'sites' => 0, 'deleted_daily' => 0, 'deleted_hourly' => 0, 'skipped' => true];
    }

    if (!$sites) {
        $sites = admin_traffic_report_sites($pdo, $date);
    }

    $saved = 0;
    foreach ($sites as $site) {
        $site = trim((string) $site);
        if ($site === '') {
            continue;
        }

        $dailyStmt = $pdo->prepare("
            SELECT
              COUNT(*) AS unique_count,
              COALESCE(SUM(hits), 0) AS hits,
              MIN(first_seen_at) AS first_seen_at,
              MAX(last_seen_at) AS last_seen_at
            FROM visit_daily_ip
            WHERE site = :site AND visit_date = :report_date
        ");
        $dailyStmt->execute([':site' => $site, ':report_date' => $date]);
        $daily = $dailyStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $hourlyHits = admin_traffic_report_empty_hours();
        $hourlyUnique = admin_traffic_report_empty_hours();
        $hourlyStmt = $pdo->prepare("
            SELECT visit_hour, COUNT(*) AS unique_count, COALESCE(SUM(hits), 0) AS hits
            FROM visit_hourly_ip
            WHERE site = :site AND visit_date = :report_date
            GROUP BY visit_hour
        ");
        $hourlyStmt->execute([':site' => $site, ':report_date' => $date]);
        foreach ($hourlyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hour = max(0, min(23, (int) ($row['visit_hour'] ?? 0)));
            $hourlyHits[$hour] = (int) ($row['hits'] ?? 0);
            $hourlyUnique[$hour] = (int) ($row['unique_count'] ?? 0);
        }

        $upsert = $pdo->prepare("
            INSERT INTO visit_traffic_report (
              site, report_date, unique_count, hits, hourly_hits_json, hourly_unique_json, first_seen_at, last_seen_at
            )
            VALUES (
              :site, :report_date, :unique_count, :hits, :hourly_hits_json, :hourly_unique_json, :first_seen_at, :last_seen_at
            )
            ON DUPLICATE KEY UPDATE
              unique_count = VALUES(unique_count),
              hits = VALUES(hits),
              hourly_hits_json = VALUES(hourly_hits_json),
              hourly_unique_json = VALUES(hourly_unique_json),
              first_seen_at = VALUES(first_seen_at),
              last_seen_at = VALUES(last_seen_at),
              updated_at = CURRENT_TIMESTAMP
        ");
        $upsert->execute([
            ':site' => $site,
            ':report_date' => $date,
            ':unique_count' => (int) ($daily['unique_count'] ?? 0),
            ':hits' => (int) ($daily['hits'] ?? 0),
            ':hourly_hits_json' => json_encode($hourlyHits, JSON_UNESCAPED_SLASHES),
            ':hourly_unique_json' => json_encode($hourlyUnique, JSON_UNESCAPED_SLASHES),
            ':first_seen_at' => $daily['first_seen_at'] ?: null,
            ':last_seen_at' => $daily['last_seen_at'] ?: null,
        ]);
        $saved++;
    }

    $deletedDaily = 0;
    $deletedHourly = 0;
    if ($deleteRaw) {
        $deleteHourly = $pdo->prepare('DELETE FROM visit_hourly_ip WHERE visit_date = ?');
        $deleteHourly->execute([$date]);
        $deletedHourly = (int) $deleteHourly->rowCount();

        $deleteDaily = $pdo->prepare('DELETE FROM visit_daily_ip WHERE visit_date = ?');
        $deleteDaily->execute([$date]);
        $deletedDaily = (int) $deleteDaily->rowCount();
    }

    return ['date' => $date, 'sites' => $saved, 'deleted_daily' => $deletedDaily, 'deleted_hourly' => $deletedHourly, 'skipped' => false];
}

function admin_traffic_report_run(PDO $pdo, ?string $throughDate = null, bool $deleteRaw = true): array
{
    admin_ensure_visit_traffic_report_table($pdo);
    $today = date('Y-m-d');
    $throughDate = $throughDate ?: date('Y-m-d', strtotime('-1 day'));
    if ($throughDate >= $today) {
        $throughDate = date('Y-m-d', strtotime('-1 day'));
    }

    $dates = [];
    foreach (['visit_daily_ip', 'visit_hourly_ip'] as $table) {
        try {
            $stmt = $pdo->prepare("SELECT DISTINCT visit_date FROM {$table} WHERE visit_date <= ? ORDER BY visit_date");
            $stmt->execute([$throughDate]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $date) {
                $date = (string) $date;
                if ($date !== '') {
                    $dates[$date] = true;
                }
            }
        } catch (Throwable $e) {
        }
    }

    ksort($dates);
    $items = [];
    foreach (array_keys($dates) as $date) {
        $items[] = admin_traffic_report_aggregate_day($pdo, $date, [], $deleteRaw);
    }

    return ['through_date' => $throughDate, 'items' => $items];
}
