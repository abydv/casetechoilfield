<?php

namespace App\Models;

use CodeIgniter\Model;

class ContentEntryModel extends Model
{
    protected $table          = 'content_entries';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'content_type_id', 'title', 'slug', 'status', 'published_at',
        'seo_meta_id', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $validationRules = [
        'title' => 'required|max_length[200]',
    ];

    public function findBySlug(int $contentTypeId, string $slug): ?array
    {
        return $this->where('content_type_id', $contentTypeId)->where('slug', $slug)->first();
    }

    public function publishedQuery(int $contentTypeId)
    {
        return $this->where('content_type_id', $contentTypeId)->where('status', 'published');
    }

    public function forListing(int $contentTypeId, ?string $search = null, int $perPage = 20)
    {
        $builder = $this->where('content_type_id', $contentTypeId)->orderBy('sort_order', 'ASC')->orderBy('title', 'ASC');
        if ($search) {
            $builder = $builder->like('title', $search);
        }

        return $builder->paginate($perPage);
    }
}
