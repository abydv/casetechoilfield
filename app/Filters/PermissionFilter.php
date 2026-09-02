<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Route-level authorization: `filter: permission:products.edit` in Routes.php
 * denies the request with 403 unless the logged-in user's role grants that
 * permission. Runs after AuthFilter, so a missing session is already
 * handled by the time this checks permissions.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (empty($arguments)) {
            return null;
        }

        $userId = session('user_id');
        if (! $userId) {
            return redirect()->to('/admin/login');
        }

        $user = (new UserModel())->findWithRoles((int) $userId);
        if (! $user) {
            return redirect()->to('/admin/login');
        }

        foreach ($arguments as $permission) {
            if (! $user->can($permission)) {
                return service('response')
                    ->setStatusCode(403)
                    ->setBody(view('admin/errors/403', ['permission' => $permission]));
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
