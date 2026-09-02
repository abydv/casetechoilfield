<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\ContentTypeModel;
use App\Models\PageModel;
use App\Models\RedirectModel;
use App\Services\NotFoundLogger;
use App\Services\PageRenderer;
use Config\Database;

class PageController extends BaseController
{
    public function show(string $slug)
    {
        $redirect = (new RedirectModel())->findActiveMatch($slug);
        if ($redirect) {
            (new RedirectModel())->recordHit((int) $redirect['id']);

            return redirect()->to($redirect['to_path'], (int) $redirect['status_code']);
        }

        $page = (new PageModel())->findBySlug($slug);
        if (! $page || ! $page->isPublished()) {
            // Not a Page — see if a custom content type owns this slug
            // (Admin\ContentTypeController; docs/architecture.md §5)
            // before giving up and logging a 404.
            if ((new ContentTypeModel())->findBySlug($slug)) {
                return (new ContentEntryController())->index($slug);
            }

            NotFoundLogger::record($slug, $this->request->getServer('HTTP_REFERER'));
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
