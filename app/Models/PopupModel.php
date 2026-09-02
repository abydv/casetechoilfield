<?php

namespace App\Models;

use CodeIgniter\Model;

class PopupModel extends Model
{
    protected $table         = 'popups';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'type', 'title', 'content', 'page_targeting', 'delay_seconds',
        'start_date', 'end_date', 'frequency', 'show_desktop', 'show_mobile', 'status',
    ];

    protected $validationRules = [
        'type' => 'required|in_list[announcement_bar,promo_popup,newsletter_popup,product_popup]',
    ];

    /** Active published popups of the given type(s), respecting the date window. */
    public function active(array $types): array
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->whereIn('type', $types)->where('status', 'published')
            ->groupStart()->where('start_date', null)->orWhere('start_date <=', $now)->groupEnd()
            ->groupStart()->where('end_date', null)->orWhere('end_date >=', $now)->groupEnd();

        return $builder->findAll();
    }
}
