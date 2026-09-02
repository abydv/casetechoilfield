<?php

use App\Entities\User;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class AdminAuthGateTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    public function testUnauthenticatedRequestToAdminIsRedirectedToLogin(): void
    {
        $result = $this->get('admin');

        $result->assertRedirectTo('/admin/login');
    }

    public function testUnauthenticatedRequestToAContentAdminRouteIsRedirectedToLogin(): void
    {
        $result = $this->get('admin/content-types');

        $result->assertRedirectTo('/admin/login');
    }

    public function testLoginPageIsPubliclyReachable(): void
    {
        $this->get('admin/login')->assertOK();
    }

    public function testAuthenticatedSessionCanReachAnAdminRoute(): void
    {
        $user = new User();
        $user->name = 'Test Admin';
        $user->email = 'test-admin@example.com';
        $user->setPassword('irrelevant-for-this-test');
        $user->status = 'active';
        $id = Database::connect()->table('users')->insert([
            'name' => $user->name, 'email' => $user->email, 'password_hash' => $user->password_hash,
            'status' => $user->status, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ], true);

        $result = $this->withSession(['isLoggedIn' => true, 'user_id' => $id])->get('admin/content-types');

        $result->assertOK();
    }
}
