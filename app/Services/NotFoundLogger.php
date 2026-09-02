<?php

namespace App\Services;

use Config\Database;

/**
 * Logs a real 404 into not_found_logs, upserting the hit count rather
 * than inserting one row per hit (docs/database-schema.md §14) — this
 * is what lets Redirects → "turn frequent 404s into redirects" work.
 */
class NotFoundLogger
{
    public static function record(string $path, ?string $referrer): void
    {
        $db = Database::connect();
        $normalized = \App\Models\RedirectModel::normalize($path);
        $now = date('Y-m-d H:i:s');

        $existing = $db->table('not_found_logs')->where('path', $normalized)->get()->getRowArray();

        if ($existing) {
            $db->table('not_found_logs')->where('id', $existing['id'])->set('hit_count', 'hit_count + 1', false)->update([
                'last_seen_at' => $now,
                'referrer'     => $referrer,
            ]);

            return;
        }

        $db->table('not_found_logs')->insert([
            'path'          => $normalized,
            'referrer'      => $referrer,
            'hit_count'     => 1,
            'first_seen_at' => $now,
            'last_seen_at'  => $now,
        ]);
    }
}
