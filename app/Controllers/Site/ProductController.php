<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use Config\Database;

class ProductController extends BaseController
{
    private ProductModel $products;
    private ProductCategoryModel $categories;

    public function __construct()
    {
        $this->products   = new ProductModel();
        $this->categories = new ProductCategoryModel();
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

        $products = $this->products->publishedQuery();
        if ($categoryId) {
            $products = $products->where('category_id', $categoryId);
        }
        if ($search) {
            $products = $products->groupStart()->like('name', $search)->orLike('product_code', $search)->groupEnd();
        }
        $products = $products->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->paginate(12);

        return view('site/products/index', [
            'products'       => $this->attachMainImages($products),
            'pager'          => $this->products->pager,
            'categories'     => $this->categories->orderedTree(),
            'search'         => $search,
            'activeCategory' => $activeCategory,
        ]);
    }

    public function show(string $slug)
    {
        $product = $this->products->findBySlug($slug);
        if (! $product || ! $product->isPublished()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = Database::connect();

        $category = $product->category_id ? $this->categories->find($product->category_id) : null;

        $seo = $product->seo_meta_id
            ? $db->table('seo_meta')->where('id', $product->seo_meta_id)->get()->getRowArray()
            : null;

        $mainImage = $product->main_image_media_id ? $this->mediaUrl((int) $product->main_image_media_id) : null;

        $gallery = $db->table('product_images pi')
            ->select('m.filename')
            ->join('media m', 'm.id = pi.media_id')
            ->where('pi.product_id', $product->id)
            ->orderBy('pi.sort_order')
            ->get()->getResultArray();
        $galleryUrls = array_map(fn ($row) => base_url('uploads/' . $row['filename']), $gallery);

        $specs = $db->table('product_specifications')->where('product_id', $product->id)->orderBy('sort_order')->get()->getResultArray();

        $documents = $db->table('product_documents pd')
            ->select('pd.label, pd.doc_type, m.filename, m.original_filename')
            ->join('media m', 'm.id = pd.media_id')
            ->where('pd.product_id', $product->id)
            ->get()->getResultArray();

        $related = [];
        if ($category) {
            $related = $this->products->publishedQuery()
                ->where('category_id', $category->id)
                ->where('id !=', $product->id)
                ->orderBy('sort_order', 'ASC')
                ->findAll(4);
            $related = $this->attachMainImages($related);
        }

        $breadcrumbs = [
            ['label' => 'Products', 'url' => site_url('products')],
        ];
        if ($category) {
            $breadcrumbs[] = ['label' => $category->name, 'url' => site_url('products?category=' . $category->slug)];
        }
        $breadcrumbs[] = ['label' => $product->name, 'url' => null];

        return view('site/products/show', [
            'product'     => $product,
            'category'    => $category,
            'seo'         => $seo,
            'mainImage'   => $mainImage,
            'gallery'     => $galleryUrls,
            'specs'       => $specs,
            'documents'   => $documents,
            'related'     => $related,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    private function mediaUrl(int $mediaId): ?string
    {
        $row = Database::connect()->table('media')->select('filename')->where('id', $mediaId)->get()->getRowArray();

        return $row ? base_url('uploads/' . $row['filename']) : null;
    }

    /**
     * @param iterable<\App\Entities\Product> $products
     */
    private function attachMainImages(iterable $products): array
    {
        $db = Database::connect();
        $out = [];
        foreach ($products as $product) {
            $url = null;
            if ($product->main_image_media_id) {
                $row = $db->table('media')->select('filename')->where('id', $product->main_image_media_id)->get()->getRowArray();
                $url = $row ? base_url('uploads/' . $row['filename']) : null;
            }
            $out[] = ['product' => $product, 'imageUrl' => $url];
        }

        return $out;
    }
}
