<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RedirectModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Redirect manager + 404 log (docs/cms-specification.md §11, spec §28).
 * Critical for preserving the old WordPress URLs' SEO value during
 * migration — see the 301 map in docs/current-site-audit.md §3.
 */
class RedirectController extends BaseController
{
    use ContentCrudHelpers;

    private RedirectModel $redirects;

    public function __construct()
    {
        $this->redirects = new RedirectModel();
    }

    public function index()
    {
        $notFoundLogs = Database::connect()->table('not_found_logs')
            ->orderBy('hit_count', 'DESC')
            ->limit(50)
            ->get()->getResultArray();

        return view('admin/redirects/index', [
            'redirects'    => $this->redirects->orderBy('hit_count', 'DESC')->findAll(),
            'notFoundLogs' => $notFoundLogs,
        ]);
    }

    public function store()
    {
        $from = RedirectModel::normalize((string) $this->request->getPost('from_path'));
        $to = (string) $this->request->getPost('to_path');

        if ($from === '' || $from === '/' || $to === '') {
            return redirect()->to('/admin/redirects')->with('error', 'Both a from-path and a to-path are required.');
        }

        $id = $this->redirects->insert([
            'from_path'   => $from,
            'to_path'     => $to,
            'status_code' => (int) ($this->request->getPost('status_code') ?: 301),
            'is_active'   => 1,
        ], true);

        $this->logAction('redirects.create', 'redirects', (int) $id, null, ['from_path' => $from, 'to_path' => $to]);

        return redirect()->to('/admin/redirects')->with('success', 'Redirect created.');
    }

    public function delete($id)
    {
        $this->redirects->delete((int) $id);

        return redirect()->to('/admin/redirects')->with('success', 'Redirect deleted.');
    }

    public function toggle($id)
    {
        $row = $this->redirects->find((int) $id);
        if ($row) {
            $this->redirects->update((int) $id, ['is_active' => $row['is_active'] ? 0 : 1]);
        }

        return redirect()->to('/admin/redirects')->with('success', 'Updated.');
    }

    /** Turns a logged 404 path directly into a redirect. */
    public function fromNotFound()
    {
        $from = RedirectModel::normalize((string) $this->request->getPost('path'));
        $to = (string) $this->request->getPost('to_path');

        if ($to === '') {
            return redirect()->to('/admin/redirects')->with('error', 'Enter a destination path.');
        }

        $this->redirects->insert([
            'from_path'   => $from,
            'to_path'     => $to,
            'status_code' => 301,
            'is_active'   => 1,
        ]);

        Database::connect()->table('not_found_logs')->where('path', $from)->delete();

        return redirect()->to('/admin/redirects')->with('success', 'Redirect created from 404 log.');
    }
}
