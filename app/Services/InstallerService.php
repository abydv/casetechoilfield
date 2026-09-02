<?php

namespace App\Services;

use App\Database\Seeds\ContentPagesSeeder;
use App\Database\Seeds\ProductCatalogSeeder;
use App\Database\Seeds\RedirectsSeeder;
use App\Database\Seeds\RolesAndPermissionsSeeder;
use App\Database\Seeds\SiteSettingsSeeder;
use CodeIgniter\Encryption\Encryption;
use Config\Database as DbConfig;
use Config\Services;
use Throwable;

/**
 * Web-based first-run bootstrap. docs/deployment.md's normal path assumes
 * SSH access to run `php spark migrate`/`db:seed`; not every Hostinger
 * plan has that. This lets the whole thing — DB credentials, schema,
 * starter content, the first Super Admin account — be done from a
 * browser after a plain file upload.
 *
 * Deliberately session-free: the installer runs before .env necessarily
 * exists, and this project's shipped .env.example points
 * session.driver at the database-backed handler, whose table
 * (ci_sessions) doesn't exist until runSchemaSetup() has run. Using
 * session()->setFlashdata() anywhere in this flow would make step 1
 * depend on step 2 having already happened. Controllers pass state
 * across redirects via query flags instead.
 */
class InstallerService
{
    public const LOCK_FILE = 'installed.lock';

    /**
     * app.baseURL values that mean "never actually configured" — the
     * current .env.example/App.php default, plus the older localhost
     * placeholder this project shipped before the Hostinger URL was
     * known, in case an existing .env still has it.
     */
    private const UNCONFIGURED_BASE_URLS = [
        'https://bisque-aardvark-342764.hostingersite.com/',
        'http://localhost:8080/',
    ];

    /**
     * True once a Super Admin exists — checked two ways so the installer
     * can't be used to hijack a site that was already provisioned over
     * SSH (which never creates the lock file) and can't be re-run against
     * a site this same flow already finished (which does).
     */
    public function isInstalled(): bool
    {
        if (is_file(WRITEPATH . self::LOCK_FILE)) {
            return true;
        }

        try {
            $db = \Config\Database::connect(null, false);
            if (! $db->tableExists('users') || ! $db->tableExists('roles') || ! $db->tableExists('user_roles')) {
                return false;
            }

            $count = $db->table('users u')
                ->join('user_roles ur', 'ur.user_id = u.id')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('r.slug', 'super-admin')
                ->countAllResults();

            return $count > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /** True once migrations have created the tables the admin-account step needs. */
    public function isSchemaReady(): bool
    {
        try {
            $db = \Config\Database::connect(null, false);

            return $db->tableExists('users') && $db->tableExists('roles') && $db->tableExists('user_roles');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Pre-fills the DB form from an existing .env, if one's already
     * there (e.g. provisioned over SSH but never finished setup) —
     * password is never read back.
     *
     * @return array{hostname:string,port:string,database:string,username:string}
     */
    public function currentDatabaseConfig(): array
    {
        return [
            'hostname' => (string) env('database.default.hostname', ''),
            'port'     => (string) env('database.default.port', '3306'),
            'database' => (string) env('database.default.database', ''),
            'username' => (string) env('database.default.username', ''),
        ];
    }

    /**
     * Attempts a real connection with the given credentials, without
     * touching the app's own configured 'default' group (so a bad
     * submission can't leave anything half-changed). Returns a
     * user-facing error message, or null on success.
     *
     * @param array{hostname:string,port:string,database:string,username:string,password:string} $config
     */
    public function testDatabaseConnection(array $config): ?string
    {
        try {
            $db = \Config\Database::connect($this->toRawDbConfig($config), false);
            $db->initialize();
            $db->query('SELECT 1');
            $db->close();

            return null;
        } catch (Throwable $e) {
            return $this->friendlyDbError($e->getMessage());
        }
    }

    /**
     * Writes the tested credentials to .env (creating it from
     * .env.example if it doesn't exist yet), generating an encryption
     * key if one isn't already set, and pointing app.baseURL at the
     * domain this request actually arrived on if it's still at
     * .env.example's shipped placeholder (see UNCONFIGURED_BASE_URLS)
     * — the single most common thing a no-SSH deploy would otherwise
     * forget to update.
     *
     * @param array{hostname:string,port:string,database:string,username:string,password:string} $config
     * @param string|null $envPath Overridable only so tests can point this at a throwaway
     *                             file instead of the project's real .env; production
     *                             callers should never pass this.
     *
     * @throws \RuntimeException if .env can't be written
     */
    public function persistDatabaseConfig(array $config, string $baseUrl, ?string $envPath = null): void
    {
        $envPath ??= ROOTPATH . '.env';

        if (! is_file($envPath)) {
            $examplePath = ROOTPATH . '.env.example';
            if (! is_file($examplePath) || ! @copy($examplePath, $envPath)) {
                throw new \RuntimeException(
                    'Could not create .env — check that the application root is writable by the web server, '
                    . 'or copy .env.example to .env manually and re-run the installer.'
                );
            }
        }

        if (! is_writable($envPath)) {
            throw new \RuntimeException(
                '.env exists but is not writable by the web server. Fix its file permissions and try again.'
            );
        }

        $contents = (string) file_get_contents($envPath);
        $contents = $this->setEnvValue($contents, 'database.default.hostname', $config['hostname']);
        $contents = $this->setEnvValue($contents, 'database.default.port', $config['port']);
        $contents = $this->setEnvValue($contents, 'database.default.database', $config['database']);
        $contents = $this->setEnvValue($contents, 'database.default.username', $config['username']);
        $contents = $this->setEnvValue($contents, 'database.default.password', $config['password']);

        $currentBaseUrl = $this->getEnvValue($contents, 'app.baseURL');
        if ($currentBaseUrl === '' || in_array($currentBaseUrl, self::UNCONFIGURED_BASE_URLS, true)) {
            $contents = $this->setEnvValue($contents, 'app.baseURL', $baseUrl);
        }

        if ($this->getEnvValue($contents, 'encryption.key') === '') {
            $key = Encryption::createKey(32);
            $contents = $this->setEnvValue($contents, 'encryption.key', 'hex2bin:' . bin2hex($key));
        }

        if (file_put_contents($envPath, $contents) === false) {
            throw new \RuntimeException('Could not write .env — check file permissions and try again.');
        }
    }

    /**
     * Runs every migration, then the roles/permissions and starter-content
     * seeders (site settings, redirects, pages, the product catalog) —
     * everything the SSH path would normally do via `spark migrate --all`
     * plus `spark db:seed`, minus the Super Admin account (createSuperAdmin()
     * below handles that from the installer's own form instead).
     *
     * @throws Throwable on the first failure, wrapped with which step failed
     */
    public function runSchemaSetup(): void
    {
        try {
            $runner = Services::migrations();
            $runner->setNamespace(null);
            $runner->latest();
        } catch (Throwable $e) {
            throw new \RuntimeException('Running migrations failed: ' . $e->getMessage(), 0, $e);
        }

        try {
            $dbConfig = new DbConfig();
            (new RolesAndPermissionsSeeder($dbConfig))->seedRolesAndPermissions();
            (new SiteSettingsSeeder($dbConfig))->run();
            (new RedirectsSeeder($dbConfig))->run();
            (new ContentPagesSeeder($dbConfig))->run();
            (new ProductCatalogSeeder($dbConfig))->run();
        } catch (Throwable $e) {
            throw new \RuntimeException('Seeding starter content failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates the Super Admin account from the installer's own form.
     * Refuses if any user already exists — this step runs exactly once,
     * whether that's because this same wizard already finished or
     * because the site was actually provisioned over SSH already.
     *
     * @throws \RuntimeException if a user already exists or the
     *                           super-admin role hasn't been seeded yet
     */
    public function createSuperAdmin(string $name, string $email, string $password): void
    {
        $db = \Config\Database::connect(null, false);

        if ($db->table('users')->countAllResults() > 0) {
            throw new \RuntimeException('An account already exists — the installer only creates the first one.');
        }

        $role = $db->table('roles')->where('slug', 'super-admin')->get()->getRowArray();
        if (! $role) {
            throw new \RuntimeException('Roles have not been seeded yet — go back and run setup first.');
        }

        $db->table('users')->insert([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $userId = $db->insertID();

        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $role['id'],
        ]);
    }

    /** Locks the installer out permanently — call only after createSuperAdmin() succeeds. */
    public function markInstalled(): void
    {
        file_put_contents(WRITEPATH . self::LOCK_FILE, date(DATE_ATOM) . "\n");
    }

    /**
     * @param array{hostname:string,port:string,database:string,username:string,password:string} $config
     *
     * @return array<string,mixed>
     */
    private function toRawDbConfig(array $config): array
    {
        return [
            'DSN'          => '',
            'hostname'     => $config['hostname'],
            'username'     => $config['username'],
            'password'     => $config['password'],
            'database'     => $config['database'],
            'DBDriver'     => 'MySQLi',
            'DBPrefix'     => '',
            'pConnect'     => false,
            'DBDebug'      => true,
            'charset'      => 'utf8mb4',
            'DBCollat'     => 'utf8mb4_unicode_ci',
            'port'         => (int) $config['port'],
        ];
    }

    private function friendlyDbError(string $message): string
    {
        return match (true) {
            str_contains($message, 'Access denied') => 'Access denied — check the database username and password.',
            str_contains($message, 'Unknown database') => 'That database does not exist yet — create it first (most Hostinger plans require creating the database in hPanel before first use), or check the name for typos.',
            str_contains($message, 'getaddrinfo') || str_contains($message, 'Name or service not known') => 'Could not resolve that hostname — check it for typos.',
            str_contains($message, 'Connection refused') || str_contains($message, "Can't connect") => 'Could not reach that host/port — check them and make sure the database server accepts remote connections.',
            default => 'Could not connect to the database: ' . $message,
        };
    }

    /** Reads a key's current value out of .env content being built, not the live process env. */
    private function getEnvValue(string $contents, string $key): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '[ \t]*=[ \t]*(.*)$/m';
        if (preg_match($pattern, $contents, $matches) !== 1) {
            return '';
        }

        return trim($matches[1], " \t\"'");
    }

    private function setEnvValue(string $contents, string $key, string $value): string
    {
        $escapedKey = preg_quote($key, '/');
        $pattern = '/^' . $escapedKey . '[ \t]*=.*$/m';
        $needsQuotes = $value !== '' && (str_contains($value, ' ') || str_contains($value, '#'));
        $encoded = $needsQuotes ? "'" . str_replace("'", "\\'", $value) . "'" : $value;
        $line = $key . ' = ' . $encoded;

        if (preg_match($pattern, $contents) === 1) {
            // preg_replace() interprets `$`/`\` followed by digits in the
            // *replacement* string as backreferences — a password
            // containing e.g. "$1" would otherwise be silently mangled.
            // preg_replace_callback() treats the returned string literally.
            return (string) preg_replace_callback($pattern, static fn () => $line, $contents, 1);
        }

        return rtrim($contents) . "\n" . $line . "\n";
    }
}
