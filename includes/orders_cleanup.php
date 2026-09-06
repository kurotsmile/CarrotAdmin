<?php

function admin_orders_cleanup_sql_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function admin_delete_created_orders(PDO $pdo): array
{
    $targets = [
        'app_orders' => 'App',
        'song_orders' => 'Âm nhạc',
        'ebook_orders' => 'Sách',
        'cloud_subscription' => 'Cloud',
        'coc_orders' => 'COC',
    ];
    $deleted = [];
    $total = 0;

    foreach ($targets as $table => $label) {
        try {
            $stmt = $pdo->prepare('DELETE FROM ' . admin_orders_cleanup_sql_ident($table) . ' WHERE status = ?');
            $stmt->execute(['CREATED']);
            $count = (int) $stmt->rowCount();
            $deleted[$label] = $count;
            $total += $count;
        } catch (Throwable $e) {
            $deleted[$label] = 0;
        }
    }

    return ['total' => $total, 'items' => $deleted];
}
