<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --- Public frontend --------------------------------------------------
// Content-driven routes (pages, products, services, projects, custom
// content types) are added incrementally as those controllers are built
// (see docs/cms-specification.md). Only the health check exists for now.
$routes->get('/', 'Home::index');
$routes->get('healthz', 'Health::check');

// --- Admin: auth (unauthenticated) -------------------------------------
$routes->get('admin/login', 'Admin\AuthController::showLogin');
$routes->post('admin/login', 'Admin\AuthController::attemptLogin');
$routes->get('admin/login/verify', 'Admin\AuthController::showVerify');
$routes->post('admin/login/verify', 'Admin\AuthController::attemptVerify');
$routes->get('admin/logout', 'Admin\AuthController::logout');

// --- Admin: everything else requires an authenticated session ----------
$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin\DashboardController::index');
});
