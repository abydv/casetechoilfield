<?php

namespace App\Models;

use App\Entities\ServiceCategory;
use CodeIgniter\Model;

class ServiceCategoryModel extends Model
{
    protected $table         = 'service_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = ServiceCategory::class;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'parent_id', 'name', 'slug', 'description', 'image_media_id',
        'is_featured', 'sort_order', 'seo_meta_id',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[150]',
        'slug' => 'required|max_length[150]',
    ];

    public function orderedTree(): array
    {
        return $this->orderBy('parent_id', 'ASC')->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
    }

    public function findBySlug(string $slug): ?ServiceCategory
    {
        return $this->where('slug', $slug)->first();
    }
}
