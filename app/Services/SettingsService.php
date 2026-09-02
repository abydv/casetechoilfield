<?php

namespace App\Services;

use Config\Database;
use Config\Services as CIServices;

/**
 * Reads/writes site_settings (docs/database-schema.md §16). Plain values
 * are stored as JSON; is_secret=1 rows (SMTP password, CAPTCHA secret
 * keys) are encrypted at rest via CI4's Encryption service and are never
 * echoed back into an admin form — see docs/cms-specification.md §17.
 */
class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = Database::connect()->table('site_settings')->where('key', $key)->get()->getRowArray();
        if (! $row) {
            return $default;
        }
        if ((int) $row['is_secret'] === 1) {
            return $row['value'] !== null && $row['value'] !== '';
        }

        $decoded = json_decode($row['value'], true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row['value'];
    }

    public function getSecretPlain(string $key): ?string
    {
        $row = Database::connect()->table('site_settings')->where('key', $key)->get()->getRowArray();
        if (! $row || $row['value'] === null || $row['value'] === '') {
            return null;
        }

        try {
            $encrypter = CIServices::encrypter();

            return $encrypter->decrypt(base64_decode($row['value']));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function set(string $key, mixed $value, string $group): void
    {
        $this->upsert($key, json_encode($value), $group, false);
    }

    /**
     * Only overwrites the stored secret when $plain is non-empty — an
     * admin leaving a password field blank on save keeps the existing
     * credential rather than erasing it.
     */
    public function setSecretIfProvided(string $key, ?string $plain, string $group): void
    {
        if ($plain === null || $plain === '') {
            return;
        }

        $encrypter = CIServices::encrypter();
        $encrypted = base64_encode($encrypter->encrypt($plain));
        $this->upsert($key, $encrypted, $group, true);
    }

    private function upsert(string $key, string $value, string $group, bool $isSecret): void
    {
        $db = Database::connect();
        $exists = $db->table('site_settings')->where('key', $key)->countAllResults() > 0;

        $data = [
            'value'      => $value,
            'group'      => $group,
            'is_secret'  => $isSecret ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            $db->table('site_settings')->where('key', $key)->update($data);

            return;
        }

        $data['key'] = $key;
        $db->table('site_settings')->insert($data);
    }
}
