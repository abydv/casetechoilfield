<?php

namespace App\Services;

use Config\Services as CIServices;

/**
 * Server-side verification for Cloudflare Turnstile / Google reCAPTCHA
 * (docs/cms-specification.md §17). Secret keys are read via
 * SettingsService::getSecretPlain() — never trusted from the client.
 */
class CaptchaVerifier
{
    private const TURNSTILE_ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const RECAPTCHA_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    public function verify(string $provider, ?string $token, string $remoteIp = ''): bool
    {
        if ($provider === 'none' || $provider === '') {
            return true;
        }
        if (empty($token)) {
            return false;
        }

        $settings = new SettingsService();

        $endpoint = $provider === 'turnstile' ? self::TURNSTILE_ENDPOINT : self::RECAPTCHA_ENDPOINT;
        $secret = $provider === 'turnstile'
            ? $settings->getSecretPlain('captcha.turnstile_secret')
            : $settings->getSecretPlain('captcha.recaptcha_secret');

        if (empty($secret)) {
            // Enabled but not configured — fail closed rather than
            // silently accepting every submission.
            return false;
        }

        try {
            $client = CIServices::curlrequest(['timeout' => 5]);
            $response = $client->post($endpoint, [
                'form_params' => [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ],
            ]);
            $body = json_decode($response->getBody(), true);

            return (bool) ($body['success'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
