<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\ServiceCategoryModel;
use App\Models\ServiceModel;
use Config\Database;

class ServiceController extends BaseController
{
    private ServiceModel $services;
    private ServiceCategoryModel $categories;

    public function __construct()
    {
        $this->services   = new ServiceModel();
        $this->categories = new ServiceCategoryModel();
    }

    public function index()
    {
        $search = $this->request->getGet('q');
        $categorySlug = $this->request->getGet('category');
        $categoryId = null;
        $activeCategory = null;

        if ($categorySlug) {
            $activeCategory = $this->categories->findBySlug($categorySlug);
            $categoryId = $activeCategory->id ?? null;
        }

        $services = $this->services->publishedQuery();
        if ($categoryId) {
            $services = $services->where('category_id', $categoryId);
        }
        if ($search) {
            $services = $services->like('name', $search);
        }
        $services = $services->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->paginate(12);

        return view('site/services/index', [
            'services'       => $this->attachThumbnails($services),
            'pager'          => $this->services->pager,
            'categories'     => $this->categories->orderedTree(),
            'search'         => $search,
            'activeCategory' => $activeCategory,
        ]);
    }

    public function show(string $slug)
    {
        $service = $this->services->findBySlug($slug);
        if (! $service || ! $service->isPublished()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = Database::connect();
        $category = $service->category_id ? $this->categories->find($service->category_id) : null;

        $seo = $service->seo_meta_id
            ? $db->table('seo_meta')->where('id', $service->seo_meta_id)->get()->getRowArray()
            : null;

        $gallery = $db->table('service_images si')
            ->select('m.filename')
            ->join('media m', 'm.id = si.media_id')
            ->where('si.service_id', $service->id)
            ->orderBy('si.sort_order')
            ->get()->getResultArray();
        $galleryUrls = array_map(fn ($row) => base_url('uploads/' . $row['filename']), $gallery);

        $documents = $db->table('service_documents sd')
            ->select('sd.label, sd.doc_type, m.filename, m.original_filename')
            ->join('media m', 'm.id = sd.media_id')
            ->where('sd.service_id', $service->id)
            ->get()->getResultArray();

        $related = [];
        if ($category) {
            $related = $this->services->publishedQuery()
                ->where('category_id', $category->id)
                ->where('id !=', $service->id)
                ->orderBy('sort_order', 'ASC')
                ->findAll(4);
            $related = $this->attachThumbnails($related);
        }

        $breadcrumbs = [['label' => 'Services', 'url' => site_url('services')]];
        if ($category) {
            $breadcrumbs[] = ['label' => $category->name, 'url' => site_url('services?category=' . $category->slug)];
        }
        $breadcrumbs[] = ['label' => $service->name, 'url' => null];

        return view('site/services/show', [
            'service'     => $service,
            'category'    => $category,
            'seo'         => $seo,
            'gallery'     => $galleryUrls,
            'documents'   => $documents,
            'related'     => $related,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * @param iterable<\App\Entities\Service> $services
     */
    private function attachThumbnails(iterable $services): array
    {
        $db = Database::connect();
        $out = [];
        foreach ($services as $service) {
            $row = $db->table('service_images si')
                ->select('m.filename')
                ->join('media m', 'm.id = si.media_id')
                ->where('si.service_id', $service->id)
                ->orderBy('si.sort_order')
                ->get()->getRowArray();
            $out[] = ['service' => $service, 'imageUrl' => $row ? base_url('uploads/' . $row['filename']) : null];
        }

        return $out;
    }
}
