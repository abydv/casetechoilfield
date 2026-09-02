<?php

namespace App\Controllers;

use App\Models\ProductModel;
use Config\Database;

/**
 * Homepage. Content mirrors docs/current-site-audit.md §5 (the live
 * site's real copy) until the page builder (docs/cms-specification.md
 * §2) lets the admin edit this as ordinary sections — see that doc for
 * the plan to make this fully CMS-controlled.
 */
class Home extends BaseController
{
    public function index()
    {
        $productModel = new ProductModel();
        $db = Database::connect();

        $featured = $productModel->publishedQuery()->orderBy('sort_order', 'ASC')->findAll(8);
        $products = [];
        foreach ($featured as $product) {
            $url = null;
            if ($product->main_image_media_id) {
                $row = $db->table('media')->select('filename')->where('id', $product->main_image_media_id)->get()->getRowArray();
                $url = $row ? base_url('uploads/' . $row['filename']) : null;
            }
            $products[] = ['product' => $product, 'imageUrl' => $url];
        }

        return view('site/home', ['products' => $products]);
    }
}
