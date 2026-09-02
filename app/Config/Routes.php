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
$routes->get('sitemap.xml', 'Site\SeoController::sitemap');
$routes->get('robots.txt', 'Site\SeoController::robots');
$routes->get('forms/(:segment)', 'Site\FormController::show/$1');
$routes->post('forms/(:segment)', 'Site\FormController::submit/$1');

// --- Admin: auth (unauthenticated) -------------------------------------
// NOTE: admin routes are registered before the generic page catch-all
// at the bottom of this file (registration order controls match order),
// so /admin/* is never swallowed by Site\PageController.
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

    $routes->get('enquiries', 'Admin\EnquiryController::index');
    $routes->get('enquiries/export', 'Admin\EnquiryController::export');
    $routes->get('enquiries/(:num)', 'Admin\EnquiryController::show/$1');
    $routes->post('enquiries/(:num)/update', 'Admin\EnquiryController::update/$1');

    $routes->get('pages', 'Admin\PageController::index');
    $routes->get('pages/create', 'Admin\PageController::create');
    $routes->post('pages', 'Admin\PageController::store');
    $routes->get('pages/(:num)/edit', 'Admin\PageController::edit/$1');
    $routes->post('pages/(:num)/update', 'Admin\PageController::update/$1');
    $routes->post('pages/(:num)/delete', 'Admin\PageController::delete/$1');
    $routes->post('pages/(:num)/sections', 'Admin\PageController::addSection/$1');
    $routes->post('pages/(:num)/sections/(:num)/delete', 'Admin\PageController::deleteSection/$1/$2');

    $routes->get('media', 'Admin\MediaController::index');
    $routes->post('media/upload', 'Admin\MediaController::upload');
    $routes->post('media/folders', 'Admin\MediaController::storeFolder');
    $routes->post('media/(:num)/update', 'Admin\MediaController::update/$1');
    $routes->post('media/(:num)/delete', 'Admin\MediaController::delete/$1');

    $routes->get('menus', 'Admin\MenuController::index');
    $routes->post('menus', 'Admin\MenuController::store');
    $routes->get('menus/(:num)/edit', 'Admin\MenuController::edit/$1');
    $routes->post('menus/(:num)/delete', 'Admin\MenuController::delete/$1');
    $routes->post('menus/(:num)/items', 'Admin\MenuController::addItem/$1');
    $routes->post('menus/(:num)/items/(:num)', 'Admin\MenuController::updateItem/$1/$2');
    $routes->post('menus/(:num)/items/(:num)/delete', 'Admin\MenuController::deleteItem/$1/$2');

    $routes->get('settings', 'Admin\SettingsController::general');
    $routes->post('settings', 'Admin\SettingsController::saveGeneral');
    $routes->get('settings/smtp', 'Admin\SettingsController::smtp');
    $routes->post('settings/smtp', 'Admin\SettingsController::saveSmtp');
    $routes->post('settings/smtp/test', 'Admin\SettingsController::sendTestEmail');
    $routes->get('settings/captcha', 'Admin\SettingsController::captcha');
    $routes->post('settings/captcha', 'Admin\SettingsController::saveCaptcha');

    $routes->get('redirects', 'Admin\RedirectController::index');
    $routes->post('redirects', 'Admin\RedirectController::store');
    $routes->post('redirects/from-not-found', 'Admin\RedirectController::fromNotFound');
    $routes->post('redirects/(:num)/delete', 'Admin\RedirectController::delete/$1');
    $routes->post('redirects/(:num)/toggle', 'Admin\RedirectController::toggle/$1');

    $routes->get('users', 'Admin\UserController::index');
    $routes->get('users/create', 'Admin\UserController::create');
    $routes->post('users', 'Admin\UserController::store');
    $routes->get('users/(:num)/edit', 'Admin\UserController::edit/$1');
    $routes->post('users/(:num)/update', 'Admin\UserController::update/$1');
    $routes->post('users/(:num)/delete', 'Admin\UserController::delete/$1');

    $routes->get('forms', 'Admin\FormController::index');
    $routes->get('forms/create', 'Admin\FormController::create');
    $routes->post('forms', 'Admin\FormController::store');
    $routes->get('forms/(:num)/edit', 'Admin\FormController::edit/$1');
    $routes->post('forms/(:num)/update', 'Admin\FormController::update/$1');
    $routes->post('forms/(:num)/delete', 'Admin\FormController::delete/$1');
    $routes->post('forms/(:num)/fields', 'Admin\FormController::addField/$1');
    $routes->post('forms/(:num)/fields/(:num)/delete', 'Admin\FormController::deleteField/$1/$2');
    $routes->get('forms/(:num)/submissions', 'Admin\FormController::submissions/$1');

    $routes->get('revisions/(:alpha)/(:num)', 'Admin\RevisionController::index/$1/$2');
    $routes->post('revisions/(:alpha)/(:num)/(:num)/restore', 'Admin\RevisionController::restore/$1/$2/$3');
});

// --- Public: generic CMS page catch-all --------------------------------
// Must be the LAST route registered: any path not matched above (an
// admin-created Page's slug — about-us, contact-us, privacy-policy, ...)
// falls through to here. Registration order determines match priority in
// CodeIgniter, so nothing above this line can be shadowed by it.
$routes->get('(:segment)', 'Site\PageController::show/$1');
