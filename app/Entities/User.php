<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        'id'              => null,
        'name'            => null,
        'email'           => null,
        'password_hash'   => null,
        'status'          => 'active',
        'totp_secret'     => null,
        'totp_enabled'    => 0,
        'avatar_media_id' => null,
        'last_login_at'   => null,
        'last_login_ip'   => null,
        'created_at'      => null,
        'updated_at'      => null,
    ];

    protected $casts = [
        'totp_enabled' => 'boolean',
    ];

    /** @var string[] roles slugs, populated by UserModel::withRoles() — not a DB column */
    public array $roleSlugs = [];

    /** @var string[] permission names, populated by UserModel::withRoles() — not a DB column */
    public array $permissionNames = [];

    public function hasRole(string $slug): bool
    {
        return in_array($slug, $this->roleSlugs, true);
    }

    public function can(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return in_array($permission, $this->permissionNames, true);
    }

    public function setPassword(string $plain): static
    {
        $this->attributes['password_hash'] = password_hash($plain, PASSWORD_DEFAULT);

        return $this;
    }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->attributes['password_hash'] ?? '');
    }
}
