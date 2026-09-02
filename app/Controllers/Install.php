<?php

namespace App\Controllers;

use App\Services\InstallerService;
use Throwable;

/**
 * Web-based first-run bootstrap — see App\Services\InstallerService for
 * why this deliberately never touches session()/flashdata: a form
 * failure re-renders the same view inline instead of redirecting, so
 * every step here works before .env, the database, or its ci_sessions
 * table necessarily exist yet.
 */
class Install extends BaseController
{
    private InstallerService $installer;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->installer = new InstallerService();
    }

    public function index()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        if (! $this->installer->isSchemaReady()) {
            return redirect()->to('/install/database');
        }

        return redirect()->to('/install/admin');
    }

    public function database()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        return view('install/database', [
            'values' => $this->installer->currentDatabaseConfig(),
            'error'  => null,
        ]);
    }

    public function saveDatabase()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        $rules = [
            'hostname' => 'required|max_length[191]',
            'port'     => 'required|is_natural_no_zero|max_length[5]',
            'database' => 'required|max_length[64]',
            'username' => 'required|max_length[191]',
        ];

        $values = [
            'hostname' => trim((string) $this->request->getPost('hostname')),
            'port'     => trim((string) $this->request->getPost('port')),
            'database' => trim((string) $this->request->getPost('database')),
            'username' => trim((string) $this->request->getPost('username')),
        ];

        if (! $this->validateData($values, $rules)) {
            return view('install/database', [
                'values' => $values,
                'error'  => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $config = $values + ['password' => (string) $this->request->getPost('password')];

        $connectionError = $this->installer->testDatabaseConnection($config);
        if ($connectionError !== null) {
            return view('install/database', [
                'values' => $values,
                'error'  => $connectionError,
            ]);
        }

        try {
            $this->installer->persistDatabaseConfig($config, $this->baseUrlFromRequest());
        } catch (Throwable $e) {
            return view('install/database', [
                'values' => $values,
                'error'  => $e->getMessage(),
            ]);
        }

        return redirect()->to('/install/setup');
    }

    public function setup()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        if ($this->installer->testDatabaseConnection($this->currentTestableConfig()) !== null) {
            return redirect()->to('/install/database');
        }

        return view('install/setup', ['error' => null]);
    }

    public function runSetup()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        try {
            $this->installer->runSchemaSetup();
        } catch (Throwable $e) {
            return view('install/setup', ['error' => $e->getMessage()]);
        }

        return redirect()->to('/install/admin');
    }

    public function admin()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        if (! $this->installer->isSchemaReady()) {
            return redirect()->to('/install/setup');
        }

        return view('install/admin', ['values' => [], 'error' => null]);
    }

    public function saveAdmin()
    {
        if ($this->installer->isInstalled()) {
            return redirect()->to('/admin/login');
        }

        if (! $this->installer->isSchemaReady()) {
            return redirect()->to('/install/setup');
        }

        $rules = [
            'name'             => 'required|min_length[2]|max_length[150]',
            'email'            => 'required|valid_email|max_length[191]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $values = [
            'name'  => trim((string) $this->request->getPost('name')),
            'email' => trim((string) $this->request->getPost('email')),
        ];

        if (! $this->validate($rules)) {
            return view('install/admin', [
                'values' => $values,
                'error'  => implode(' ', $this->validator->getErrors()),
            ]);
        }

        try {
            $this->installer->createSuperAdmin($values['name'], $values['email'], (string) $this->request->getPost('password'));
        } catch (Throwable $e) {
            return view('install/admin', [
                'values' => $values,
                'error'  => $e->getMessage(),
            ]);
        }

        $this->installer->markInstalled();

        return redirect()->to('/install/finish');
    }

    public function finish()
    {
        if (! $this->installer->isInstalled()) {
            return redirect()->to('/install/database');
        }

        return view('install/finish');
    }

    private function baseUrlFromRequest(): string
    {
        $uri = $this->request->getUri();

        return $uri->getScheme() . '://' . $uri->getAuthority() . '/';
    }

    /** @return array{hostname:string,port:string,database:string,username:string,password:string} */
    private function currentTestableConfig(): array
    {
        $config = $this->installer->currentDatabaseConfig();

        return $config + ['password' => (string) env('database.default.password', '')];
    }
}
