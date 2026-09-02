<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = User::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'name', 'email', 'password_hash', 'status', 'totp_secret',
        'totp_enabled', 'avatar_media_id', 'last_login_at', 'last_login_ip',
    ];

    protected $validationRules = [
        'name'  => 'required|min_length[2]|max_length[150]',
        'email' => 'required|valid_email|max_length[191]|is_unique[users.email,id,{id}]',
    ];

    /**
     * Loads a user by email together with their role slugs and the
     * union of permission names granted by those roles — this is the
     * shape app/Entities/User expects for hasRole()/can() to work
     * without extra queries during the request.
     */
    public function findByEmailWithRoles(string $email): ?User
    {
        $user = $this->where('email', $email)->first();
        if (! $user) {
            return null;
        }

        return $this->attachRolesAndPermissions($user);
    }

    public function findWithRoles(int $id): ?User
    {
        $user = $this->find($id);
        if (! $user) {
            return null;
        }

        return $this->attachRolesAndPermissions($user);
    }

    private function attachRolesAndPermissions(User $user): User
    {
        $db = $this->db;

        $roles = $db->table('roles r')
            ->select('r.slug')
            ->join('user_roles ur', 'ur.role_id = r.id')
            ->where('ur.user_id', $user->id)
            ->get()
            ->getResultArray();
        $user->roleSlugs = array_column($roles, 'slug');

        $permissions = $db->table('permissions p')
            ->select('p.name')
            ->join('role_permissions rp', 'rp.permission_id = p.id')
            ->join('user_roles ur', 'ur.role_id = rp.role_id')
            ->where('ur.user_id', $user->id)
            ->distinct()
            ->get()
            ->getResultArray();
        $user->permissionNames = array_column($permissions, 'name');

        return $user;
    }
}
