<?php

namespace App\Models;

use CodeIgniter\Model;

class FormModel extends Model
{
    protected $table         = 'forms';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'slug', 'recipient_emails', 'success_message', 'redirect_url',
        'store_in_db', 'captcha_provider', 'auto_response_enabled',
        'auto_response_subject', 'auto_response_body',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[150]',
    ];

    public function findBySlug(string $slug)
    {
        return $this->where('slug', $slug)->first();
    }
}
