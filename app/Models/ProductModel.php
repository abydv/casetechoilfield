<?php

namespace App\Models;

use App\Entities\Product;
use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = Product::class;
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name', 'slug', 'product_code', 'category_id', 'short_description',
        'full_description', 'main_image_media_id', 'features', 'benefits',
        'applications', 'video_url', 'status', 'published_at', 'sort_order',
        'seo_meta_id', 'created_by', 'updated_by',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[200]',
        'slug' => 'required|max_length[200]',
    ];

    public function findBySlug(string $slug): ?Product
    {
        return $this->where('slug', $slug)->first();
    }

    public function publishedQuery()
    {
        return $this->where('status', 'published');
    }

    public function forListing(?int $categoryId = null, ?string $search = null, int $perPage = 20)
    {
        $builder = $this->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC');

        if ($categoryId) {
            $builder = $builder->where('category_id', $categoryId);
        }
        if ($search) {
            $builder = $builder->groupStart()
                ->like('name', $search)
                ->orLike('product_code', $search)
                ->groupEnd();
        }

        return $builder->paginate($perPage);
    }
}
