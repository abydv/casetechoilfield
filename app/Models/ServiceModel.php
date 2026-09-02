<?php

namespace App\Models;

use App\Entities\Service;
use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table          = 'services';
    protected $primaryKey     = 'id';
    protected $returnType     = Service::class;
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'name', 'slug', 'category_id', 'description', 'features',
        'applications', 'process', 'status', 'published_at', 'sort_order',
        'seo_meta_id', 'created_by', 'updated_by',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[200]',
        'slug' => 'required|max_length[200]',
    ];

    public function findBySlug(string $slug): ?Service
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
            $builder = $builder->like('name', $search);
        }

        return $builder->paginate($perPage);
    }
}
