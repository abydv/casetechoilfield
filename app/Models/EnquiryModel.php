<?php

namespace App\Models;

use CodeIgniter\Model;

class EnquiryModel extends Model
{
    protected $table         = 'enquiries';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'product_id', 'service_id', 'form_submission_id', 'name', 'company',
        'email', 'phone', 'quantity', 'message', 'source_url', 'status',
        'assigned_to', 'follow_up_date',
    ];

    protected $validationRules = [
        'name'  => 'required|max_length[150]',
        'email' => 'required|valid_email|max_length[191]',
    ];
}
