<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Services;

/**
 * Google Drive OAuth tokens (docs/backup-architecture.md §3, §7).
 * access_token/refresh_token are encrypted at rest via CI4's Encryption
 * service — never stored or logged in plain text.
 */
class OauthConnectionModel extends Model
{
    protected $table         = 'oauth_connections';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'provider', 'account_email', 'access_token', 'refresh_token',
        'token_expires_at', 'connected_by', 'connected_at',
    ];

    public function findGoogleDrive(): ?array
    {
        return $this->where('provider', 'google_drive')->first();
    }

    public function saveTokens(string $accountEmail, string $accessToken, ?string $refreshToken, int $expiresIn, ?int $userId): void
    {
        $encrypter = Services::encrypter();
        $existing = $this->findGoogleDrive();

        $data = [
            'provider'         => 'google_drive',
            'account_email'    => $accountEmail,
            'access_token'     => base64_encode($encrypter->encrypt($accessToken)),
            'token_expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
        ];

        // Google only returns a refresh_token on the very first consent
        // (or when prompt=consent forces re-consent, which getAuthUrl()
        // always sets) — never overwrite a previously stored one with
        // null just because a later token refresh didn't repeat it.
        if ($refreshToken !== null) {
            $data['refresh_token'] = base64_encode($encrypter->encrypt($refreshToken));
        }

        if ($existing) {
            $this->update($existing['id'], $data);

            return;
        }

        $data['connected_by'] = $userId;
        $data['connected_at'] = date('Y-m-d H:i:s');
        $this->insert($data);
    }

    public function getDecryptedAccessToken(array $connection): ?string
    {
        return $this->decrypt($connection['access_token'] ?? null);
    }

    public function getDecryptedRefreshToken(array $connection): ?string
    {
        return $this->decrypt($connection['refresh_token'] ?? null);
    }

    private function decrypt(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Services::encrypter()->decrypt(base64_decode($value));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function disconnect(): void
    {
        $this->where('provider', 'google_drive')->delete();
    }
}
