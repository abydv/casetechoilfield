<?php

use Config\Database;

/**
 * setting('general.phone') reads one site_settings row (docs/database-
 * schema.md §16). This is the single mechanism every template uses for
 * company info/SEO defaults/etc. so changing a value in Settings updates
 * every page that reads it (spec §70 "change the phone number once").
 *
 * Cached for the lifetime of the request — one query loads every row.
 */
if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];
            $rows = Database::connect()->table('site_settings')->get()->getResultArray();
            foreach ($rows as $row) {
                if ((int) $row['is_secret'] === 1) {
                    // Secrets are never readable through the generic helper —
                    // use App\Services\SettingsService::getSecretPlain() from
                    // trusted server-side code only (e.g. sending mail).
                    continue;
                }
                $decoded = json_decode($row['value'], true);
                $cache[$row['key']] = json_last_error() === JSON_ERROR_NONE ? $decoded : $row['value'];
            }
        }

        return $cache[$key] ?? $default;
    }
}
