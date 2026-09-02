<?php

namespace App\Models;

use CodeIgniter\Model;

class SeoMetaModel extends Model
{
    protected $table         = 'seo_meta';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'seo_title', 'meta_description', 'canonical_url', 'robots',
        'focus_keyword', 'og_title', 'og_description', 'og_image_media_id',
        'twitter_title', 'twitter_description', 'twitter_image_media_id',
        'schema_json',
    ];

    /**
     * Creates or updates the seo_meta row for a content record and
     * returns its id — the pattern every content model's save() calls
     * so every module shares one SEO tab implementation (docs/cms-
     * specification.md §11).
     */
    public function saveFromRequest(?int $existingSeoMetaId, array $post): ?int
    {
        $data = [
            'seo_title'            => $post['seo_title'] ?? null,
            'meta_description'     => $post['meta_description'] ?? null,
            'canonical_url'        => $post['canonical_url'] ?? null,
            'robots'               => $post['robots'] ?? 'index,follow',
            'focus_keyword'        => $post['focus_keyword'] ?? null,
            'og_title'             => $post['og_title'] ?? null,
            'og_description'       => $post['og_description'] ?? null,
            'twitter_title'        => $post['twitter_title'] ?? null,
            'twitter_description'  => $post['twitter_description'] ?? null,
        ];

        $isEmpty = count(array_filter($data, static fn ($v) => $v !== null && $v !== '')) === 0
            && empty($post['og_image_media_id']) && empty($post['twitter_image_media_id']);

        if ($isEmpty && ! $existingSeoMetaId) {
            return null;
        }

        if (! empty($post['og_image_media_id'])) {
            $data['og_image_media_id'] = (int) $post['og_image_media_id'];
        }
        if (! empty($post['twitter_image_media_id'])) {
            $data['twitter_image_media_id'] = (int) $post['twitter_image_media_id'];
        }

        if ($existingSeoMetaId) {
            $this->update($existingSeoMetaId, $data);

            return $existingSeoMetaId;
        }

        return $this->insert($data, true);
    }
}
