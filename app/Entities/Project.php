<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Project extends Entity
{
    protected $attributes = [
        'id'           => null,
        'title'        => null,
        'slug'         => null,
        'client'       => null,
        'location'     => null,
        'industry_id'  => null,
        'project_date' => null,
        'description'  => null,
        'challenge'    => null,
        'solution'     => null,
        'results'      => null,
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

    public function isPublished(): bool
    {
        return $this->attributes['status'] === 'published';
    }
}
