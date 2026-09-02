<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\FormFieldModel;
use App\Models\FormModel;
use App\Models\FormSubmissionModel;
use App\Services\CaptchaVerifier;
use App\Services\SettingsService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services as CIServices;

/**
 * Renders and accepts submissions for any admin-built form
 * (docs/cms-specification.md §9) at /forms/{slug}.
 */
class FormController extends BaseController
{
    public function show(string $slug)
    {
        $form = (new FormModel())->findBySlug($slug);
        if (! $form) {
            throw PageNotFoundException::forPageNotFound();
        }

        $fields = (new FormFieldModel())->forForm((int) $form['id']);
        foreach ($fields as &$field) {
            $field['options'] = json_decode($field['options'] ?? '[]', true) ?: [];
        }

        return view('site/forms/show', [
            'form'          => $form,
            'fields'        => $fields,
            'turnstileSite' => setting('captcha.turnstile_site_key', ''),
            'turnstileOn'   => $form['captcha_provider'] === 'turnstile' && setting('captcha.turnstile_enabled', false),
            'recaptchaSite' => setting('captcha.recaptcha_site_key', ''),
            'recaptchaOn'   => $form['captcha_provider'] === 'recaptcha' && setting('captcha.recaptcha_enabled', false),
        ]);
    }

    public function submit(string $slug)
    {
        $form = (new FormModel())->findBySlug($slug);
        if (! $form) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Honeypot, consistent with Site\EnquiryController.
        if ((string) $this->request->getPost('website') !== '') {
            return redirect()->to('/forms/' . $slug)->with('form_success', $form['success_message']);
        }

        $captchaToken = (string) ($this->request->getPost('cf-turnstile-response') ?: $this->request->getPost('g-recaptcha-response'));
        if (! (new CaptchaVerifier())->verify($form['captcha_provider'], $captchaToken, $this->request->getIPAddress())) {
            return redirect()->back()->withInput()->with('form_error', 'CAPTCHA verification failed. Please try again.');
        }

        $fields = (new FormFieldModel())->forForm((int) $form['id']);
        $data = [];
        foreach ($fields as $field) {
            if ($field['is_required'] && trim((string) $this->request->getPost($field['field_key'])) === '') {
                return redirect()->back()->withInput()->with('form_error', "\"{$field['label']}\" is required.");
            }
            $data[$field['label']] = $this->request->getPost($field['field_key']);
        }

        $submissionId = null;
        if ($form['store_in_db']) {
            $submissionId = (new FormSubmissionModel())->insert([
                'form_id'    => $form['id'],
                'data'       => json_encode($data),
                'source_url' => (string) $this->request->getPost('source_url') ?: (string) $this->request->getServer('HTTP_REFERER'),
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => (string) $this->request->getUserAgent(),
                'status'     => 'new',
            ], true);
        }

        $this->notifyRecipients($form, $data);
        if ($form['auto_response_enabled']) {
            $this->sendAutoResponse($form, $data);
        }

        if (! empty($form['redirect_url'])) {
            return redirect()->to($form['redirect_url']);
        }

        return redirect()->to('/forms/' . $slug)->with('form_success', $form['success_message']);
    }

    private function notifyRecipients(array $form, array $data): void
    {
        $recipients = json_decode($form['recipient_emails'] ?? '[]', true) ?: [];
        if (empty($recipients)) {
            return;
        }

        $this->configuredMailer()
            ->setTo($recipients)
            ->setSubject('New submission: ' . $form['name'])
            ->setMessage($this->renderDataAsHtml($data))
            ->send();
    }

    private function sendAutoResponse(array $form, array $data): void
    {
        $emailField = null;
        foreach ($data as $label => $value) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $emailField = $value;
                break;
            }
        }
        if (! $emailField) {
            return;
        }

        $body = $form['auto_response_body'] ?: 'Thank you for your submission.';
        foreach ($data as $label => $value) {
            $body = str_replace('{' . $label . '}', (string) $value, $body);
        }

        $this->configuredMailer()
            ->setTo($emailField)
            ->setSubject($form['auto_response_subject'] ?: 'Thank you for contacting us')
            ->setMessage(nl2br(esc($body)))
            ->send();
    }

    private function configuredMailer()
    {
        $settings = new SettingsService();
        $email = CIServices::email();
        $email->setFrom(
            $settings->get('smtp.from_email', 'no-reply@example.com'),
            $settings->get('smtp.from_name', setting('general.company_name', 'CaseTech CMS'))
        );

        $config = config('Email');
        $config->SMTPHost = $settings->get('smtp.host', '');
        $config->SMTPPort = (int) $settings->get('smtp.port', 587);
        $config->SMTPUser = $settings->get('smtp.username', '');
        $config->SMTPPass = $settings->getSecretPlain('smtp.password_secret') ?? '';
        $config->SMTPCrypto = $settings->get('smtp.encryption', 'tls');
        $config->protocol = $config->SMTPHost ? 'smtp' : 'mail';
        $email->initialize((array) $config);

        return $email;
    }

    private function renderDataAsHtml(array $data): string
    {
        $html = '<table>';
        foreach ($data as $label => $value) {
            $html .= '<tr><th style="text-align:left;padding-right:1em;">' . esc($label) . '</th><td>' . nl2br(esc((string) $value)) . '</td></tr>';
        }

        return $html . '</table>';
    }
}
