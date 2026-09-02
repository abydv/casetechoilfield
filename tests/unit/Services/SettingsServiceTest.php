<?php

use App\Services\SettingsService;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class SettingsServiceTest extends DatabaseTestCase
{
    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new SettingsService();
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $this->assertSame('fallback', $this->settings->get('nonexistent.key', 'fallback'));
    }

    public function testSetAndGetRoundTripAStringValue(): void
    {
        $this->settings->set('general.company_name', 'CaseTech Oilfield Services', 'general');

        $this->assertSame('CaseTech Oilfield Services', $this->settings->get('general.company_name'));
    }

    public function testSetAndGetRoundTripAnArrayValue(): void
    {
        $this->settings->set('general.social_links', ['facebook' => 'https://facebook.com/casetech'], 'general');

        $this->assertSame(['facebook' => 'https://facebook.com/casetech'], $this->settings->get('general.social_links'));
    }

    public function testSetOverwritesAnExistingValue(): void
    {
        $this->settings->set('general.tagline', 'Old tagline', 'general');
        $this->settings->set('general.tagline', 'New tagline', 'general');

        $this->assertSame('New tagline', $this->settings->get('general.tagline'));
    }

    public function testSecretValueIsEncryptedAtRestAndDecryptsCorrectly(): void
    {
        $this->settings->setSecretIfProvided('smtp.password', 'super-secret-password', 'smtp');

        $raw = \Config\Database::connect()->table('site_settings')->where('key', 'smtp.password')->get()->getRowArray();
        $this->assertNotSame('super-secret-password', $raw['value']);
        $this->assertSame(1, (int) $raw['is_secret']);

        $this->assertSame('super-secret-password', $this->settings->getSecretPlain('smtp.password'));
    }

    public function testGetOnASecretReturnsBooleanPresenceNotThePlainValue(): void
    {
        $this->settings->setSecretIfProvided('smtp.password', 'super-secret-password', 'smtp');

        $this->assertTrue($this->settings->get('smtp.password'));
    }

    public function testSetSecretIfProvidedIgnoresBlankValueAndKeepsExisting(): void
    {
        $this->settings->setSecretIfProvided('smtp.password', 'keep-me', 'smtp');
        $this->settings->setSecretIfProvided('smtp.password', '', 'smtp');

        $this->assertSame('keep-me', $this->settings->getSecretPlain('smtp.password'));
    }
}
