<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * User management (docs/cms-specification.md §15). Role → permission
 * matrix editing is out of scope for this first pass — the six roles
 * seeded by RolesAndPermissionsSeeder cover the spec's default grants;
 * this screen is for assigning users to those existing roles.
 */
class UserController extends BaseController
{
    use ContentCrudHelpers;

    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function index()
    {
        $db = Database::connect();
        $rows = $db->table('users')->orderBy('name')->get()->getResultArray();

        foreach ($rows as &$row) {
            $roles = $db->table('roles r')
                ->select('r.name')
                ->join('user_roles ur', 'ur.role_id = r.id')
                ->where('ur.user_id', $row['id'])
                ->get()->getResultArray();
            $row['role_names'] = implode(', ', array_column($roles, 'name'));
        }

        return view('admin/users/index', ['users' => $rows]);
    }

    public function create()
    {
        return view('admin/users/form', [
            'user'         => null,
            'roles'        => Database::connect()->table('roles')->orderBy('name')->get()->getResultArray(),
            'selectedRoles' => [],
        ]);
    }

    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|max_length[191]|is_unique[users.email]',
            'password' => 'required|min_length[8]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = $this->users->insert([
            'name'          => $this->request->getPost('name'),
            'email'         => $this->request->getPost('email'),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'status'        => $this->request->getPost('status') ?: 'active',
        ], true);

        $this->syncRoles((int) $id);
        $this->logAction('users.create', 'users', (int) $id, null, ['name' => $this->request->getPost('name'), 'email' => $this->request->getPost('email')]);

        return redirect()->to('/admin/users')->with('success', 'User created.');
    }

    public function edit($id)
    {
        $user = $this->users->find((int) $id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $db = Database::connect();
        $selectedRoles = array_column(
            $db->table('user_roles')->select('role_id')->where('user_id', $id)->get()->getResultArray(),
            'role_id'
        );

        return view('admin/users/form', [
            'user'          => $user,
            'roles'         => $db->table('roles')->orderBy('name')->get()->getResultArray(),
            'selectedRoles' => $selectedRoles,
        ]);
    }

    public function update($id)
    {
        $user = $this->users->find((int) $id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $rules = [
            'name'  => 'required|min_length[2]|max_length[150]',
            'email' => "required|valid_email|max_length[191]|is_unique[users.email,id,{$id}]",
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $before = $user->toArray();
        $data = [
            'name'   => $this->request->getPost('name'),
            'email'  => $this->request->getPost('email'),
            'status' => $this->request->getPost('status') ?: 'active',
        ];

        $newPassword = (string) $this->request->getPost('password');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
            }
            $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $this->users->update((int) $id, $data);
        $this->syncRoles((int) $id);
        $this->logAction('users.update', 'users', (int) $id, $before, $data);

        return redirect()->to('/admin/users/' . $id . '/edit')->with('success', 'User saved.');
    }

    public function delete($id)
    {
        if ((int) $id === $this->currentUserId()) {
            return redirect()->to('/admin/users')->with('error', 'You cannot delete your own account.');
        }

        $db = Database::connect();
        $isSuperAdmin = $db->table('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $id)->where('r.slug', 'super-admin')
            ->countAllResults() > 0;

        if ($isSuperAdmin) {
            $superAdminCount = $db->table('user_roles ur')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('r.slug', 'super-admin')
                ->countAllResults();
            if ($superAdminCount <= 1) {
                return redirect()->to('/admin/users')->with('error', 'Cannot delete the last Super Admin.');
            }
        }

        $user = $this->users->find((int) $id);
        $db->table('user_roles')->where('user_id', $id)->delete();
        $this->users->delete((int) $id);
        $this->logAction('users.delete', 'users', (int) $id, $user ? $user->toArray() : null, null);

        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }

    private function syncRoles(int $userId): void
    {
        $db = Database::connect();
        $roleIds = array_filter((array) ($this->request->getPost('roles') ?? []));

        $db->table('user_roles')->where('user_id', $userId)->delete();
        foreach ($roleIds as $roleId) {
            $db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => (int) $roleId]);
        }
    }
}
