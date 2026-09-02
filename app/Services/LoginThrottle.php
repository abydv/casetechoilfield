<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

/**
 * Login attempt throttling backed by the `login_attempts` table
 * (docs/database-schema.md §1). Locks out an email+IP combination after
 * too many failures in a rolling window, per spec §36.
 */
class LoginThrottle
{
    private ConnectionInterface $db;
    private int $maxAttempts;
    private int $windowMinutes;

    public function __construct(?int $maxAttempts = null, ?int $windowMinutes = null)
    {
        $this->db            = Database::connect();
        $this->maxAttempts   = $maxAttempts ?? 5;
        $this->windowMinutes = $windowMinutes ?? 15;
    }

    public function isLocked(string $email, string $ip): bool
    {
        return $this->recentFailureCount($email) >= $this->maxAttempts
            || $this->recentFailureCount(null, $ip) >= ($this->maxAttempts * 3);
    }

    public function recordAttempt(string $email, string $ip, bool $success): void
    {
        $this->db->table('login_attempts')->insert([
            'email'      => $email,
            'ip_address' => $ip,
            'success'    => $success ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($success) {
            // A successful login clears prior failures for this email so a
            // legitimate user who mistyped a password a few times isn't
            // punished after they get in.
            $this->db->table('login_attempts')
                ->where('email', $email)
                ->where('success', 0)
                ->delete();
        }
    }

    private function recentFailureCount(?string $email, ?string $ip = null): int
    {
        $since = date('Y-m-d H:i:s', time() - ($this->windowMinutes * 60));

        $builder = $this->db->table('login_attempts')
            ->where('success', 0)
            ->where('created_at >=', $since);

        if ($email !== null) {
            $builder->where('email', $email);
        }
        if ($ip !== null) {
            $builder->where('ip_address', $ip);
        }

        return $builder->countAllResults();
    }
}
