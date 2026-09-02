<?php

namespace App\Models;

use CodeIgniter\Model;

class ContentTypeModel extends Model
{
    protected $table         = 'content_types';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'slug', 'icon', 'has_categories', 'has_seo', 'supports_revisions'];

    protected $validationRules = [
        'name' => 'required|max_length[150]',
    ];

    public function findBySlug(string $slug)
    {
        return $this->where('slug', $slug)->first();
    }
}
