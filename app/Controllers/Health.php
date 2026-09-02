<?php

namespace App\Controllers;

use Config\Database;

/**
 * GET /healthz — unauthenticated deploy health check.
 * See docs/deployment.md §7. Polled by the deploy pipeline after the
 * release symlink flip; a non-200 response triggers an automatic
 * rollback of the symlink (not the database).
 */
class Health extends BaseController
{
    public function check()
    {
        $status = [
            'app'      => 'ok',
            'database' => 'unknown',
            'writable' => 'unknown',
        ];
        $healthy = true;

        try {
            Database::connect()->query('SELECT 1');
            $status['database'] = 'ok';
        } catch (\Throwable $e) {
            $status['database'] = 'error';
            $healthy = false;
        }

        if (is_writable(WRITEPATH)) {
            $status['writable'] = 'ok';
        } else {
            $status['writable'] = 'error';
            $healthy = false;
        }

        return $this->response
            ->setStatusCode($healthy ? 200 : 503)
            ->setJSON($status);
    }
}
