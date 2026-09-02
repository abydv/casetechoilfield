<?php

namespace App\Models;

use App\Entities\Page;
use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table          = 'pages';
    protected $primaryKey     = 'id';
    protected $returnType     = Page::class;
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'title', 'slug', 'is_homepage', 'status', 'published_at',
        'scheduled_publish_at', 'scheduled_unpublish_at', 'template',
        'seo_meta_id', 'created_by', 'updated_by',
    ];

    protected $validationRules = [
        'title' => 'required|max_length[200]',
        'slug'  => 'required|max_length[200]',
    ];

    public function findBySlug(string $slug): ?Page
    {
        return $this->where('slug', $slug)->first();
    }

    public function findHomepage(): ?Page
    {
        return $this->where('is_homepage', 1)->first();
    }

    public function forListing(?string $search = null, int $perPage = 20)
    {
        $builder = $this->orderBy('title', 'ASC');
        if ($search) {
            $builder = $builder->like('title', $search);
        }

        return $builder->paginate($perPage);
    }
}
