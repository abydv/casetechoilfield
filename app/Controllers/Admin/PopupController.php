<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PopupModel;
use App\Traits\ContentCrudHelpers;

/**
 * Announcement bar / promo / newsletter / product popups (docs/cms-
 * specification.md §14). The announcement bar type is also read
 * directly by site/layouts/main.php; the other three render as a
 * dismissible modal via public/assets/site/popups.js.
 */
class PopupController extends BaseController
{
    use ContentCrudHelpers;

    private PopupModel $popups;

    private const TYPES = [
        'announcement_bar' => 'Announcement Bar',
        'promo_popup'      => 'Promotional Popup',
        'newsletter_popup' => 'Newsletter Popup',
        'product_popup'    => 'Product Popup',
    ];

    public function __construct()
    {
        $this->popups = new PopupModel();
    }

    public function index()
    {
        return view('admin/popups/index', ['popups' => $this->popups->orderBy('id', 'DESC')->findAll(), 'types' => self::TYPES]);
    }

    public function create()
    {
        return view('admin/popups/form', ['popup' => null, 'types' => self::TYPES]);
    }

    public function store()
    {
        if (! $this->validate(['type' => 'required|in_list[announcement_bar,promo_popup,newsletter_popup,product_popup]'])) {
            return redirect()->back()->withInput()->with('error', 'Choose a valid popup type.');
        }

        $id = $this->popups->insert($this->collect(), true);
        $this->logAction('popups.create', 'popups', (int) $id, null, $this->request->getPost());

        return redirect()->to('/admin/popups')->with('success', 'Popup created.');
    }

    public function edit($id)
    {
        $popup = $this->popups->find((int) $id);
        if (! $popup) {
            return redirect()->to('/admin/popups')->with('error', 'Popup not found.');
        }

        return view('admin/popups/form', ['popup' => $popup, 'types' => self::TYPES]);
    }

    public function update($id)
    {
        $popup = $this->popups->find((int) $id);
        if (! $popup) {
            return redirect()->to('/admin/popups')->with('error', 'Popup not found.');
        }

        $data = $this->collect();
        $this->popups->update((int) $id, $data);
        $this->logAction('popups.update', 'popups', (int) $id, $popup, $data);

        return redirect()->to('/admin/popups/' . $id . '/edit')->with('success', 'Popup saved.');
    }

    public function delete($id)
    {
        $this->popups->delete((int) $id);
        $this->logAction('popups.delete', 'popups', (int) $id, null, null);

        return redirect()->to('/admin/popups')->with('success', 'Popup deleted.');
    }

    private function collect(): array
    {
        return [
            'type'           => $this->request->getPost('type'),
            'title'          => $this->request->getPost('title'),
            'content'        => $this->request->getPost('content'),
            'delay_seconds'  => (int) ($this->request->getPost('delay_seconds') ?: 0),
            'start_date'     => $this->request->getPost('start_date') ?: null,
            'end_date'       => $this->request->getPost('end_date') ?: null,
            'frequency'      => $this->request->getPost('frequency') ?: 'once_per_session',
            'show_desktop'   => $this->request->getPost('show_desktop') ? 1 : 0,
            'show_mobile'    => $this->request->getPost('show_mobile') ? 1 : 0,
            'status'         => $this->request->getPost('status') ?: 'draft',
        ];
    }
}
