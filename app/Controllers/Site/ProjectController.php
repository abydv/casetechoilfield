<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\ProjectModel;
use Config\Database;

class ProjectController extends BaseController
{
    private ProjectModel $projects;

    public function __construct()
    {
        $this->projects = new ProjectModel();
    }

    public function index()
    {
        $search = $this->request->getGet('q');
        $projects = $this->projects->publishedQuery()
            ->orderBy('sort_order', 'ASC')->orderBy('project_date', 'DESC');

        if ($search) {
            $projects = $projects->groupStart()->like('title', $search)->orLike('client', $search)->groupEnd();
        }
        $projects = $projects->paginate(12);

        return view('site/projects/index', [
            'projects' => $this->attachThumbnails($projects),
            'pager'    => $this->projects->pager,
            'search'   => $search,
        ]);
    }

    public function show(string $slug)
    {
        $project = $this->projects->findBySlug($slug);
        if (! $project || ! $project->isPublished()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = Database::connect();

        $seo = $project->seo_meta_id
            ? $db->table('seo_meta')->where('id', $project->seo_meta_id)->get()->getRowArray()
            : null;

        $gallery = $db->table('project_images pi')
            ->select('m.filename')
            ->join('media m', 'm.id = pi.media_id')
            ->where('pi.project_id', $project->id)
            ->orderBy('pi.sort_order')
            ->get()->getResultArray();
        $galleryUrls = array_map(fn ($row) => base_url('uploads/' . $row['filename']), $gallery);

        $documents = $db->table('project_documents pd')
            ->select('pd.label, pd.doc_type, m.filename, m.original_filename')
            ->join('media m', 'm.id = pd.media_id')
            ->where('pd.project_id', $project->id)
            ->get()->getResultArray();

        $relatedProducts = $db->table('project_related_products prp')
            ->select('p.name, p.slug')
            ->join('products p', 'p.id = prp.product_id')
            ->where('prp.project_id', $project->id)
            ->where('p.status', 'published')
            ->get()->getResultArray();

        $relatedServices = $db->table('project_related_services prs')
            ->select('s.name, s.slug')
            ->join('services s', 's.id = prs.service_id')
            ->where('prs.project_id', $project->id)
            ->where('s.status', 'published')
            ->get()->getResultArray();

        $breadcrumbs = [
            ['label' => 'Projects', 'url' => site_url('projects')],
            ['label' => $project->title, 'url' => null],
        ];

        return view('site/projects/show', [
            'project'          => $project,
            'seo'              => $seo,
            'gallery'          => $galleryUrls,
            'documents'        => $documents,
            'relatedProducts'  => $relatedProducts,
            'relatedServices'  => $relatedServices,
            'breadcrumbs'      => $breadcrumbs,
        ]);
    }

    private function attachThumbnails(iterable $projects): array
    {
        $db = Database::connect();
        $out = [];
        foreach ($projects as $project) {
            $row = $db->table('project_images pi')
                ->select('m.filename')
                ->join('media m', 'm.id = pi.media_id')
                ->where('pi.project_id', $project->id)
                ->orderBy('pi.sort_order')
                ->get()->getRowArray();
            $out[] = ['project' => $project, 'imageUrl' => $row ? base_url('uploads/' . $row['filename']) : null];
        }

        return $out;
    }
}
