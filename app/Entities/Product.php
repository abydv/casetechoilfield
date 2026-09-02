<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Product extends Entity
{
    protected $attributes = [
        'id'                  => null,
        'name'                => null,
        'slug'                => null,
        'product_code'        => null,
        'category_id'         => null,
        'short_description'   => null,
        'full_description'    => null,
        'main_image_media_id' => null,
        'features'            => null,
        'benefits'            => null,
        'applications'        => null,
        'video_url'           => null,
        'status'              => 'draft',
        'published_at'        => null,
        'sort_order'          => 0,
        'seo_meta_id'         => null,
        'created_by'          => null,
        'updated_by'          => null,
        'created_at'          => null,
        'updated_at'          => null,
        'deleted_at'          => null,
    ];

    protected $casts = [
        'features'     => 'json-array',
        'benefits'     => 'json-array',
        'applications' => 'json-array',
    ];

    public function isPublished(): bool
    {
        return $this->attributes['status'] === 'published';
    }
}
