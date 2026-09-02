<?php

namespace App\Models;

use CodeIgniter\Model;

class RedirectModel extends Model
{
    protected $table         = 'redirects';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['from_path', 'to_path', 'status_code', 'hit_count', 'is_active'];

    protected $validationRules = [
        'from_path' => 'required|max_length[255]',
        'to_path'   => 'required|max_length[255]',
    ];

    /** Normalizes to a leading-slash, no-trailing-slash, no-query path. */
    public static function normalize(string $path): string
    {
        $path = '/' . ltrim(explode('?', $path)[0], '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function findActiveMatch(string $path): ?array
    {
        return $this->where('from_path', self::normalize($path))->where('is_active', 1)->first();
    }

    public function recordHit(int $id): void
    {
        $this->db->table('redirects')->where('id', $id)->set('hit_count', 'hit_count + 1', false)->update();
    }
}
