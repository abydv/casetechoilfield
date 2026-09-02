<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Page extends Entity
{
    protected $attributes = [
        'id'                     => null,
        'title'                  => null,
        'slug'                   => null,
        'is_homepage'            => 0,
        'status'                 => 'draft',
        'published_at'           => null,
        'scheduled_publish_at'   => null,
        'scheduled_unpublish_at' => null,
        'template'               => 'default',
        'seo_meta_id'            => null,
        'created_by'             => null,
        'updated_by'             => null,
        'created_at'             => null,
        'updated_at'             => null,
        'deleted_at'             => null,
    ];

    protected $casts = [
        'is_homepage' => 'boolean',
    ];

    public function isPublished(): bool
    {
        return $this->attributes['status'] === 'published';
    }
}
