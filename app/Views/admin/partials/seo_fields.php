<?php
/**
 * Reusable SEO tab, included by every content type's edit form.
 * Expects an optional $seo array (seo_meta row) in scope.
 * See docs/cms-specification.md §11.
 */
$seo = $seo ?? [];
?>
<fieldset class="form-fieldset">
    <legend>SEO</legend>

    <label for="seo_title">SEO title</label>
    <input type="text" id="seo_title" name="seo_title" maxlength="255"
           value="<?= esc(old('seo_title', $seo['seo_title'] ?? '')) ?>"
           placeholder="Defaults to the content title if left blank">

    <label for="meta_description">Meta description</label>
    <textarea id="meta_description" name="meta_description" maxlength="320" rows="2"><?= esc(old('meta_description', $seo['meta_description'] ?? '')) ?></textarea>

    <div class="form-row">
        <div>
            <label for="canonical_url">Canonical URL</label>
            <input type="text" id="canonical_url" name="canonical_url"
                   value="<?= esc(old('canonical_url', $seo['canonical_url'] ?? '')) ?>">
        </div>
        <div>
            <label for="robots">Robots</label>
            <select id="robots" name="robots">
                <?php $robots = old('robots', $seo['robots'] ?? 'index,follow'); ?>
                <?php foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $opt): ?>
                    <option value="<?= esc($opt) ?>" <?= $robots === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label for="focus_keyword">Focus keyword</label>
    <input type="text" id="focus_keyword" name="focus_keyword" value="<?= esc(old('focus_keyword', $seo['focus_keyword'] ?? '')) ?>">

    <div class="form-row">
        <div>
            <label for="og_title">OG title</label>
            <input type="text" id="og_title" name="og_title" value="<?= esc(old('og_title', $seo['og_title'] ?? '')) ?>">
        </div>
        <div>
            <label for="og_image">OG image</label>
            <input type="file" id="og_image" name="og_image" accept="image/*">
        </div>
    </div>
    <label for="og_description">OG description</label>
    <textarea id="og_description" name="og_description" rows="2"><?= esc(old('og_description', $seo['og_description'] ?? '')) ?></textarea>

    <div class="form-row">
        <div>
            <label for="twitter_title">Twitter/X title</label>
            <input type="text" id="twitter_title" name="twitter_title" value="<?= esc(old('twitter_title', $seo['twitter_title'] ?? '')) ?>">
        </div>
        <div>
            <label for="twitter_image">Twitter/X image</label>
            <input type="file" id="twitter_image" name="twitter_image" accept="image/*">
        </div>
    </div>
    <label for="twitter_description">Twitter/X description</label>
    <textarea id="twitter_description" name="twitter_description" rows="2"><?= esc(old('twitter_description', $seo['twitter_description'] ?? '')) ?></textarea>
</fieldset>
