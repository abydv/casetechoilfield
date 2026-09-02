<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\SeoHealthService;

class SeoHealthController extends BaseController
{
    public function index()
    {
        return view('admin/seo_health/index', ['report' => (new SeoHealthService())->report()]);
    }
}
