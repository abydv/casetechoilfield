<?php

namespace App\Services;

use Config\Database;

/**
 * Writes to the DB-backed audit_logs table — deliberately never to the
 * filesystem, per docs/architecture.md §7 (inode-safety) and spec §37.
 */
class AuditLog
{
    public static function record(
        ?int $userId,
        string $action,
        string $module,
        ?string $recordType = null,
        ?int $recordId = null,
        ?array $before = null,
        ?array $after = null
    ): void {
        $request = service('request');

        Database::connect()->table('audit_logs')->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'module'      => $module,
            'record_type' => $recordType,
            'record_id'   => $recordId,
            'before_data' => $before !== null ? json_encode($before) : null,
            'after_data'  => $after !== null ? json_encode($after) : null,
            'ip_address'  => $request->getIPAddress(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
