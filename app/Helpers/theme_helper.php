<?php

use App\Services\ThemeSettingsService;

/**
 * theme_css() returns the compiled :root CSS custom-property override
 * block for Settings → Theme (spec §33) — injected once in the public
 * layout's <head>, after site.css, so unset values fall back to the
 * stylesheet's own defaults.
 */
if (! function_exists('theme_css')) {
    function theme_css(): string
    {
        static $css = null;

        return $css ??= (new ThemeSettingsService())->compileCss();
    }
}
