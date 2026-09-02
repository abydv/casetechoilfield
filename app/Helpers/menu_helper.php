<?php

use App\Models\MenuItemModel;
use App\Models\MenuModel;

/**
 * cms_menu('main') returns the resolved item tree for the menu whose
 * `location` is 'main' (or 'footer', etc.), or [] if the admin hasn't
 * assigned one yet — callers should fall back to a sensible default
 * when empty (see site/layouts/main.php). Location, not slug, is the
 * field that means "where this menu renders" — a menu's slug is just
 * its own URL-safe identifier and has no fixed value.
 */
if (! function_exists('cms_menu')) {
    function cms_menu(string $location): array
    {
        static $cache = [];

        if (array_key_exists($location, $cache)) {
            return $cache[$location];
        }

        $menu = (new MenuModel())->findByLocation($location);
        $cache[$location] = $menu ? (new MenuItemModel())->resolvedTree($menu['id']) : [];

        return $cache[$location];
    }
}
