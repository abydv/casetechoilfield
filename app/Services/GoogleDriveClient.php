<?php

namespace App\Services;

use Config\Services as CIServices;
use RuntimeException;

/**
 * Thin REST client for Google OAuth 2.0 + Drive API v3 (docs/backup-
 * architecture.md §3) — deliberately not the official google/apiclient
 * SDK, which is tens of MB and far more than a handful of endpoints
 * need, an unnecessary footprint on Hostinger shared hosting
 * (architecture.md §7's inode/size discipline applies to vendor/ too).
 *
 * Every method that talks to Google can throw \RuntimeException — the
 * caller (BackupService) is responsible for turning that into a
 * job-failure with a clear, credential-redacted error_message.
 */
class GoogleDriveClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const API_BASE  = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_BASE = 'https://www.googleapis.com/upload/drive/v3';

    public function isConfigured(): bool
    {
        return (bool) (env('GOOGLE_OAUTH_CLIENT_ID') && env('GOOGLE_OAUTH_CLIENT_SECRET'));
    }

    public function getAuthUrl(string $state): string
    {
        $params = [
            'client_id'              => env('GOOGLE_OAUTH_CLIENT_ID'),
            'redirect_uri'           => env('GOOGLE_OAUTH_REDIRECT_URI'),
            'response_type'          => 'code',
            'scope'                  => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email',
            'access_type'            => 'offline',
            'prompt'                 => 'consent',
            'state'                  => $state,
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /** @return array{access_token:string,refresh_token:?string,expires_in:int} */
    public function exchangeCode(string $code): array
    {
        return $this->tokenRequest([
            'code'          => $code,
            'client_id'     => env('GOOGLE_OAUTH_CLIENT_ID'),
            'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
            'redirect_uri'  => env('GOOGLE_OAUTH_REDIRECT_URI'),
            'grant_type'    => 'authorization_code',
        ]);
    }

    /** @return array{access_token:string,expires_in:int} */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->tokenRequest([
            'refresh_token' => $refreshToken,
            'client_id'     => env('GOOGLE_OAUTH_CLIENT_ID'),
            'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
            'grant_type'    => 'refresh_token',
        ]);
    }

    private function tokenRequest(array $fields): array
    {
        $client = CIServices::curlrequest(['timeout' => 30]);

        try {
            $response = $client->post(self::TOKEN_URL, ['form_params' => $fields]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Google token request failed: ' . $this->redact($e->getMessage()));
        }

        $body = json_decode((string) $response->getBody(), true);
        if ($response->getStatusCode() >= 300 || ! isset($body['access_token'])) {
            throw new RuntimeException('Google token request failed: ' . ($body['error_description'] ?? $body['error'] ?? 'unknown error'));
        }

        return $body;
    }

    public function getAccountEmail(string $accessToken): ?string
    {
        $client = CIServices::curlrequest(['timeout' => 15]);
        $response = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        return $body['email'] ?? null;
    }

    /**
     * List-then-create-if-missing a folder under $parentId (or Drive root
     * when null) — idempotent, so calling this on every backup run never
     * creates duplicate Year/Month folders (docs/backup-architecture.md §3).
     */
    public function ensureFolder(string $accessToken, ?string $parentId, string $name): string
    {
        $client = CIServices::curlrequest(['timeout' => 20]);
        $parentClause = $parentId ? "'{$parentId}' in parents and " : "'root' in parents and ";
        $query = $parentClause . "name = '" . addslashes($name) . "' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";

        $response = $client->get(self::API_BASE . '/files', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            'query'   => ['q' => $query, 'fields' => 'files(id,name)', 'spaces' => 'drive'],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        if (! empty($body['files'][0]['id'])) {
            return $body['files'][0]['id'];
        }

        $create = $client->post(self::API_BASE . '/files', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Content-Type' => 'application/json'],
            'json'    => [
                'name'     => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => $parentId ? [$parentId] : [],
            ],
        ]);
        $created = json_decode((string) $create->getBody(), true);

        if (empty($created['id'])) {
            throw new RuntimeException('Could not create Drive folder "' . $name . '".');
        }

        return $created['id'];
    }

    /**
     * Resumable upload (docs/backup-architecture.md §2 step 5): opens a
     * session, then PUTs the file content in one shot (files are backup
     * archives, not the huge assets resumable is really built for, so a
     * single PUT after opening the session is enough — a network blip
     * still just fails this one PUT rather than corrupting a running
     * multipart request, and the session URL could be retried without
     * restarting from a fresh upload — the plumbing this app builds on).
     *
     * @return array{id:string,size:string}
     */
    public function uploadFile(string $accessToken, string $folderId, string $localPath, string $filename, string $mimeType): array
    {
        if (! is_file($localPath)) {
            throw new RuntimeException('Local file not found for upload: ' . $localPath);
        }

        $client = CIServices::curlrequest(['timeout' => 30]);
        $session = $client->post(self::UPLOAD_BASE . '/files?uploadType=resumable', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Content-Type' => 'application/json'],
            'json'    => ['name' => $filename, 'parents' => [$folderId]],
        ]);

        $uploadUrl = $session->getHeaderLine('Location');
        if (! $uploadUrl) {
            throw new RuntimeException('Google Drive did not return a resumable upload session URL.');
        }

        $size = filesize($localPath);
        $put = CIServices::curlrequest(['timeout' => 0])->put($uploadUrl, [
            'headers' => ['Content-Type' => $mimeType, 'Content-Length' => (string) $size],
            'body'    => file_get_contents($localPath),
        ]);

        $body = json_decode((string) $put->getBody(), true);
        if ($put->getStatusCode() >= 300 || empty($body['id'])) {
            throw new RuntimeException('Google Drive upload failed (HTTP ' . $put->getStatusCode() . ').');
        }

        return ['id' => $body['id'], 'size' => (string) $size];
    }

    /** @return array{id:string,size:?string}|null */
    public function getFileMetadata(string $accessToken, string $fileId): ?array
    {
        $client = CIServices::curlrequest(['timeout' => 20]);
        $response = $client->get(self::API_BASE . '/files/' . $fileId, [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            'query'   => ['fields' => 'id,name,size'],
        ]);

        if ($response->getStatusCode() >= 300) {
            return null;
        }

        return json_decode((string) $response->getBody(), true);
    }

    /** @return list<array{id:string,name:string}> */
    public function listFilesInFolder(string $accessToken, string $folderId): array
    {
        $client = CIServices::curlrequest(['timeout' => 20]);
        $response = $client->get(self::API_BASE . '/files', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            'query'   => [
                'q'      => "'{$folderId}' in parents and trashed = false",
                'fields' => 'files(id,name)',
                'spaces' => 'drive',
                'pageSize' => 1000,
            ],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        return $body['files'] ?? [];
    }

    /**
     * Downloads a Drive file straight to a local path via raw curl with
     * CURLOPT_FILE — CI4's CURLRequest has no streaming-sink option, and
     * buffering a whole backup archive through PHP userland twice
     * (response body + write) is the kind of memory pressure Hostinger
     * shared hosting doesn't have room for. Used only by the explicit
     * "Download Backup" admin action (docs/backup-architecture.md §6.1),
     * never automatically.
     */
    public function downloadFileTo(string $accessToken, string $fileId, string $destinationPath): void
    {
        $fh = fopen($destinationPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('Could not open destination file for the Drive download.');
        }

        $ch = curl_init(self::API_BASE . '/files/' . $fileId . '?alt=media');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER    => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_FILE          => $fh,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT       => 0,
        ]);
        $ok = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if (! $ok || $status >= 300) {
            @unlink($destinationPath);

            throw new RuntimeException('Drive download failed (HTTP ' . $status . '): ' . $error);
        }
    }

    public function deleteFile(string $accessToken, string $fileId): void
    {
        $client = CIServices::curlrequest(['timeout' => 20]);
        $client->delete(self::API_BASE . '/files/' . $fileId, [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
    }

    /** Strips anything that looks like a token/secret before it ever reaches a log line (docs/backup-architecture.md §7). */
    private function redact(string $message): string
    {
        return (string) preg_replace('/(client_secret|refresh_token|access_token)=[^&\s]+/i', '$1=[redacted]', $message);
    }
}
