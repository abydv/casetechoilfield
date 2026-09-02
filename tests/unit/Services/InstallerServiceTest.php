<?php

use App\Services\InstallerService;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class InstallerServiceTest extends DatabaseTestCase
{
    private string $tmpEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpEnvPath = WRITEPATH . 'installer_test.env';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->tmpEnvPath);
        @unlink(WRITEPATH . InstallerService::LOCK_FILE);
    }

    public function testIsInstalledIsFalseOnAFreshSchemaWithNoUsers(): void
    {
        $this->assertFalse((new InstallerService())->isInstalled());
    }

    public function testIsInstalledIsTrueOnceTheLockFileExists(): void
    {
        file_put_contents(WRITEPATH . InstallerService::LOCK_FILE, date(DATE_ATOM));

        $this->assertTrue((new InstallerService())->isInstalled());
    }

    public function testIsSchemaReadyIsTrueOnceMigrationsHaveRun(): void
    {
        // DatabaseTestTrait already migrates the app's schema for this
        // test class, so this simply confirms the check itself works.
        $this->assertTrue((new InstallerService())->isSchemaReady());
    }

    /**
     * Real production connection attempts always hardcode DBDriver =
     * MySQLi (this app only ever targets MySQL — see docs/architecture.md
     * §7), so an unreachable host/port is a genuinely deterministic
     * failure to test against — no real MySQL server needed. The success
     * path can't be covered here for the same reason the Google Drive
     * integration's OAuth success path can't: no real external server is
     * reachable from this environment.
     */
    public function testTestDatabaseConnectionReturnsAFriendlyErrorForAnUnreachableHost(): void
    {
        $error = (new InstallerService())->testDatabaseConnection([
            'hostname' => '127.0.0.1',
            'port'     => '1', // nothing listens on port 1
            'database' => 'does_not_matter',
            'username' => 'nobody',
            'password' => '',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('reach that host', $error);
    }

    public function testPersistDatabaseConfigWritesDatabaseValuesAndGeneratesAnEncryptionKey(): void
    {
        copy(ROOTPATH . '.env.example', $this->tmpEnvPath);

        (new InstallerService())->persistDatabaseConfig([
            'hostname' => 'db.example.com',
            'port'     => '3306',
            'database' => 'casetech_cms',
            'username' => 'casetech_user',
            'password' => 'super secret',
        ], 'https://casetechoilfield.com/', $this->tmpEnvPath);

        $contents = file_get_contents($this->tmpEnvPath);
        $this->assertStringContainsString('database.default.hostname = db.example.com', $contents);
        $this->assertStringContainsString('database.default.database = casetech_cms', $contents);
        $this->assertStringContainsString('database.default.username = casetech_user', $contents);
        $this->assertStringContainsString("database.default.password = 'super secret'", $contents);
        $this->assertStringContainsString('app.baseURL = https://casetechoilfield.com/', $contents);
        $this->assertMatchesRegularExpression('/encryption\.key = hex2bin:[0-9a-f]{64}/', $contents);
    }

    /**
     * Regression test: preg_replace() (unlike preg_replace_callback())
     * interprets `$1`/`\1`-style sequences in its *replacement* string as
     * backreferences, so a password containing one would otherwise be
     * silently mangled on write — found manually while reviewing this
     * code before committing it.
     */
    public function testPersistDatabaseConfigDoesNotMangleAPasswordContainingDollarDigits(): void
    {
        copy(ROOTPATH . '.env.example', $this->tmpEnvPath);

        (new InstallerService())->persistDatabaseConfig([
            'hostname' => 'localhost',
            'port'     => '3306',
            'database' => 'casetech_cms',
            'username' => 'root',
            'password' => 'p$1assw0rd',
        ], 'https://example.com/', $this->tmpEnvPath);

        $this->assertStringContainsString('database.default.password = p$1assw0rd', file_get_contents($this->tmpEnvPath));
    }

    public function testPersistDatabaseConfigCreatesEnvFromExampleWhenMissing(): void
    {
        $this->assertFileDoesNotExist($this->tmpEnvPath);

        (new InstallerService())->persistDatabaseConfig([
            'hostname' => 'localhost',
            'port'     => '3306',
            'database' => 'casetech_cms',
            'username' => 'root',
            'password' => '',
        ], 'https://example.com/', $this->tmpEnvPath);

        $this->assertFileExists($this->tmpEnvPath);
        $this->assertStringContainsString('database.default.hostname = localhost', file_get_contents($this->tmpEnvPath));
    }

    public function testCreateSuperAdminAssignsTheSuperAdminRoleAndMarksInstalled(): void
    {
        $service = new InstallerService();
        (new App\Database\Seeds\RolesAndPermissionsSeeder(new Database()))->seedRolesAndPermissions();

        $this->assertFalse($service->isInstalled());

        $service->createSuperAdmin('Jane Admin', 'jane@example.com', 'a-strong-password');
        $service->markInstalled();

        $this->assertTrue($service->isInstalled());

        $db = \Config\Database::connect();
        $row = $db->table('users u')
            ->select('u.name, u.email, r.slug')
            ->join('user_roles ur', 'ur.user_id = u.id')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('u.email', 'jane@example.com')
            ->get()->getRowArray();

        $this->assertSame('Jane Admin', $row['name']);
        $this->assertSame('super-admin', $row['slug']);
    }

    public function testCreateSuperAdminRefusesWhenAUserAlreadyExists(): void
    {
        $service = new InstallerService();
        (new App\Database\Seeds\RolesAndPermissionsSeeder(new Database()))->seedRolesAndPermissions();
        $service->createSuperAdmin('First Admin', 'first@example.com', 'a-strong-password');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');
        $service->createSuperAdmin('Second Admin', 'second@example.com', 'another-password');
    }

    public function testCreateSuperAdminRefusesBeforeRolesAreSeeded(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Roles have not been seeded');
        (new InstallerService())->createSuperAdmin('Jane', 'jane@example.com', 'a-strong-password');
    }

    public function testRunSchemaSetupSeedsRolesAndStarterContentWithoutCreatingAUser(): void
    {
        (new InstallerService())->runSchemaSetup();

        $db = \Config\Database::connect();
        $this->assertGreaterThan(0, $db->table('roles')->countAllResults());
        $this->assertGreaterThan(0, $db->table('permissions')->countAllResults());
        $this->assertGreaterThan(0, $db->table('site_settings')->countAllResults());
        $this->assertGreaterThan(0, $db->table('products')->countAllResults());
        $this->assertSame(0, $db->table('users')->countAllResults());
    }
}
