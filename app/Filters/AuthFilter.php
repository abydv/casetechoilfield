<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks access to admin routes unless a user is authenticated.
 * Registered as 'auth' in app/Config/Filters.php.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('isLoggedIn') || ! $session->get('user_id')) {
            $session->setFlashdata('error', 'Please log in to continue.');

            return redirect()->to('/admin/login')->withCookies();
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
