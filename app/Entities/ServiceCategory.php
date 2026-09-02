<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ServiceCategory extends Entity
{
    protected $attributes = [
        'id'              => null,
        'parent_id'       => null,
        'name'            => null,
        'slug'            => null,
        'description'     => null,
        'image_media_id'  => null,
        'is_featured'     => 0,
        'sort_order'      => 0,
        'seo_meta_id'     => null,
        'created_at'      => null,
        'updated_at'      => null,
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];
}
