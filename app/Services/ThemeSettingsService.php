<?php

namespace App\Services;

use Config\Database;

/**
 * Settings → Theme (spec §33). A small whitelist of CSS custom
 * properties, each server-validated against a safe format/range before
 * storage — "do not allow arbitrary settings to break the frontend."
 * Compiles to a single inline <style> override block the public layout
 * injects after site.css, so unset keys simply fall back to the
 * stylesheet's own defaults.
 */
class ThemeSettingsService
{
    private const COLOR_KEYS = [
        'color_primary'      => '--color-primary',
        'color_primary_dark' => '--color-primary-dark',
        'color_accent'       => '--color-accent',
        'color_ink'          => '--color-ink',
        'color_bg'           => '--color-bg',
        'color_surface'      => '--color-surface',
        'color_text'         => '--color-text',
        'color_muted'        => '--color-muted',
    ];

    public const FONT_CHOICES = [
        'georgia_serif'  => "Georgia, 'Times New Roman', serif",
        'system_sans'    => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
        'system_serif'   => "'Iowan Old Style', 'Palatino Linotype', Palatino, serif",
    ];

    public function all(): array
    {
        $db = Database::connect();
        $rows = $db->table('theme_settings')->get()->getResultArray();
        $values = array_column($rows, 'value', 'key');

        return [
            'color_primary'      => $values['color_primary'] ?? '#0b5e59',
            'color_primary_dark' => $values['color_primary_dark'] ?? '#08423f',
            'color_accent'       => $values['color_accent'] ?? '#d99a2b',
            'color_ink'          => $values['color_ink'] ?? '#10201e',
            'color_bg'           => $values['color_bg'] ?? '#f7f8f7',
            'color_surface'      => $values['color_surface'] ?? '#ffffff',
            'color_text'         => $values['color_text'] ?? '#202b2a',
            'color_muted'        => $values['color_muted'] ?? '#5c6b69',
            'font_heading'       => $values['font_heading'] ?? 'georgia_serif',
            'font_body'          => $values['font_body'] ?? 'system_sans',
            'radius'             => $values['radius'] ?? '4',
            'container_width'    => $values['container_width'] ?? '1180',
        ];
    }

    /** @return string[] validation errors, empty if the whole set was saved */
    public function save(array $input): array
    {
        $errors = [];
        $toSave = [];

        foreach (array_keys(self::COLOR_KEYS) as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value !== '' && ! preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                $errors[] = "{$key} must be a hex color like #0b5e59.";
                continue;
            }
            if ($value !== '') {
                $toSave[$key] = $value;
            }
        }

        foreach (['font_heading', 'font_body'] as $key) {
            $value = (string) ($input[$key] ?? '');
            if ($value !== '' && ! array_key_exists($value, self::FONT_CHOICES)) {
                $errors[] = "Unknown {$key} choice.";
                continue;
            }
            if ($value !== '') {
                $toSave[$key] = $value;
            }
        }

        $radius = (int) ($input['radius'] ?? 4);
        if ($radius < 0 || $radius > 24) {
            $errors[] = 'Corner radius must be between 0 and 24px.';
        } else {
            $toSave['radius'] = (string) $radius;
        }

        $container = (int) ($input['container_width'] ?? 1180);
        if ($container < 960 || $container > 1600) {
            $errors[] = 'Container width must be between 960 and 1600px.';
        } else {
            $toSave['container_width'] = (string) $container;
        }

        if (! empty($errors)) {
            return $errors;
        }

        $db = Database::connect();
        foreach ($toSave as $key => $value) {
            $exists = $db->table('theme_settings')->where('key', $key)->countAllResults() > 0;
            if ($exists) {
                $db->table('theme_settings')->where('key', $key)->update(['value' => $value]);
            } else {
                $db->table('theme_settings')->insert(['key' => $key, 'value' => $value]);
            }
        }

        return [];
    }

    public function compileCss(): string
    {
        $theme = $this->all();
        $declarations = [];

        foreach (self::COLOR_KEYS as $key => $cssVar) {
            $declarations[] = "{$cssVar}:{$theme[$key]}";
        }
        $declarations[] = '--font-heading:' . (self::FONT_CHOICES[$theme['font_heading']] ?? self::FONT_CHOICES['georgia_serif']);
        $declarations[] = '--font-body:' . (self::FONT_CHOICES[$theme['font_body']] ?? self::FONT_CHOICES['system_sans']);
        $declarations[] = '--radius:' . $theme['radius'] . 'px';
        $declarations[] = '--container:' . $theme['container_width'] . 'px';

        return ':root{' . implode(';', $declarations) . '}';
    }
}
