<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the six roles from docs/cms-specification.md §15 and a starter
 * permission set, then creates the first Super Admin user if none exists.
 *
 * Run with: php spark db:seed RolesAndPermissionsSeeder
 *
 * The Super Admin password is never hardcoded: if SEED_SUPERADMIN_EMAIL /
 * SEED_SUPERADMIN_PASSWORD are not set in .env, a random password is
 * generated and printed once to the console.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<string,string> module => list of action suffixes */
    private array $modulePermissions = [
        'pages'       => ['view', 'create', 'edit', 'delete', 'publish'],
        'products'    => ['view', 'create', 'edit', 'delete', 'publish'],
        'services'    => ['view', 'create', 'edit', 'delete', 'publish'],
        'projects'    => ['view', 'create', 'edit', 'delete', 'publish'],
        'media'       => ['view', 'upload', 'delete'],
        'menus'       => ['view', 'edit'],
        'forms'       => ['view', 'create', 'edit', 'delete'],
        'enquiries'   => ['view', 'edit', 'assign', 'export'],
        'seo'         => ['view', 'edit'],
        'redirects'   => ['view', 'edit'],
        'settings'    => ['view', 'edit'],
        'theme'       => ['view', 'edit'],
        'users'       => ['view', 'create', 'edit', 'delete'],
        'roles'       => ['view', 'edit'],
        'backups'     => ['view', 'run', 'restore'],
        'system'      => ['view'],
        'content_types' => ['view', 'create', 'edit', 'delete'],
    ];

    /** @var array<string,array<string,string[]>> role slug => module => actions (or ['*'] for all) */
    private array $roleGrants = [
        'super-admin' => ['*' => ['*']],
        'administrator' => [
            'pages' => ['*'], 'products' => ['*'], 'services' => ['*'], 'projects' => ['*'],
            'media' => ['*'], 'menus' => ['*'], 'forms' => ['*'], 'enquiries' => ['*'],
            'seo' => ['*'], 'redirects' => ['*'], 'settings' => ['*'], 'theme' => ['*'],
            'users' => ['view', 'create', 'edit'], 'backups' => ['view', 'run'],
            'system' => ['view'], 'content_types' => ['*'],
        ],
        'editor' => [
            'pages' => ['view', 'create', 'edit'], 'media' => ['*'],
            'forms' => ['view'], 'enquiries' => ['view'],
        ],
        'product-manager' => [
            'products' => ['*'], 'media' => ['view', 'upload'], 'enquiries' => ['view', 'edit', 'assign'],
        ],
        'seo-manager' => [
            'seo' => ['*'], 'redirects' => ['*'], 'pages' => ['view'], 'products' => ['view'],
        ],
        'sales-manager' => [
            'enquiries' => ['*'],
        ],
    ];

    public function run()
    {
        $roleIds = $this->seedRoles();
        $permissionIds = $this->seedPermissions();
        $this->seedRolePermissions($roleIds, $permissionIds);
        $this->seedSuperAdmin($roleIds['super-admin']);
    }

    private function seedRoles(): array
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => 1],
            ['name' => 'Administrator', 'slug' => 'administrator', 'is_system' => 1],
            ['name' => 'Editor', 'slug' => 'editor', 'is_system' => 0],
            ['name' => 'Product Manager', 'slug' => 'product-manager', 'is_system' => 0],
            ['name' => 'SEO Manager', 'slug' => 'seo-manager', 'is_system' => 0],
            ['name' => 'Sales Manager', 'slug' => 'sales-manager', 'is_system' => 0],
        ];

        $ids = [];
        foreach ($roles as $role) {
            $existing = $this->db->table('roles')->where('slug', $role['slug'])->get()->getRow();
            if ($existing) {
                $ids[$role['slug']] = $existing->id;
                continue;
            }
            $this->db->table('roles')->insert($role);
            $ids[$role['slug']] = $this->db->insertID();
        }

        return $ids;
    }

    private function seedPermissions(): array
    {
        $ids = [];
        foreach ($this->modulePermissions as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                $existing = $this->db->table('permissions')->where('name', $name)->get()->getRow();
                if ($existing) {
                    $ids[$name] = $existing->id;
                    continue;
                }
                $this->db->table('permissions')->insert([
                    'name'        => $name,
                    'module'      => $module,
                    'description' => ucfirst($action) . ' ' . str_replace('_', ' ', $module),
                ]);
                $ids[$name] = $this->db->insertID();
            }
        }

        return $ids;
    }

    private function seedRolePermissions(array $roleIds, array $permissionIds): void
    {
        foreach ($this->roleGrants as $roleSlug => $grants) {
            $roleId = $roleIds[$roleSlug];

            $allowedNames = [];
            if (isset($grants['*']) && $grants['*'] === ['*']) {
                $allowedNames = array_keys($permissionIds);
            } else {
                foreach ($grants as $module => $actions) {
                    if ($actions === ['*']) {
                        foreach ($this->modulePermissions[$module] ?? [] as $action) {
                            $allowedNames[] = "{$module}.{$action}";
                        }
                        continue;
                    }
                    foreach ($actions as $action) {
                        $allowedNames[] = "{$module}.{$action}";
                    }
                }
            }

            foreach ($allowedNames as $name) {
                if (! isset($permissionIds[$name])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionIds[$name])
                    ->countAllResults();
                if ($exists === 0) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permissionIds[$name],
                    ]);
                }
            }
        }
    }

    private function seedSuperAdmin(int $superAdminRoleId): void
    {
        if ($this->db->table('users')->countAllResults() > 0) {
            return;
        }

        $email    = env('SEED_SUPERADMIN_EMAIL', 'admin@casetechoilfield.com');
        $password = env('SEED_SUPERADMIN_PASSWORD');
        $generated = false;

        if (empty($password)) {
            $password  = bin2hex(random_bytes(9));
            $generated = true;
        }

        $userId = $this->db->table('users')->insert([
            'name'          => 'Super Admin',
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]) ? $this->db->insertID() : null;

        if ($userId) {
            $this->db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $superAdminRoleId,
            ]);
        }

        if ($generated) {
            CLI::write('');
            CLI::write('==========================================================', 'yellow');
            CLI::write(' Super Admin created:', 'yellow');
            CLI::write("   Email:    {$email}", 'yellow');
            CLI::write("   Password: {$password}", 'yellow');
            CLI::write(' Log in and change this password immediately.', 'yellow');
            CLI::write('==========================================================', 'yellow');
            CLI::write('');
        }
    }
}
