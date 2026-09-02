<?php

/**
 * Renders the <title>/meta/OG/Twitter tags for a public page from an
 * optional seo_meta row plus fallback title/description — used by every
 * public content template so the SEO tab (cms-specification.md §11)
 * actually reaches the page. Falls back to the site-wide title template
 * from Settings → General when no per-page SEO title is set.
 */
if (! function_exists('render_seo_tags')) {
    function render_seo_tags(string $fallbackTitle, ?string $fallbackDescription = null, ?array $seo = null, ?string $canonical = null): string
    {
        $seo = $seo ?? [];

        $titleTemplate = setting('seo.title_template', '{title} | ' . setting('general.company_name', ''));
        $title = $seo['seo_title'] ?? str_replace('{title}', $fallbackTitle, $titleTemplate);
        $description = $seo['meta_description'] ?? $fallbackDescription ?? '';
        $robots = $seo['robots'] ?? 'index,follow';
        $canonicalUrl = $seo['canonical_url'] ?? $canonical ?? current_url();

        $ogTitle = $seo['og_title'] ?? $title;
        $ogDescription = $seo['og_description'] ?? $description;

        $html = '<title>' . esc($title) . "</title>\n";
        if ($description !== '') {
            $html .= '<meta name="description" content="' . esc($description) . "\">\n";
        }
        $html .= '<meta name="robots" content="' . esc($robots) . "\">\n";
        $html .= '<link rel="canonical" href="' . esc($canonicalUrl) . "\">\n";
        $html .= '<meta property="og:title" content="' . esc($ogTitle) . "\">\n";
        if ($ogDescription !== '') {
            $html .= '<meta property="og:description" content="' . esc($ogDescription) . "\">\n";
        }
        $html .= '<meta property="og:type" content="website">' . "\n";
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";

        return $html;
    }
}
