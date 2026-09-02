<?php

use App\Models\PopupModel;

/**
 * active_popups(['promo_popup', ...]) returns published popups of the
 * given types whose date window (if any) currently includes today —
 * see App\Models\PopupModel::active(). Cached per request.
 */
if (! function_exists('active_popups')) {
    function active_popups(array $types): array
    {
        static $cache = [];
        $key = implode(',', $types);

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = (new PopupModel())->active($types);
        }

        return $cache[$key];
    }
}
