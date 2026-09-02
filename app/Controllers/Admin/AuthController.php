<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\AuditLog;
use App\Services\LoginThrottle;
use App\Services\Totp;

class AuthController extends BaseController
{
    public function showLogin()
    {
        if (session('isLoggedIn')) {
            return redirect()->to('/admin');
        }

        return view('admin/auth/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();

        $throttle = new LoginThrottle();
        if ($throttle->isLocked($email, $ip)) {
            return redirect()->back()->withInput()
                ->with('error', 'Too many failed login attempts. Please try again in a few minutes.');
        }

        $userModel = new UserModel();
        $user      = $userModel->findByEmailWithRoles($email);

        if (! $user || $user->status !== 'active' || ! $user->verifyPassword($password)) {
            $throttle->recordAttempt($email, $ip, false);

            return redirect()->back()->withInput()
                ->with('error', 'Invalid email or password.');
        }

        $throttle->recordAttempt($email, $ip, true);

        if ($user->totp_enabled) {
            session()->set('pending_2fa_user_id', $user->id);

            return redirect()->to('/admin/login/verify');
        }

        $this->completeLogin($user->id, $ip);

        return redirect()->to('/admin');
    }

    public function showVerify()
    {
        if (! session('pending_2fa_user_id')) {
            return redirect()->to('/admin/login');
        }

        return view('admin/auth/verify');
    }

    public function attemptVerify()
    {
        $pendingId = session('pending_2fa_user_id');
        if (! $pendingId) {
            return redirect()->to('/admin/login');
        }

        $code = (string) $this->request->getPost('code');
        $user = (new UserModel())->find((int) $pendingId);

        if (! $user || ! $user->totp_secret || ! (new Totp())->verify($user->totp_secret, $code)) {
            return redirect()->back()->with('error', 'Invalid authentication code.');
        }

        session()->remove('pending_2fa_user_id');
        $this->completeLogin($user->id, $this->request->getIPAddress());

        return redirect()->to('/admin');
    }

    public function logout()
    {
        $userId = session('user_id');
        session()->destroy();

        if ($userId) {
            AuditLog::record((int) $userId, 'auth.logout', 'auth');
        }

        return redirect()->to('/admin/login');
    }

    private function completeLogin(int $userId, string $ip): void
    {
        $session = session();
        $session->regenerate(true);

        $userModel = new UserModel();
        $user      = $userModel->findWithRoles($userId);

        $session->set([
            'isLoggedIn' => true,
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'user_email' => $user->email,
            'user_roles' => $user->roleSlugs,
        ]);

        $userModel->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);

        AuditLog::record($userId, 'auth.login', 'auth');
    }
}
