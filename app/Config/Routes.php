<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --- Public frontend --------------------------------------------------
// Content-driven routes (pages, products, services, projects, custom
// content types) are added incrementally as those controllers are built
// (see docs/cms-specification.md). Only the health check exists for now.
$routes->get('/', 'Home::index');
$routes->get('healthz', 'Health::check');
$routes->get('products', 'Site\ProductController::index');
$routes->get('products/(:segment)', 'Site\ProductController::show/$1');
$routes->get('services', 'Site\ServiceController::index');
$routes->get('services/(:segment)', 'Site\ServiceController::show/$1');
$routes->get('projects', 'Site\ProjectController::index');
$routes->get('projects/(:segment)', 'Site\ProjectController::show/$1');
$routes->post('enquiry', 'Site\EnquiryController::submit');

// --- Admin: auth (unauthenticated) -------------------------------------
$routes->get('admin/login', 'Admin\AuthController::showLogin');
$routes->post('admin/login', 'Admin\AuthController::attemptLogin');
$routes->get('admin/login/verify', 'Admin\AuthController::showVerify');
$routes->post('admin/login/verify', 'Admin\AuthController::attemptVerify');
$routes->get('admin/logout', 'Admin\AuthController::logout');

// --- Admin: everything else requires an authenticated session ----------
$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin\DashboardController::index');

    $routes->get('product-categories', 'Admin\ProductCategoryController::index');
    $routes->get('product-categories/create', 'Admin\ProductCategoryController::create');
    $routes->post('product-categories', 'Admin\ProductCategoryController::store');
    $routes->get('product-categories/(:num)/edit', 'Admin\ProductCategoryController::edit/$1');
    $routes->post('product-categories/(:num)/update', 'Admin\ProductCategoryController::update/$1');
    $routes->post('product-categories/(:num)/delete', 'Admin\ProductCategoryController::delete/$1');

    $routes->get('products', 'Admin\ProductController::index');
    $routes->get('products/create', 'Admin\ProductController::create');
    $routes->post('products', 'Admin\ProductController::store');
    $routes->get('products/(:num)/edit', 'Admin\ProductController::edit/$1');
    $routes->post('products/(:num)/update', 'Admin\ProductController::update/$1');
    $routes->post('products/(:num)/delete', 'Admin\ProductController::delete/$1');
    $routes->post('products/(:num)/images/(:num)/delete', 'Admin\ProductController::deleteImage/$1/$2');
    $routes->post('products/(:num)/documents/(:num)/delete', 'Admin\ProductController::deleteDocument/$1/$2');

    $routes->get('service-categories', 'Admin\ServiceCategoryController::index');
    $routes->get('service-categories/create', 'Admin\ServiceCategoryController::create');
    $routes->post('service-categories', 'Admin\ServiceCategoryController::store');
    $routes->get('service-categories/(:num)/edit', 'Admin\ServiceCategoryController::edit/$1');
    $routes->post('service-categories/(:num)/update', 'Admin\ServiceCategoryController::update/$1');
    $routes->post('service-categories/(:num)/delete', 'Admin\ServiceCategoryController::delete/$1');

    $routes->get('services', 'Admin\ServiceController::index');
    $routes->get('services/create', 'Admin\ServiceController::create');
    $routes->post('services', 'Admin\ServiceController::store');
    $routes->get('services/(:num)/edit', 'Admin\ServiceController::edit/$1');
    $routes->post('services/(:num)/update', 'Admin\ServiceController::update/$1');
    $routes->post('services/(:num)/delete', 'Admin\ServiceController::delete/$1');
    $routes->post('services/(:num)/images/(:num)/delete', 'Admin\ServiceController::deleteImage/$1/$2');
    $routes->post('services/(:num)/documents/(:num)/delete', 'Admin\ServiceController::deleteDocument/$1/$2');

    $routes->get('projects', 'Admin\ProjectController::index');
    $routes->get('projects/create', 'Admin\ProjectController::create');
    $routes->post('projects', 'Admin\ProjectController::store');
    $routes->get('projects/(:num)/edit', 'Admin\ProjectController::edit/$1');
    $routes->post('projects/(:num)/update', 'Admin\ProjectController::update/$1');
    $routes->post('projects/(:num)/delete', 'Admin\ProjectController::delete/$1');
    $routes->post('projects/(:num)/images/(:num)/delete', 'Admin\ProjectController::deleteImage/$1/$2');
    $routes->post('projects/(:num)/documents/(:num)/delete', 'Admin\ProjectController::deleteDocument/$1/$2');
});
