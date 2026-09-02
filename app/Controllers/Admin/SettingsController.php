<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AuditLog;
use App\Services\SettingsService;
use App\Traits\ContentCrudHelpers;

/**
 * Settings → General / SMTP / CAPTCHA (docs/cms-specification.md §17).
 * Every public template reads these back through the setting() helper
 * (app/Helpers/settings_helper.php) — this is the literal mechanism
 * behind "change the phone number once, it updates everywhere" (spec §70).
 */
class SettingsController extends BaseController
{
    use ContentCrudHelpers;

    private const GENERAL_FIELDS = [
        'company_name', 'phone', 'email', 'address', 'whatsapp', 'tagline',
        'business_hours', 'copyright', 'analytics_id', 'search_console_verification',
    ];

    public function general()
    {
        $settings = new SettingsService();
        $values = [];
        foreach (self::GENERAL_FIELDS as $field) {
            $values[$field] = $settings->get("general.{$field}", '');
        }

        return view('admin/settings/general', ['values' => $values]);
    }

    public function saveGeneral()
    {
        $settings = new SettingsService();
        $before = [];
        foreach (self::GENERAL_FIELDS as $field) {
            $before[$field] = $settings->get("general.{$field}");
            $settings->set("general.{$field}", (string) $this->request->getPost($field), 'general');
        }

        $this->logAction('settings.general.update', 'site_settings', 0, $before, $this->request->getPost());

        return redirect()->to('/admin/settings')->with('success', 'General settings saved.');
    }

    public function smtp()
    {
        $settings = new SettingsService();
        $values = [
            'host'       => $settings->get('smtp.host', ''),
            'port'       => $settings->get('smtp.port', 587),
            'encryption' => $settings->get('smtp.encryption', 'tls'),
            'username'   => $settings->get('smtp.username', ''),
            'from_name'  => $settings->get('smtp.from_name', ''),
            'from_email' => $settings->get('smtp.from_email', ''),
            'reply_to'   => $settings->get('smtp.reply_to', ''),
        ];
        $hasPassword = $settings->get('smtp.password_secret', false);

        return view('admin/settings/smtp', ['values' => $values, 'hasPassword' => $hasPassword]);
    }

    public function saveSmtp()
    {
        $settings = new SettingsService();
        $settings->set('smtp.host', (string) $this->request->getPost('host'), 'smtp');
        $settings->set('smtp.port', (int) $this->request->getPost('port'), 'smtp');
        $settings->set('smtp.encryption', (string) $this->request->getPost('encryption'), 'smtp');
        $settings->set('smtp.username', (string) $this->request->getPost('username'), 'smtp');
        $settings->set('smtp.from_name', (string) $this->request->getPost('from_name'), 'smtp');
        $settings->set('smtp.from_email', (string) $this->request->getPost('from_email'), 'smtp');
        $settings->set('smtp.reply_to', (string) $this->request->getPost('reply_to'), 'smtp');
        $settings->setSecretIfProvided('smtp.password_secret', $this->request->getPost('password'), 'smtp');

        AuditLog::record($this->currentUserId(), 'settings.smtp.update', 'site_settings');

        return redirect()->to('/admin/settings/smtp')->with('success', 'SMTP settings saved.');
    }

    public function sendTestEmail()
    {
        $settings = new SettingsService();
        $to = $this->request->getPost('test_email');

        if (! $to) {
            return redirect()->to('/admin/settings/smtp')->with('error', 'Enter an email address to send the test to.');
        }

        $email = \Config\Services::email();
        $email->setFrom(
            $settings->get('smtp.from_email', 'no-reply@example.com'),
            $settings->get('smtp.from_name', 'CaseTech CMS')
        );
        $email->setTo($to);
        $email->setSubject('CaseTech CMS — Test Email');
        $email->setMessage('This is a test email from your CaseTech Oilfield CMS SMTP configuration. If you received this, SMTP is working.');

        $config = config('Email');
        $config->SMTPHost = $settings->get('smtp.host', '');
        $config->SMTPPort = (int) $settings->get('smtp.port', 587);
        $config->SMTPUser = $settings->get('smtp.username', '');
        $config->SMTPPass = $settings->getSecretPlain('smtp.password_secret') ?? '';
        $config->SMTPCrypto = $settings->get('smtp.encryption', 'tls');
        $config->protocol = 'smtp';
        $email->initialize((array) $config);

        $sent = $email->send();

        if ($sent) {
            return redirect()->to('/admin/settings/smtp')->with('success', "Test email sent to {$to}.");
        }

        return redirect()->to('/admin/settings/smtp')->with('error', 'Failed to send: ' . strip_tags($email->printDebugger(['headers'])));
    }

    public function captcha()
    {
        $settings = new SettingsService();
        $values = [
            'turnstile_enabled'   => $settings->get('captcha.turnstile_enabled', false),
            'turnstile_site_key'  => $settings->get('captcha.turnstile_site_key', ''),
            'recaptcha_enabled'   => $settings->get('captcha.recaptcha_enabled', false),
            'recaptcha_site_key'  => $settings->get('captcha.recaptcha_site_key', ''),
        ];
        $hasTurnstileSecret = $settings->get('captcha.turnstile_secret', false);
        $hasRecaptchaSecret = $settings->get('captcha.recaptcha_secret', false);

        return view('admin/settings/captcha', compact('values', 'hasTurnstileSecret', 'hasRecaptchaSecret'));
    }

    public function saveCaptcha()
    {
        $settings = new SettingsService();
        $settings->set('captcha.turnstile_enabled', (bool) $this->request->getPost('turnstile_enabled'), 'captcha');
        $settings->set('captcha.turnstile_site_key', (string) $this->request->getPost('turnstile_site_key'), 'captcha');
        $settings->setSecretIfProvided('captcha.turnstile_secret', $this->request->getPost('turnstile_secret'), 'captcha');

        $settings->set('captcha.recaptcha_enabled', (bool) $this->request->getPost('recaptcha_enabled'), 'captcha');
        $settings->set('captcha.recaptcha_site_key', (string) $this->request->getPost('recaptcha_site_key'), 'captcha');
        $settings->setSecretIfProvided('captcha.recaptcha_secret', $this->request->getPost('recaptcha_secret'), 'captcha');

        AuditLog::record($this->currentUserId(), 'settings.captcha.update', 'site_settings');

        return redirect()->to('/admin/settings/captcha')->with('success', 'CAPTCHA settings saved.');
    }
}
