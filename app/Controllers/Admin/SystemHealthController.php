<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\SystemHealthService;

class SystemHealthController extends BaseController
{
    public function index()
    {
        return view('admin/system_health/index', ['health' => (new SystemHealthService())->gather()]);
    }
}
