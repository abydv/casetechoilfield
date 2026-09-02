<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Service extends Entity
{
    protected $attributes = [
        'id'           => null,
        'name'         => null,
        'slug'         => null,
        'category_id'  => null,
        'description'  => null,
        'features'     => null,
        'applications' => null,
        'process'      => null,
        'status'       => 'draft',
        'published_at' => null,
        'sort_order'   => 0,
        'seo_meta_id'  => null,
        'created_by'   => null,
        'updated_by'   => null,
        'created_at'   => null,
        'updated_at'   => null,
        'deleted_at'   => null,
    ];

    protected $casts = [
        'features'     => 'json-array',
        'applications' => 'json-array',
        'process'      => 'json-array',
    ];

    public function isPublished(): bool
    {
        return $this->attributes['status'] === 'published';
    }
}
