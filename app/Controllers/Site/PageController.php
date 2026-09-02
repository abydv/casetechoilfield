<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\PageModel;
use App\Services\PageRenderer;
use Config\Database;

class PageController extends BaseController
{
    public function show(string $slug)
    {
        $page = (new PageModel())->findBySlug($slug);
        if (! $page || ! $page->isPublished()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $seo = $page->seo_meta_id
            ? Database::connect()->table('seo_meta')->where('id', $page->seo_meta_id)->get()->getRowArray()
            : null;

        $content = (new PageRenderer())->render((int) $page->id);

        return view('site/pages/show', [
            'page'        => $page,
            'seo'         => $seo,
            'content'     => $content,
            'breadcrumbs' => [['label' => $page->title, 'url' => null]],
        ]);
    }
}
