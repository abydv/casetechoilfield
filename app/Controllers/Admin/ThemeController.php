<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AuditLog;
use App\Services\ThemeSettingsService;

class ThemeController extends BaseController
{
    public function index()
    {
        $service = new ThemeSettingsService();

        return view('admin/theme/index', [
            'theme'       => $service->all(),
            'fontChoices' => ThemeSettingsService::FONT_CHOICES,
        ]);
    }

    public function save()
    {
        $service = new ThemeSettingsService();
        $errors = $service->save($this->request->getPost() ?? []);

        if (! empty($errors)) {
            return redirect()->to('/admin/theme')->withInput()->with('errors', $errors);
        }

        AuditLog::record((int) (session('user_id') ?? 0) ?: null, 'settings.theme.update', 'theme_settings');

        return redirect()->to('/admin/theme')->with('success', 'Theme saved.');
    }
}
