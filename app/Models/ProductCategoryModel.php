<?php

namespace App\Models;

use App\Entities\ProductCategory;
use CodeIgniter\Model;

class ProductCategoryModel extends Model
{
    protected $table         = 'product_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = ProductCategory::class;
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
        return $this->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function findBySlug(string $slug): ?ProductCategory
    {
        return $this->where('slug', $slug)->first();
    }
}
