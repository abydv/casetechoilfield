<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class DashboardController extends BaseController
{
    public function index()
    {
        $db = Database::connect();

        $counts = [
            'pages'     => $this->safeCount($db, 'pages'),
            'products'  => $this->safeCount($db, 'products'),
            'services'  => $this->safeCount($db, 'services'),
            'projects'  => $this->safeCount($db, 'projects'),
            'enquiries' => $this->safeCount($db, 'enquiries'),
            'media'     => $this->safeCount($db, 'media'),
        ];

        $recentActivity = $db->table('audit_logs')
            ->select('audit_logs.*, users.name as user_name')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return view('admin/dashboard/index', [
            'counts'         => $counts,
            'recentActivity' => $recentActivity,
        ]);
    }

    private function safeCount(\CodeIgniter\Database\ConnectionInterface $db, string $table): int
    {
        try {
            return $db->table($table)->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
