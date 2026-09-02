<?php

namespace App\Models;

use App\Entities\Project;
use CodeIgniter\Model;

class ProjectModel extends Model
{
    protected $table          = 'projects';
    protected $primaryKey     = 'id';
    protected $returnType     = Project::class;
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'title', 'slug', 'client', 'location', 'industry_id', 'project_date',
        'description', 'challenge', 'solution', 'results', 'status',
        'published_at', 'sort_order', 'seo_meta_id', 'created_by', 'updated_by',
    ];

    protected $validationRules = [
        'title' => 'required|max_length[200]',
        'slug'  => 'required|max_length[200]',
    ];

    public function findBySlug(string $slug): ?Project
    {
        return $this->where('slug', $slug)->first();
    }

    public function publishedQuery()
    {
        return $this->where('status', 'published');
    }

    public function forListing(?string $search = null, int $perPage = 20)
    {
        $builder = $this->orderBy('sort_order', 'ASC')->orderBy('project_date', 'DESC');
        if ($search) {
            $builder = $builder->groupStart()->like('title', $search)->orLike('client', $search)->groupEnd();
        }

        return $builder->paginate($perPage);
    }
}
