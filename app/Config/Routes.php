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
});
